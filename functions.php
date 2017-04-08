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

function open_shm($shm_key, $seg_size, $mode) {
	global $state;
	/* try opening first */
	$shm_id = @shmop_open($shm_key, $mode, 0, 0);
	// echo "found shm id $shm_id \n";
	if(!$shm_id){
		$state = log_message($state, "Couldn't find shared memory segment for key {$shm_key}, creating");
			$shm_id = shmop_open($shm_key, "c", 0664, $seg_size);
	}
	return $shm_id;
}

function close_shm ($shm_id) {
	global $state;
	//Now lets delete the block and close the shared memory segment
	if (!shmop_delete($shm_id)) {
		$state = log_message($state, "Couldn't mark shared memory block for deletion.");
	}
	shmop_close($shm_id);
}

function write_state_shm($shm_id, $state) {
	// Get shared memory block's size
	$state['time'] = time();
	$shm_size = shmop_size($shm_id);
	$string = str_pad(serialize($state), $shm_size, "\0");
	$shm_bytes_written = shmop_write($shm_id, $string, 0);
	if ($shm_bytes_written != strlen($string)) {
		$state = log_message($state, "The serialized state array data is too large for shm id '{$shm_id}', wrote '{$shm_bytes_written}', size is '{$shm_size}'");
		exit(2);
	}
	return($state);
}

function write_p1_shm($shm_id, $shm_raw_id, $array, $datagram) {
	global $state;
	// Get shared memory block's size
	$shm_size = shmop_size($shm_id);
	$shm_raw_size = shmop_size($shm_raw_id);

	$string = str_pad(serialize($array), $shm_size, "\0");
	$shm_bytes_written = shmop_write($shm_id, $string, 0);
	if ($shm_bytes_written != strlen($string)) {
		$state = log_message($state,"The serialized array data is too large for shm");
		exit(2);
	}
	$datagram =  str_pad($datagram, $shm_raw_size, "\0");
	$shm_bytes_written = shmop_write($shm_raw_id, $datagram, 0);
	if ($shm_bytes_written != strlen($datagram)) {
		$state = log_message($state,"The P1 datagram is too large for shm");
		exit(2);
	}
	shmop_close($shm_id);
	shmop_close($shm_raw_id);
	return($state);
}

function read_p1_shm($shm_key, $seg_size) {
	global $state;
	$data = array();
	$shm_id = open_shm($shm_key, $seg_size, "a");
	$my_string = shmop_read($shm_id, 0, $seg_size);
	if(empty($my_string)) {
	    $state = log_message($state,"Couldn't read from shared memory block");
		return false;
	}
	$data = @unserialize($my_string);
	if(!is_array($data))
		return false;

	shmop_close($shm_id);
	return($data);
}

function return_chargers_power($cfg) {
	$chargers = array();
	$chargers['count'] = count($cfg['chargers']);
	foreach($cfg['chargers'] as $idx => $charger) {
		$power_max[$idx] = ($charger['power'] * $charger['pwm_max'])/100;
		$power_min[$idx] = ($charger['power'] * $charger['pwm_min'])/100;
	}
	/* find smallest charger */
	asort($power_min);
	arsort($power_max);
	$chargers['power_min'] = reset($power_min);
	foreach($power_min as $idx => $value) {
		$chargers['charger_min'] = $idx;
		break;
	}
	foreach($power_max as $idx => $value) {
		$chargers['charger_max'] = $idx;
		break;
	}
	$chargers['power_max'] = array_sum($power_max);
	return $chargers;
}

function return_inverters_power($cfg) {
	$inverters = array();
	$inverters['count'] = count($cfg['inverters']);
	foreach($cfg['inverters'] as $idx => $inverter) {
		$power_max[$idx] = ($inverter['power'] * $inverter['pwm_max'])/100;
		$power_min[$idx] = ($inverter['power'] * $inverter['pwm_min'])/100;
	}
	/* find smallest inverter */
	asort($power_min);
	arsort($power_max);
	$inverters['power_min'] = reset($power_min);
	foreach($power_min as $idx => $value) {
		$inverters['inverter_min'] = $idx;
		break;
	}
	foreach($power_max as $idx => $value) {
		$inverters['inverter_max'] = $idx;
		break;
	}
	$inverters['power_max'] = array_sum($power_max);
	return $inverters;
}

function controller($cfg, $state, $power, $battstate, $dev) {
	/* blink that we are here */
	$dev->getLeds()[7]->turnOn();

	$inverters = return_inverters_power($cfg);
	$chargers = return_chargers_power($cfg);
	/* calculate current charger power */
	$cur_charger_power = $state['charger_power'];
	$cur_inverter_power = $state['inverter_power'];

	$previous_state = previous_state($state);
	/* predictable logical charger and inverter state */
	switch($state['operation']) {
		case -2:
			$state[$state['operation']] = time();
			$state['duration'][$state['operation']] = $state[$state['operation']] - $state[-1];
			/* calculate how much we can drive the PWM, the actual percentage is determined in the driver */
			$power_diff = 0 - $power['power_gen_cur'] + $power['power_cons_cur'] - $cur_inverter_power;
			// $state = log_message($state, "Consumption power diff = {$power_diff}, inverter power {$cur_inverter_power}");
			/* Calculate PWM based on current diff but add current inverter power */
			$state['inverter_power'] = round(($power_diff + $cur_inverter_power) - $cfg['pow_cons_min']);
			$state = drive_inverters($state);
			$state = drive_chargers($state);
			// $state = drive_chargers($state);
			$state['battery'] = "discharging";
			if(($power_diff - $cfg['pow_cons_min'] + $cur_inverter_power) < $inverters['power_min']) {
				$state['operation'] = $state['operation'] + 1;
				$state = log_message($state,"Not enough consumption to drive the Inverter with minimum of {$inverters['power_min']} Watt moving to state {$state['operation']}");
			}
			break;
		case -1:
			$state[$state['operation']] = time();
			$state = drive_inverters($state);
			$state = drive_chargers($state);
			$state['battery'] = "idle";
			if($power['power_cons_cur'] > ($cfg['pow_cons_min'] + $inverters['power_min'])) {
				$state['inverter_power'] = 0;
				$state['duration'][$state['operation']] = $state[$state['operation']] - $state[0];
				$state['operation'] = $state['operation'] - 1;
				$state = log_message($state,"Consumption power {$power['power_cons_cur']} exceeding minimum of {$cfg['pow_cons_min']},  move to state {$state['operation']}");
			}
			if(($power['power_gen_cur'] > $cfg['pow_gen_min']) || ($power['power_cons_cur'] < $cfg['pow_cons_min']))  {
				$state['duration'][$state['operation']] = $state[$state['operation']] - $state[-2];
				$state['operation'] = $state['operation'] + 1;
				$state = log_message($state,"We don't have enough consumption move to idle state {$state['operation']}");
			}
			break;
		case 0:
			$state[$state['operation']] = time();
			if($state['battery'] == "") {
				$state = log_message($state,"Startup, disable everything for now, just to be sure");
			}
			/* reset PWM to 0 */
			$state['charger_power'] = 0;
			$state['inverter_power'] = 0;
			$state['battery'] = "idle";
			$state = drive_inverters($state);
			$state = drive_chargers($state);
			if($state[$previous_state] > 0)
				$state['duration'][$state['operation']] = $state[$state['operation']] - $state[$previous_state];
			/* with that done, let's see about that power levels */
			if((($power['power_gen_cur'] - $cur_charger_power) > 0)) {
				$state['operation'] = $state['operation'] + 1;
				$state = log_message($state,"We have some generation move to standby {$state['operation']}");
			}
			if((($power['power_cons_cur'] - $cur_inverter_power) > 0)) {
				$state['operation'] = $state['operation'] - 1;
				$state = log_message($state,"We have some consumption move to standby {$state['operation']}"); 
			}
			break;
		case 1:
			$state[$state['operation']] = time();
			$state = drive_inverters($state);
			$state = drive_chargers($state);
			$state['battery'] = "idle";
			if($power['power_gen_cur'] > ($cfg['pow_gen_min'] + $chargers['power_min'])) {
				$state['charger_power'] = 0;
				$state['duration'][$state['operation']] = $state[$state['operation']] - $state[0];
				$state['operation'] = $state['operation'] + 1;
				$state = log_message($state,"Generation exceeding minimum, enable AC, move to Charge state {$state['operation']}");
			}
			if(($power['power_cons_cur'] > $cfg['pow_cons_min']) || ($power['power_gen_cur'] < $cfg['pow_gen_min']))  {
				$state['duration'][$state['operation']] = $state[$state['operation']] - $state[2];
				$state['operation'] = $state['operation'] - 1;
				$state = log_message($state,"Not enough generation to start move to idle {$state['operation']}");
			}
			break;
		case 2:
			$state[$state['operation']] = time();
			$state['duration'][$state['operation']] = $state[$state['operation']] - $state[1];

			$power_diff = 0 - $power['power_cons_cur'] + $power['power_gen_cur'] - $cur_charger_power;
			/* calculate how much we can drive the PWM, the actual percentage is determined in the driver */
			$state['charger_power'] = round(($power_diff + $cur_charger_power) - $cfg['pow_gen_min']);
			// $state = log_message($state, "Generation power diff = {$power_diff}, charger power {$cur_charger_power}");
			// $state = drive_inverters($state);
			$state = drive_chargers($state);
			$state = drive_inverters($state);
			$state['battery'] = "charging";
			if(($power_diff - $cfg['pow_gen_min'] + $cur_charger_power) < $chargers['power_min']) {
				$state['operation'] = $state['operation'] - 1;
				$state = log_message($state,"Not enough generation to drive the Charger with minium of {$chargers['power_min']} Watt  moving to state {$state['operation']}");
			}
			break;
	}
	//sleep(0.5);
	$dev->getLeds()[7]->turnOff();

	return($state);
}

function previous_state($state) {
	$times = array();

	$times[-2] = $state[-2];
	$times[-1] = $state[-1];
	$times[0] = $state[0];
	$times[1] = $state[1];
	$times[2] = $state[2];

	/* remove our current state, it's always last ;) */
	unset($times[$state['operation']]);
	arsort($times);

	$previous = array();
	foreach($times as $state => $value) {
		// $previous[$state] = $value;
		return $state;
	}
}

function toggle_battery($bool) {
	global $state;
	global $dev;
	global $cfg;
	if($bool === true) {
		if($state['battery_connect'] === false) {
			$state['battery_connect'] = true;
			$state = log_message($state,"Enable battery relay");
			$dev->getLeds()[$cfg['batt_dcpin']]->turnOn();
			$dev->getOutputPins()[$cfg['batt_dcpin']]->turnOn(); // 1
		}
	}
	if($bool === false) {
		if($state['battery_connect'] === true) {
			$state['battery_connect'] = false;
			$state = log_message($state,"Disable battery relay");
			$dev->getLeds()[$cfg['batt_dcpin']]->turnOff();
			$dev->getOutputPins()[$cfg['batt_dcpin']]->turnOff(); // 0
		}
	}
	return($state);
}

function get_cell_voltages($cfg, $battstate) {
	global $state;
	global $dev;
	/* pretend a dry run with simulate and previous battstate */
	if($cfg['simulate']) {
		if($state['battery'] == "") {
			$cells[1] = 3.95;
			$cells[2] = 3.96;
			$cells[3] = 3.93;
			$cells[4] = 3.95;
			$cells[5] = 3.94;
			$cells[6] = 3.95;
			$cells[7] = 3.95;
			$cells[8] = 3.97;
			$battstate['cells'] = $cells;
		}
		$cells = $battstate['cells'];
		switch($state['operation']) {
			case 2:
				/* pretend we are charging */
				foreach($cells as $idx => $cell) {
					$cells[$idx] = $cell + $cfg['simulate_step'];
				}
				break;
			case -2:
				/* pretend we are discharging */
				foreach($cells as $idx => $cell) {
					$cells[$idx] = $cell - $cfg['simulate_step'];
				}
				break;

		}
		$battstate['cells'] = $cells;
		return $battstate['cells'];
	}
	$cells = array();
	exec("{$cfg['batt_cell_cmd']}", $out, $ret);
	if($ret > 0) {
		if((time() - $battstate['time']) < 10) {
			$state = log_message($state, "Failed to get battery cell voltages, using previous values");
			return $battstate['cells'];
		}
		$state = log_message($state, "Failed to get battery cell voltages");
		$battstate['cells'] = array();
		return $battstate['cells'];
	}


	$previous = 0;
	foreach($out as $line) {
		/* only use configured number of cells */
		if(count($cells) >= $cfg['batt_cells'])
			continue;
		if(!empty(trim($line))) {
			$c_arr = explode(":", $line);
			$previous = array_sum($cells);
			$cells[$c_arr[0]] = floatval(trim($c_arr[1])) * floatval($cfg['batt_voltage_div']) - $previous;
		}
	}
	$battstate['cells'] = $cells;
	$battstate['time'] = time();
	return $battstate['cells'];
}

function battery_status($cfg, $battstate) {
	global $state;

	$cells = get_cell_voltages($cfg, $battstate);

	$battstate['total'] = array_sum($cells);
	$battstate['cells'] = $cells;

	arsort($cells);
	$battstate['cell_min'] = end($cells);
	$battstate['cell_max'] =  reset($cells);
	$cellcount = count($cells);
	/* set 0% at $cfg['batt_volt_crit_min'] and 100% at $cfg['batt_volt_crit_max'] */
	/* use the lowest cell voltage for the battery level, that way you can see it never charges "fully" again */
	$range_diff = floatval($cfg['batt_cell_crit_max']) - floatval($cfg['batt_cell_crit_min']);
	$cell_diff = floatval($battstate['cell_min']) - floatval($cfg['batt_cell_crit_min']);
	$battstate['level'] = round(($cell_diff / $range_diff) * 100);


	if((($battstate['cell_min'] > $cfg['batt_cell_crit_min']) && ($battstate['cell_min'] < $cfg['batt_cell_min'])) && ($state['battery_connect'] === false)) {
		if($state['maintenance'] === false)
			$state = log_message($state,"Battery cell voltage below minimum {$cfg['batt_cell_min']} but above critical {$cfg['batt_cell_crit_min']}, continue");
		$state['charger_throttle'] = 1;
		$state['inverter_throttle'] = 0;
		if($state['operation'] <> 0)
			$state = toggle_battery(true);
	}
	if($battstate['total'] < $cfg['batt_volt_crit_min']) {
		$state = log_message($state,"Battery voltage {$battstate['total']} below critical {$cfg['batt_volt_crit_min']}, abort");
		$state['charger_throttle'] = 0;
		$state['inverter_throttle'] = 0;
		$state = toggle_battery(false);
		shutdown();
		return($battstate);
	}

	if((($battstate['cell_max'] < $cfg['batt_cell_crit_max']) &&  ($battstate['cell_max'] > $cfg['batt_cell_max'])) && ($state['battery_connect'] === false)){
		if($state['maintenance'] === false)
			$state = log_message($state,"Battery cell voltage above maximum {$cfg['batt_cell_max']} but below critical {$cfg['batt_cell_crit_max']}, continue");
		$state['charger_throttle'] = 0;
		$state['inverter_throttle'] = 1;
		if($state['operation'] <> 0)
			$state = toggle_battery(true);
	}
	if($battstate['total'] > $cfg['batt_volt_crit_max']) {
		$state = log_message($state,"Battery voltage {$battstate['total']} above critical {$cfg['batt_volt_crit_max']}, abort");
		$state['charger_throttle'] = 0;
		$state['inverter_throttle'] = 0;
		$state = toggle_battery(false);
		shutdown();
		return($battstate);
	}
	/* normal operating conditions */
	if((($battstate['cell_max'] < $cfg['batt_cell_max']) && ($battstate['cell_min'] > $cfg['batt_cell_min'])) && ($state['battery_connect'] === false) && ($state['operation'] <> 0)) {
		if($state['operation'] <> 0)
			$state = toggle_battery(true);
	}
	if($state['battery_connect'] === true) {
		/* calculate charge and invert throttle based on voltage difference from maximum or minimum */
		$state['charger_throttle'] = round((($cfg['batt_cell_max'] - ($battstate['cell_max'] - $cfg['batt_hysteresis'])) * $cfg['batt_charge_taper']), 2);
		// $state = log_message($state, "Charge throttle is {$state['charger_throttle']}");
		if($state['charger_throttle'] > 1)
			$state['charger_throttle'] = 1;
		//if(($state['charger_throttle'] < 1) && ($state['charger_throttle'] > 0)) 
		//	$state = log_message($state, "Battery almost Full, limiter effective");
		if($state['charger_throttle'] <= 0)
			$state['charger_throttle'] = 0;

		$state['inverter_throttle'] = round(((($battstate['cell_min'] + $cfg['batt_hystersis'])- $cfg['batt_cell_min']) * $cfg['batt_discharge_taper']), 2);
		// $state = log_message($state, "Invert throttle is {$state['inverter_throttle']}");
		if($state['inverter_throttle'] > 1)
			$state['inverter_throttle'] = 1;
		//if(($state['inverter_throttle'] < 1) && ($state['inverter_throttle'] > 0))
		//	$state = log_message($state, "Battery almost empty, limiter effective");
		if($state['inverter_throttle'] <= 0)
			$state['inverter_throttle'] = 0;
	}
	return($battstate);
}

function disable_gpio($state) {
	$state = log_message($state,"Disable all GPIO to defaults (off)");
	return($state);
}

function enable_gpio($state) {
	$state = log_message($state,"Enable all GPIO");
	return($state);
}

function drive_chargers($state) {
	global $cfg;
	global $dev;
	$chargers = return_chargers_power($cfg);
	switch($state['operation']) {
		case -2:
		case -1:
		case 0:
			// Disable AC after standby, disable DC immediate
			foreach($cfg['chargers'] as $idx => $charger) {
				$state = power_device_ac($cfg, $state, "chargers", $idx, 0);
				$state = power_device_dc($cfg, $state, "chargers", $idx, 0);
			}
			break;
		case 1:
			foreach($cfg['chargers'] as $idx => $charger) {
				// Enable AC, keep disabled DC
				$state = power_device_ac($cfg, $state, "chargers", $idx, 1);
				$state = power_device_dc($cfg, $state, "chargers", $idx, 0);
			}
			break;
		case 2:
			// drive PWM
			$state['battery'] = "charging";
			if($state['charger_power'] > ($chargers['power_max'] * $state['charger_throttle']))
				$state['charger_power'] = ($chargers['power_max'] * $state['charger_throttle']);
			if($state['charger_power'] < ($chargers['power_min']))
				$state['charger_power'] = $chargers['power_min'];
			/* Determine which to run */
			$enabled = array();
			$c = 0;
			$pwm = array();
			$cpower = $state['charger_power'];
			foreach($cfg['chargers'] as $idx => $charger) {
				$pwm[$idx] = 0;
				if($cpower >= 0) {
					$enabled[$idx] = true;
					$c++;
				} else {
					$enabled[$idx] = false;
				}
				$cpower = $cpower - (($charger['power'] * $charger['pwm_max'])/100);
			}
			if($c == 1) {
				foreach($enabled as $idx => $value) {
					$pwm[$idx] = (($state['charger_power']/$c)/$cfg['chargers'][$idx]['power']);
				}
			}
			if($c > 1) {
				foreach($enabled as $idx => $value) {
					$factor = (($state['charger_power']/$c) / $cfg['chargers'][$idx]['power']);
					$pwm[$idx] = (($state['charger_power']/$c) / $factor) / $cfg['chargers'][$idx]['power'];
				}
			}
			// Enable AC, Enable DC on demand
			foreach($cfg['chargers'] as $idx => $charger) {
				$state = power_device_ac($cfg, $state, "chargers", $idx, 1);
				if($enabled[$idx] === true) {
					$state = power_device_dc($cfg, $state, "chargers", $idx, $pwm[$idx]);
				} else {
					$state = power_device_ac($cfg, $state, "chargers", $idx, 0);
					$state = power_device_dc($cfg, $state, "chargers", $idx, 0);
				}
			}
			break;
	}
	return($state);
}

function power_device_ac($cfg, $state, $category, $idx, $toggle) {
	global $dev;

	$previousstate = previous_state($state);
	if($toggle == 0) {
		/* Power off */
		switch($category) {
			case "inverters":
				$statetime = $state[-2];
				$dstatetime = $state[$category][$idx]['time'];
				break;
			case "chargers":
				$statetime = $state[2];
				$dstatetime = $state[$category][$idx]['time'];
				break;
			default:
				$statetime = $state[$previousstate];
				$dstatetime = $state[$category][$idx]['time'];
				break;
		}

		if(((time() - $statetime) > $cfg[$category][$idx]['standby'])  && ($state[$category][$idx]['ac'] === true)) {
			$state = log_message($state, "Disable {$category} index {$idx} AC after standby time ".(time() - $statetime)." exceeds {$cfg[$category][$idx]['standby']}");
			$dev->getLeds()[$cfg[$category][$idx]['acpin']]->turnOff();
			$dev->getOutputPins()[$cfg[$category][$idx]['acpin']]->turnOff();
			$state[$category][$idx]['ac'] = false;
		}
		if((((time() - $dstatetime) > $cfg[$category][$idx]['standby'])  && ($state[$category][$idx]['ac'] === true)) && ($dstatetime > 0)) {
			$state = log_message($state, "Disable {$category} index {$idx} AC after standby time ".(time() - $state[$category][$idx]['time'])." exceeds {$cfg[$category][$idx]['standby']}");
			$dev->getLeds()[$cfg[$category][$idx]['acpin']]->turnOff();
			$dev->getOutputPins()[$cfg[$category][$idx]['acpin']]->turnOff();
			$state[$category][$idx]['ac'] = false;
		}
	}
	/* Let's go */
	if(($toggle > 0) && ($state[$category][$idx]['ac'] === false)) {
		$state = log_message($state,"Enable {$category} index $idx AC");
		$dev->getLeds()[$cfg[$category][$idx]['acpin']]->turnOn();
		$dev->getOutputPins()[$cfg[$category][$idx]['acpin']]->turnOn();
		$state[$category][$idx]['ac'] = true;
	}

	return($state);
}

function power_device_dc($cfg, $state, $category, $idx, $pwm) {
	global $dev;
	if(($pwm == 0)){
		if($state[$category][$idx]['dc'] === true) {
			$state = log_message($state,"Disable {$category} index $idx DC");
			drive_pwm($cfg, $cfg[$category][$idx]['pwm_channel'], $pwm);
			sleep(0.1);
			$dev->getLeds()[$cfg[$category][$idx]['dcpin']]->turnOff();
			$dev->getOutputPins()[$cfg[$category][$idx]['dcpin']]->turnOff();
			$state[$category][$idx]['dc'] = false;
		}
		if($state[$category][$idx]['pwm'] != $pwm)
			drive_pwm($cfg, $cfg[$category][$idx]['pwm_channel'], $pwm);
		$state[$category][$idx]['pwm'] = $pwm;
	}
	if(($pwm > 0)){
		if($state[$category][$idx]['dc'] === false) {
			$state = log_message($state,"Enable {$category} index $idx DC");
			drive_pwm($cfg, $cfg[$category][$idx]['pwm_channel'], $pwm);
			sleep(0.1);
			$dev->getLeds()[$cfg[$category][$idx]['dcpin']]->turnOn();
			$dev->getOutputPins()[$cfg[$category][$idx]['dcpin']]->turnOn();
			$state[$category][$idx]['dc'] = true;
		}
		if($state[$category][$idx]['pwm'] != $pwm)
			drive_pwm($cfg, $cfg[$category][$idx]['pwm_channel'], $pwm);
		$state[$category][$idx]['pwm'] = $pwm;
	}
	return($state);
}

function drive_inverters($state) {
	global $cfg;
	global $dev;
	$inverters = return_inverters_power($cfg);
	switch($state['operation']) {
		case 2:
		case 1:
		case 0:
			// Disable AC after standby, disable DC immediate
			foreach($cfg['inverters'] as $idx => $inverter) {
				$state = power_device_ac($cfg, $state, "inverters", $idx, 0);
				$state = power_device_dc($cfg, $state, "inverters", $idx, 0);
			}
			break;
		case -1:
			foreach($cfg['inverters'] as $idx => $inverter) {
				// Enable AC, keep disabled DC
				$state = power_device_ac($cfg, $state, "inverters", $idx, 1);
				$state = power_device_dc($cfg, $state, "inverters", $idx, 0);
			}
			break;
		case -2:
			// drive PWM
			$state['battery'] = "discharging";
			if($state['inverter_power'] > ($inverters['power_max'] * $state['inverter_throttle']))
				$state['inverter_power'] = ($inverters['power_max'] * $state['inverter_throttle']);
			if($state['inverter_power'] < ($inverters['power_min']))
				$state['inverter_power'] = $inverters['power_min'];

			/* Determine which to run */
			$enabled = array();
			$cpower = $state['inverter_power'];
			$c = 0;
			foreach($cfg['inverters'] as $idx => $inverter) {
				if($cpower >= 0) {
					$enabled[$idx] = true;
					$c++;
				} else {
					$enabled[$idx] = false;
				}
				$cpower = $cpower - (($inverter['power'] * $inverter['pwm_max'])/100);
			}
print_r($cpower);
			if($c == 1) {
				foreach($enabled as $idx => $value) {
					$pwm[$idx] = (($state['inverter_power']/$c)/$cfg['inverters'][$idx]['power']);
				}
			}
			if($c > 1) {
				foreach($enabled as $idx => $value) {
					$factor = (($state['inverter_power']/$c) / $cfg['inverters'][$idx]['power']);
					$pwm[$idx] = (($state['inverter_power']/$c) / $factor) / $cfg['inverters'][$idx]['power'];
				}
			}
			// Enable AC, Enable DC on demand
			foreach($cfg['inverters'] as $idx => $inverter) {
				$state = power_device_ac($cfg, $state, "inverters", $idx, 1);
				if($enabled[$idx] === true) {
					$state = power_device_dc($cfg, $state, "inverters", $idx, $pwm[$idx]);
				} else {
					$state = power_device_ac($cfg, $state, "inverters", $idx, 0);
					$state = power_device_dc($cfg, $state, "inverters", $idx, 0);
				}
			}
			break;
	}
	return($state);
}

function drive_pwm($cfg, $channel, $pwm) {
	global $state;
	if(($channel > 16) || ($channel < 0) || (!is_numeric($channel))) {
		$state = log_message($state,"We are passed an invalid channel '{$channel}'");
		return false;
	}
	if(($pwm > 1) || ($pwm < 0) || (!is_numeric($pwm))) {
		$state = log_message($state,"We are passed an invalid pwm value '{$pwm}'");
		return false;
	}

	$pwmstep = round($pwm * 4096);
	if($pwmstep == 4096)
		$pwmstep = 4095;

	$state = log_message($state,"Set pwm to $pwm, step $pwmstep channel $channel");
	exec("{$cfg['pwm_command']} {$channel} {$pwmstep}", $out, $ret);
	if($ret > 0)
		$state = log_message($state,"Failed to set pwm to $pwm channel $channel");
	if(count($out) > 0)
		$state = log_message($state,"We got unexpected results $out");
}

function shutdown() {
	global $dev;
	global $cfg;
	global $shm_state_id;
	global $state;
	$state = log_message($state,"Shutdown, move to state 0, disable charger(s) and inverter(s). Disconnect Battery. Reset IO.");
	$state['operation'] = 0;
	$state['charger_power'] = 0;
	$state['inverter_power'] = 0;
	$state['available_power'] = 0;
	$state['duration'][2] = 0;
	$state['duration'][-2] = 0;
	write_state_shm($shm_state_id, $state);
	foreach($cfg['inverters'] as $idx => $inverter) {
		$state = power_device_ac($cfg, $state, "inverters", $idx, 0);
		$state = power_device_dc($cfg, $state, "inverters", $idx, 0);
	}
	foreach($cfg['chargers'] as $idx => $charger) {
		$state = power_device_ac($cfg, $state, "chargers", $idx, 0);
		$state = power_device_dc($cfg, $state, "chargers", $idx, 0);
	}
	$state = drive_inverters($state);
	$state = drive_chargers($state);
	write_state_shm($shm_state_id, $state);
	sleep($cfg['timer_loop']);
	$state = maintenance_charger_disable($cfg, $battstate, $state);
	toggle_battery(false);
	// reset gpio
	$dev->init();
	write_state_shm($shm_state_id, $state);
	sleep($cfg['timer_loop']);
	write_state_shm($shm_state_id, $state);
	exit(0);
}

function log_message($state = array(), $message = "") {
	if(!empty($state) && ($state['message'] == $message))
		return $state;
	syslog(LOG_NOTICE, $message);
	$date = date("Y-m-d H:i:s");
	echo "{$date} {$message}\n";
	$state['message'] = "{$message}";
	$state['message_time'] = "{$date}";
	return $state;
}

function maintenance_charger_disable($cfg, $battstate, $state) {
	global $dev;
		$state = log_message($state,"Disable maintenance charger.");
		$state['maintenance'] = false;
		$dev->getLeds()[$cfg['maintenance_charger_acpin']]->turnOff();
		$dev->getOutputPins()[$cfg['maintenance_charger_acpin']]->turnOff();

	return $state;
}

function maintenance_charge($cfg, $battstate, $state) {
	global $dev;
	$state[$state['operation']] = time();
	if(($state['operation'] <> 0) && ($state['maintenance'] === true)) {
		$state = log_message($state,"Disable maintenance charger");
		$state['maintenance'] = false;
		$dev->getLeds()[$cfg['maintenance_charger_acpin']]->turnOff();
		$dev->getOutputPins()[$cfg['maintenance_charger_acpin']]->turnOff();
	}
	$cell_diff = round(($battstate['cell_max'] - $battstate['cell_min']), 3);
	if(($cell_diff > $cfg['maintenance_diff']) && ($state['maintenance'] === false) && ($state['operation'] == 0) && ($state['battery'] == "full")) {
		$state = log_message($state,"Enable maintenance charger,  cell difference '{$cell_diff}' exceeds '{$cfg['maintenance_diff']}' limit.");
		$state['maintenance'] = true;
		$dev->getLeds()[$cfg['maintenance_charger_acpin']]->turnOn();
		$dev->getOutputPins()[$cfg['maintenance_charger_acpin']]->turnOn();

	}
	if(($cell_diff < $cfg['batt_hysteresis']) && ($state['maintenance'] === true) && ($state['operation'] == 0)) {
		$state = log_message($state,"Disable maintenance charger,  cell difference '{$cell_diff}' within hysteresis '{$cfg['batt_hysteresis']}'.");
		$state['maintenance'] = false;
		$dev->getLeds()[$cfg['maintenance_charger_acpin']]->turnOff();
		$dev->getOutputPins()[$cfg['maintenance_charger_acpin']]->turnOff();

	}
	return $state;
}

function timeDiff($time, $opt = array()) {
    // The default values
    $defOptions = array(
        'to' => 0,
        'parts' => 1,
        'precision' => 'second',
        'distance' => TRUE,
        'separator' => ', '
    );
    $opt = array_merge($defOptions, $opt);
    // Default to current time if no to point is given
    (!$opt['to']) && ($opt['to'] = time());
    // Init an empty string
    $str = '';
    // To or From computation
    $diff = ($opt['to'] > $time) ? $opt['to']-$time : $time-$opt['to'];
    // An array of label => periods of seconds;
    $periods = array(
        'decade' => 315569260,
        'year' => 31556926,
        'month' => 2629744,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1
    );
    // Round to precision
    if ($opt['precision'] != 'second')
        $diff = round(($diff/$periods[$opt['precision']])) * $periods[$opt['precision']];
    // Report the value is 'less than 1 ' precision period away
    (0 == $diff) && ($str = 'less than 1 '.$opt['precision']);
    // Loop over each period
    foreach ($periods as $label => $value) {
        // Stitch together the time difference string
        (($x=floor($diff/$value))&&$opt['parts']--) && $str.=($str?$opt['separator']:'').($x.' '.$label.($x>1?'s':''));
        // Stop processing if no more parts are going to be reported.
        if ($opt['parts'] == 0 || $label == $opt['precision']) break;
        // Get ready for the next pass
        $diff -= $x*$value;
    }
    $opt['distance'] && $str.=($str&&$opt['to']>$time)?' ago':' away';
    return $str;
}

?>
