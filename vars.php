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

// Create 1k byte shared memory block with system id of 0xff3
$shm_p1_key = 0x1337;
$shm_raw_key = 0xb33f;
$shm_state_key = 0xd34d;
$shm_batt_key = 0x50c4;
$seg_size = 2048;

// Serial port
$p1_serial = "/dev/ttyUSB0";
$p1_baud = "9600";
$p1_bits = "7";
$p1_parity = "E";
$p1_stopbits = "1";

// Timers
$cfg['timer_loop'] = 3;
$cfg['timer_wait'] = 60;

// Battery
$cfg['batt_dcpin'] = 6;
$cfg['batt_volt_crit_min'] = 3.2; // Volt
$cfg['batt_volt_crit_max'] = 4.2;
$cfg['batt_volt_min'] = 3.45;
$cfg['batt_volt_max'] = 4.05;
$cfg['batt_cell_cmd'] = "python ~/baf/readvoltage.py";
$cfg['batt_voltage_div'] = ((56000+6800)/6800);

// Define dead-band Thresholds
$cfg['pow_gen_min'] = 10; // Watts
$cfg['pow_cons_min'] = 10;

// Set PWM limits for our charger and inverter, depends on the battery
$cfg['inverters'][1]['pwm_min'] = 20; // Percent
$cfg['inverters'][1]['pwm_max'] = 100;
$cfg['inverters'][1]['power'] = 240; // Watts
$cfg['inverters'][1]['acpin'] = 0;
$cfg['inverters'][1]['dcpin'] = 1;
$cfg['inverters'][1]['standby'] = 300;
/*
$cfg['inverters'][2]['pwm_min'] = 5; // Percent
$cfg['inverters'][2]['pwm_max'] = 100;
$cfg['inverters'][2]['power'] = 240; // Watts
$cfg['inverters'][2]['acpin'] = 2;
$cfg['inverters'][2]['dcpin'] = 3;
$cfg['inverters'][2]['standby'] = 120;
*/
$cfg['chargers'][1]['pwm_min'] = 20; // Percent
$cfg['chargers'][1]['pwm_max'] = 100;
$cfg['chargers'][1]['power'] = 185; // Watts
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
