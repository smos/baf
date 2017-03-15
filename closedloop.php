<?php
/*
Copyright 2017 Seth Mos <seth.mos@dds.nl>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

declare(ticks=1); // PHP internal, make signal handling work

// Import Config
ini_set ('include_path', '.:/home/pi/baf');
include("vars.php");
include("functions.php");

if (!function_exists('pcntl_signal')) {
	printf("Error, you need to enable the pcntl extension in your php binary, see http://www.php.net/manual/en/pcntl.installation.php for more info%s", PHP_EOL);
	exit(1);
}
pcntl_signal(SIGINT, 'shutdown');
pcntl_signal(SIGTERM, 'shutdown');

use Pkj\Raspberry\PiFace\PiFaceDigital;
require 'vendor/autoload.php';

$dev = PiFaceDigital::create();

$shm_p1_id = open_shm($shm_p1_key, $seg_size, "a");
$shm_state_id = open_shm($shm_state_key, $seg_size, "w");
$shm_batt_id = open_shm($shm_batt_key, $seg_size, "w");

$state = array();
/* this decides the direction from -2 to +2 */
$state['time'] = time();
$state[-2] = 0;
$state[-1] = 0;
$state[0] = time();
$state[1] = 0;
$state[2] = 0;
$state['duration'][-2] = 0;
$state['duration'][-1] = 0;
$state['duration'][0] = 0;
$state['duration'][1] = 0;
$state['duration'][2] = 0;
$state['operation'] = 0;
$state['available_power'] = 0;
$state['charger_power'] = 0;
$state['charger_throttle'] = 0;
$state['inverter_power'] = 0;
$state['inverter_throttle'] = 0;
$state['battery'] = "";
$state['battery_connect'] = false;
$state['message'] = "Starting up";
$state['message_time'] = date("Y-m-d H:i:s");
foreach($cfg['inverters'] as $idx => $inverter) {
	$state['inverters'][$idx]['ac'] = false;
	$state['inverters'][$idx]['dc'] = false;
	$state['inverters'][$idx]['pwm'] = false;
	$state['inverters'][$idx]['time'] = 0;
}
foreach($cfg['chargers'] as $idx => $charger) {
	$state['chargers'][$idx]['ac'] = false;
	$state['chargers'][$idx]['dc'] = false;
	$state['chargers'][$idx]['pwm'] = false;
	$state['chargers'][$idx]['time'] = 0;
}
// Pre flight check

// Run once.
if($dev->init() === false) {
	log_message($state, "Failed to initialize the SPI interface");
}

$battstate = array();
// Battery status should connect the battery for us if it's good
$battstate = battery_status($cfg, $battstate);
/* write the startup state so it's live */
write_state_shm($shm_state_id, $state);
write_state_shm($shm_batt_id, $battstate);

if($state['battery_connect'] === false)
	log_message($state, "Something horribly wrong with the battery");

// Enter closed loop
while($state['battery_connect']) {
	// Check if the sensor is current atleast
	$p1_pow = read_p1_shm($shm_p1_key, $seg_size);
	$battstate = battery_status($cfg, $battstate);
	write_state_shm($shm_batt_id, $battstate);
	$state['available_power'] = 0 + $p1_pow['power_gen_cur'] - $p1_pow['power_cons_cur'];
	$batt_hysteresis = 0;
	// If charging or discharging we need to apply hysteresis
	if(($state['operation'] == 2) || ($state['operation'] == -2))
		$batt_hysteresis = $cfg['batt_hysteresis'];

	if((time() - $p1_pow['time']) > 30) {
		$state = log_message($state,"Sensor is atleast 30 seconds out of date, disable charger(s) and inverter(s)");
		$state['operation'] = 0;
		$state['charger_pwm'] = 0;
		$state['inverter_pwm'] = 0;
		$state['available_power'] = 0;
		$state = drive_inverters($state);
		$state = drive_chargers($state);
		write_state_shm($shm_state_id, $state);
		sleep($cfg['timer_loop']);
		continue;
	}
	// Check low battery status without generation
	if(($p1_pow['power_gen_cur'] < $cfg['pow_gen_min']) && (($battstate['cell_min'] + $batt_hysteresis)< $cfg['batt_cell_min'])) {
		if($state['battery'] != "empty")
			$state = log_message($state,"No generation, battery empty");
		$state['operation'] = 0;
		$state['charger_pwm'] = 0;
		$state['inverter_pwm'] = 0;
		$state['battery'] = "empty";
		$state = drive_inverters($state);
		$state = drive_chargers($state);
		$state['available_power'] = 0 + $p1_pow['power_gen_cur'] - $p1_pow['power_cons_cur'];
		write_state_shm($shm_state_id, $state);
		sleep($cfg['timer_loop']);
		continue;
	}
	// Check high battery status without consumption
	if(($p1_pow['power_cons_cur'] < $cfg['pow_cons_min']) && (($battstate['cell_max'] - $batt_hysteresis) > $cfg['batt_cell_max'])) {
		if($state['battery'] != "full")
			$state = log_message($state,"No consumption, battery full");
		$state['operation'] = 0;
		$state['charger_pwm'] = 0;
		$state['inverter_pwm'] = 0;
		$state['battery'] = "full";
		$state = drive_inverters($state);
		$state = drive_chargers($state);
		$state['available_power'] = 0 + $p1_pow['power_gen_cur'] - $p1_pow['power_cons_cur'];
		write_state_shm($shm_state_id, $state);
		sleep($cfg['timer_loop']);
		continue;
	}

	// Still here? Good. Let the controller attempt to make some decisions
	$state = controller($cfg, $state, $p1_pow, $battstate, $dev);
	write_state_shm($shm_state_id, $state);

	// print_r($dev->getOutputPins());
	// echo "Operating Normally, consumption {$p1_pow['power_cons_cur']}, generation {$p1_pow['power_gen_cur']} sleeping for just about {$cfg['timer_loop']}\n";
	sleep($cfg['timer_loop']);

}
$state = log_message($state,"Cleaning up state shared memory");
close_shm ($shm_state_id);
$dev->init();
$state = log_message($state, "Exiting");
?>
