<?php /* Copyright 2017 Seth Mos <seth.mos@dds.nl>
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

// Create 1k byte shared memory block with system id of 0xff3
$shm_p1_key = 0x1337;
$shm_raw_key = 0xb33f;
$shm_state_key = 0xd34d;
$shm_batt_key = 0x50c4;
$seg_size = 1024;

// Serial port
$p1_serial = "/dev/ttyUSB0";
$p1_baud = "9600";
$p1_bits = "7";
$p1_parity = "E";
$p1_stopbits = "1";

// Timers
$cfg['timer_loop'] = 1;
$cfg['timer_wait'] = 60;

// Simulate a battery
$cfg['simulate'] = false;
$cfg['simulate_step'] = 0.01;
$cfg['batt_cells'] = 8;

// Battery
$cfg['batt_dcpin'] = 6;
/* shared battery PWM channel, if used, set the individual PWM min to 100% if so */
/* We calculate the drive value based on the individual power and PWM values */
$cfg['batt_pwm_channel'] = 2;
$cfg['batt_pwm_shared'] = true;
if($cfg['simulate'] === false)
	$cfg['batt_cells'] = 8;
$cfg['batt_volt_crit_min'] = 3.2 * $cfg['batt_cells']; // Volt
$cfg['batt_volt_crit_max'] = 4.2 * $cfg['batt_cells'];
$cfg['batt_cell_crit_min'] = 3.2; // Volt
$cfg['batt_cell_crit_max'] = 4.2;
$cfg['batt_cell_min'] = 3.55;
$cfg['batt_cell_max'] = 4.0;
$cfg['batt_volt_min'] = $cfg['batt_cell_min'] * $cfg['batt_cells'];
$cfg['batt_volt_max'] = $cfg['batt_cell_max'] * $cfg['batt_cells'];
$cfg['batt_cell_cmd'] = "python ~/baf/readvoltage.py";
// Use the calculator https://www.abelectronics.co.uk/tools/adc-pi-input-calc
$cfg['batt_voltage_div'] = 6.9569;
// Per cel voltage reading correction
$cfg['batt_cell_correction'][0] = 0.996;
$cfg['batt_cell_correction'][1] = 0.995;
$cfg['batt_cell_correction'][2] = 0.981;
$cfg['batt_cell_correction'][3] = 0.986;
$cfg['batt_cell_correction'][4] = 0.983;
$cfg['batt_cell_correction'][5] = 0.9945;
$cfg['batt_cell_correction'][6] = 0.976;
$cfg['batt_cell_correction'][7] = 0.985;
$cfg['batt_charge_taper'] = 5; // percent
$cfg['batt_discharge_taper'] = 20; //percent
$cfg['batt_hysteresis'] = 0.05; // Volt
$cfg['batt_timeout'] = 60;

// Maintenance charger AC relay pin
$cfg['maintenance_charger_acpin'] = 4;
$cfg['maintenance_diff'] = 0.2; // Volt

// Define dead-band Thresholds
$cfg['pow_gen_min'] = 20; // Watts
$cfg['pow_cons_min'] = 20;
$cfg['pwm_command'] = "python ~/baf/drive-pwm.py";

// Set PWM limits for our charger and inverter, depends on the battery
$cfg['inverters'][1]['pwm_min'] = 10; // Percent
$cfg['inverters'][1]['pwm_max'] = 30;
$cfg['inverters'][1]['pwm_channel'] = 0;
$cfg['inverters'][1]['pwm_shared'] = true;
$cfg['inverters'][1]['power'] = 600; // Watts
$cfg['inverters'][1]['acpin'] = 0;
$cfg['inverters'][1]['dcpin'] = 1;
$cfg['inverters'][1]['standby'] = 300;
/*
$cfg['inverters'][2]['pwm_min'] = 20; // Percent
$cfg['inverters'][2]['pwm_max'] = 100;
$cfg['inverters'][2]['power'] = 500; // Watts
$cfg['inverters'][2]['acpin'] = 5;
$cfg['inverters'][2]['dcpin'] = 6;
$cfg['inverters'][2]['standby'] = 300;
*/
$cfg['chargers'][1]['pwm_min'] = 10; // Percent
$cfg['chargers'][1]['pwm_max'] = 100;
$cfg['chargers'][1]['pwm_channel'] = 1;
$cfg['chargers'][1]['pwm_shared'] = true;
$cfg['chargers'][1]['power'] = 36; // Watts
$cfg['chargers'][1]['acpin'] = 2;
$cfg['chargers'][1]['dcpin'] = 3;
$cfg['chargers'][1]['standby'] = 60;
/*
$cfg['chargers'][2]['pwm_min'] = 5; // Percent
$cfg['chargers'][2]['pwm_max'] = 100;
$cfg['chargers'][2]['power'] = 320; // Watts
$cfg['chargers'][2]['acpin'] = 0;
$cfg['chargers'][2]['dcpin'] = 1;
$cfg['chargers'][2]['standby'] = 30;
*/

?>
