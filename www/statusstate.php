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
$shm_state_id = open_shm($shm_state_key, $seg_size, "a");
$state = unserialize(rtrim(shmop_read($shm_state_id, 0, $seg_size), "\0"));

echo "<table id='statusstate' border=0 width=500>";
echo "<tr><td align=right width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/p1_generation2.png height=30>";
		break;
	case -2:
		echo "<img src=images/p1_consumption2.png height=30>";
		break;
	case -1:
	case 0:
	case 1:
		if($state['available_power'] > 0)
			echo "<img src=images/p1_generation2.png height=30>";
		if($state['available_power'] < 0)
			echo "<img src=images/p1_consumption2.png height=30>";
		if($state['available_power'] == 0)
			echo "<img src=images/batt_idle.png height=30>";
		break;
}

echo "</td><td align=center width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/t-generation.png height=30>";
		break;
	case -2:
		echo "<img src=images/t-consumption.png height=30>";
		break;
	case -1:
	case 1:
		echo "<img src=images/t-standby.png height=30>";
		break;
	case 0:
		echo "<img src=images/t-idle.png height=30>";
		break;
}
echo "</td><td align=left width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/p1_generation2.png height=30>";
		break;
	case -2:
		echo "<img src=images/p1_consumption2.png height=30>";
		break;
	case -1:
	case 0:
	case 1:
		if($state['available_power'] > 0)
			echo "<img src=images/p1_generation2.png height=30>";
		if($state['available_power'] < 0)
			echo "<img src=images/p1_consumption2.png height=30>";
		if($state['available_power'] == 0)
			echo "<img src=images/batt_idle.png height=30>";
		break;
}
echo "</td><td align=center width=200 valign=top>";
switch($state['operation']) {
	case -2:
	case -1:
	case 0:
	case 1:
	case 2:
		if($state['available_power'] > 0)
			echo "<img src=images/house_generation.png height=30>";
		if($state['available_power'] < 0)
			echo "<img src=images/house_consumption.png height=30>";
		if($state['available_power'] == 0)
			echo "<img src=images/house_idle.png height=30>";
		break;
}
echo "</td></tr>\n";

echo "<tr><td align=right width=100 height=30 valign=top>&nbsp;";
echo "</td><td align=center width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/batt_charge.png height=30>";
		break;
	case -2:
		echo "<img src=images/batt_invert.png height=30>";
		break;
	case -1:
	case 1:
	case 0:
		echo "<img src=images/batt_idle.png height=30>";
		break;
}
echo "</td><td align=left width=100 valign=top>&nbsp;";
echo "</td></tr>\n";

echo "<tr><td colspan=3 valign=top>";
echo "<table border=0 width=300>";
echo "<tr><td width=150 align=right valign=top>";
echo "<table border=0>";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['ac'] === false))
		echo "<img src='images/power_off.png' width=40>";
	if(($array['ac'] === true) && ($state['operation'] == -2))
		echo "<img src='images/power_on.png' width=40>";
	elseif(($array['ac'] === true) && ($state['operation'] != -2))
		echo "<img src='images/power_idle.png' width=40>";
	echo "</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center><font size=2>";
	echo 0 + ($array['pwm'] * $cfg['inverters'][$idx]['power']);
	echo "W</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center>";
	echo "<img src='images/inverter.png' width=40>";
	echo "</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['dc'] === false))
		echo "<img src='images/power_off.png' width=40>";
	if(($array['dc'] === true) && ($state['operation'] == -2))
		if(($array['pwm'] > 0) && ($array['pwm'] < 1))
			echo "<img src='images/limiter_on.png' width=40>";
		else
			echo "<img src='images/power_on.png' width=40>";
	elseif(($array['dc'] === true) && ($state['operation'] != -2))
		echo "<img src='images/power_idle.png' width=40>";
	echo "</td>";
}
echo "</tr>";

echo "</table>";

echo "</td><td width=150 align=left>";
echo "<table border=0>";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['ac'] === false))
		echo "<img src='images/power_off.png' width=40>";
	if(($array['ac'] === true) && ($state['operation'] == 2))
		echo "<img src='images/power_on.png' width=40>";
	elseif(($array['ac'] === true) && ($state['operation'] != 2))
		echo "<img src='images/power_idle.png' width=40>";
	echo "</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center><font size=2>";
	echo 0 + ($array['pwm'] * $cfg['chargers'][$idx]['power']);
	echo "W</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	echo "<img src='images/charger.png' width=40>";
	echo "</td>";
}
echo "</tr>";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['dc'] === false))
		echo "<img src='images/power_off.png' width=40>";
	if(($array['dc'] === true) && ($state['operation'] == 2))
		if(($array['pwm'] > 0) && ($array['pwm'] < 1))
			echo "<img src='images/limiter_on.png' width=40>";
		else
			echo "<img src='images/power_on.png' width=40>";
	elseif(($array['dc'] === true) && ($state['operation'] != 2))
		echo "<img src='images/power_idle.png' width=40>";
	echo "</td>";
}
echo "</tr>";

echo "</td></tr>";
echo "</table>";
echo "</td></tr>";

echo "<tr><td colspan=3 align=center>";
if($state['battery_connect'] === true)
	echo UcWords($state['battery']) ."<br><img src='images/battery_ok.png' width=50>";
if($state['battery_connect'] === false)
	echo "{$state['battery']}<br><img src='images/battery_nok.png' width=50>";
if(($state['charger_throttle'] < 1) && ($state['charger_throttle'] > 0) && ($state['battery'] == "charging"))
	echo "<img src='images/limiter.png' width=30>";
if(($state['inverter_throttle'] < 1) && ($state['inverter_throttle'] > 0) && ($state['battery'] == "discharging"))
	echo "<img src='images/limiter.png' width=30>";
echo "</td></tr>";

/*
echo "<tr><td colspan=3><pre>";
echo "The data inside shared memory is: \n" . print_r($state, true) . "\n";
echo "</pre></td></tr>";
*/
echo "</table>";

?>
