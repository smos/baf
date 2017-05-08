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

ini_set ('include_path', '.:/home/pi/baf');
include("vars.php");
include("functions.php");

// $fp = fopen("php://input", 'r+');
// $data = stream_get_contents($fp);
$data = file_get_contents("php://input");;
$array = array();
$array['time'] = time();
// syslog(LOG_NOTICE, print_r($_SERVER, true));
$lines = preg_split("/\n/", preg_replace("/\r\n/", "\n", $data));
foreach($lines as $line) {
	$line = trim($line);
	if(!empty($line)) {
		$el = explode(",", $line);
	}
	switch($el[0]) {
		case "181":
			$array['energy_cons_1'] = floatval($el[1]) /1000;
			break;
		case "182":
			$array['energy_cons_2'] = floatval($el[1]) /1000;
			break;
		case "281":
			$array['energy_gen_1'] = floatval($el[1]) /1000;
			break;
		case "282":
			$array['energy_gen_2'] = floatval($el[1]) /1000;
			break;
		case "270":
			$array['power_gen_cur'] = floatval($el[1]) * 10;
			break;
		case "170":
			$array['power_cons_cur'] = floatval($el[1]) * 10;
			break;
		case "2421":
			$array['gas_cons'] = floatval($el[1]) /1000;
			break;
		default:
			// $array[$el[0]] = $el[1];
			break;

	}
}
if(count($array) == 1) {
	syslog(LOG_ERR, "Arduino P1 out of service");
	exit(0);
}
// file_put_contents("/tmp/p1test.txt", $data . print_r($array, true));
$shm_id = open_shm($shm_p1_key, $seg_size, "w");
$shm_raw_id = open_shm($shm_raw_key, $seg_size, "w");
write_p1_shm($shm_id, $shm_raw_id, $array, $data);

?>
