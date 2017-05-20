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

$shm_batt_id = open_shm($shm_batt_key, $seg_size, "w");

$state = array();
/* this decides the direction from -2 to +2 */
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
$state['charger_pwm'] = 0;
$state['inverter_pwm'] = 0;
$state['battery_connect'] = false;
$state['battery'] = "";
$state['message'] = "Starting up";
$state['message_time'] = date("Y-m-d H:i:s");
$state['inverters'][1]['ac'] = false;
$state['inverters'][1]['dc'] = false;
$state['chargers'][1]['ac'] = false;
$state['chargers'][1]['dc'] = false;
// Pre flight check
$cfg['simulate'] = false;
$cfg['batt_cells'] = 8;

$battstate = array();
// Battery status should connect the battery for us if it's good
$battstate = battery_status($cfg, $battstate);

// print_r(return_chargers_power($cfg));
// print_r(return_inverters_power($cfg));

print_r($battstate);



?>
