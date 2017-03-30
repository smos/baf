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

echo "<table id='statusstate' border=0 width=300>";
echo "<tr><td align=right width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/p1_generation2.png title='Generation' height=30>";
		break;
	case -2:
		echo "<img src=images/p1_consumption2.png title='Consumption' height=30>";
		break;
	case -1:
	case 0:
	case 1:
		if($state['available_power'] > 0)
			echo "<img src=images/grid_generation.png title='Generation' height=30>&nbsp;";
		if($state['available_power'] < 0)
			echo "<img src=images/grid_consumption.png title='Consumption' height=30>&nbsp;";
		if($state['available_power'] == 0)
			echo "<img src=images/grid_idle.png title='No generation, no consumption' height=30>&nbsp;";

		if($state['available_power'] > 0)
			echo "<img src=images/p1_generation2.png title='Generation' height=30>";
		if($state['available_power'] < 0)
			echo "<img src=images/p1_consumption2.png title='Consumption' height=30>";
		if($state['available_power'] == 0)
			echo "<img src=images/batt_idle.png title='No generation, no consumption' height=30>";
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
		echo "<img src=images/p1_generation2.png title='Generation' height=30>";
		break;
	case -2:
		echo "<img src=images/p1_consumption2.png title='Consumption' height=30>";
		break;
	case -1:
	case 0:
	case 1:
		if($state['available_power'] > 0)
			echo "<img src=images/p1_generation2.png title='Generation standby' height=30>";
		if($state['available_power'] < 0)
			echo "<img src=images/p1_consumption2.png title='Consumption standby' height=30>";
		if($state['available_power'] == 0)
			echo "<img src=images/batt_idle.png title='No consumption, no generation' height=30>";

		if($state['available_power'] > 0)
			echo "&nbsp;<img src=images/house_generation.png title='Generation' height=30>";
		if($state['available_power'] < 0)
			echo "&nbsp;<img src=images/house_consumption.png title='Consumption' height=30>";
		if($state['available_power'] == 0)
			echo "&nbsp;<img src=images/house_idle.png title='No consumption, no generation' height=30>";

		break;
}
/*
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
*/
echo "</td></tr>\n";

echo "<tr><td align=right width=100 height=30 valign=top>&nbsp;";
echo "</td><td align=center width=100 valign=top>";
switch($state['operation']) {
	case 2:
		echo "<img src=images/batt_charge.png title='Charging' height=30>";
		break;
	case -2:
		echo "<img src=images/batt_invert.png title='Inverting' height=30>";
		break;
	case -1:
	case 1:
	case 0:
		echo "<img src=images/batt_idle.png title='Idle' height=30>";
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
		echo "<img src='images/power_off.png' title='Inverter AC disconnected' width=40>";
	if(($array['ac'] === true) && ($state['operation'] == -2))
		echo "<img src='images/power_on.png' title='Inverter AC connected' width=40>";
	elseif(($array['ac'] === true) && ($state['operation'] != AQ-2))
		echo "<img src='images/power_idle.png' title='Inverter AC idle, timer' width=40>";
	echo "</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center><font size=2>";
	echo 0 + ($array['pwm'] * $cfg['inverters'][$idx]['power']);
	echo "W</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center>";
	echo "<img src='images/inverter.png' title='Inverter'  width=40>";
	echo "</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['inverters'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['dc'] === false))
		echo "<img src='images/power_off.png' title='Inverter DC disconnected'  width=40>";
	if(($array['dc'] === true) && ($state['operation'] == -2))
		if(($array['pwm'] > 0) && ($array['pwm'] < 1))
			echo "<img src='images/limiter_on.png' title='Inverter DC power limited' width=40>";
		else
			echo "<img src='images/power_on.png' title='Inverter DC Connected' width=40>";
	elseif(($array['dc'] === true) && ($state['operation'] != -2))
		echo "<img src='images/power_idle.png' title='Inverter DC idle'  width=40>";
	echo "</td>";
}
echo "</tr>\n";

echo "</table>\n";

echo "</td><td width=150 align=left>";
echo "<table border=0>";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['ac'] === false))
		echo "<img src='images/power_off.png' title='Charger AC disconnected' width=40>";
	if(($array['ac'] === true) && ($state['operation'] == 2))
		echo "<img src='images/power_on.png' title='Charger AC connected' width=40>";
	elseif(($array['ac'] === true) && ($state['operation'] != 2))
		echo "<img src='images/power_idle.png' title='Charger AC idle, timer'  width=40>";
	echo "</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center><font size=2>";
	echo 0 + ($array['pwm'] * $cfg['chargers'][$idx]['power']);
	echo "W</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	echo "<img src='images/charger.png' title='Charger' width=40>";
	echo "</td>";
}
echo "</tr>\n";
echo "<tr>";
foreach($state['chargers'] as $idx => $array) {
	echo "<td align=center>";
	if(($array['dc'] === false))
		echo "<img src='images/power_off.png' title='Charger DC disconnected' width=40>";
	if(($array['dc'] === true) && ($state['operation'] == 2))
		if(($array['pwm'] > 0) && ($array['pwm'] < 1))
			echo "<img src='images/limiter_on.png' title='Charger DC power limited' width=40>";
		else
			echo "<img src='images/power_on.png' title='Charger DC connected'  width=40>";
	elseif(($array['dc'] === true) && ($state['operation'] != 2))
		echo "<img src='images/power_idle.png' title='Charger DC idle' width=40>";
	echo "</td>";
}
echo "</tr>\n";

echo "</td></tr>\n";
echo "</table>\n";
echo "</td></tr>\n";

echo "<tr><td colspan=2 align=center>";
if($state['battery_connect'] === true)
	echo UcWords($state['battery']) ."<br><img src='images/battery_ok.png' title='Battery Connected' width=50>&nbsp;";
if($state['battery_connect'] === false)
	echo "{$state['battery']}<br><img src='images/battery_nok.png' title='Battery Not Connected' width=50>&nbsp;";
if($state['maintenance'] === true)
	echo "&nbsp;<img valign=top src='images/maintenance.png' title='Maintenance Charging enabled' width=50>";
if(($state['charger_throttle'] < 1) && ($state['charger_throttle'] > 0) && ($state['battery'] == "charging"))
	echo "&nbsp;<img valign=top src='images/blimiter.png' title='Battery almost full, limiting' width=50>";
if(($state['inverter_throttle'] < 1) && ($state['inverter_throttle'] > 0) && ($state['battery'] == "discharging"))
	echo "&nbsp;<img valign=top src='images/blimiter.png' title='Battery almost empty, limiting' width=50>";
echo "</td></tr>\n";

if((time() - $state['time']) > 10)
	echo "<tr><td >Timeout</td><td bgcolor=coral>". date("H:i:s", $state['time']) ."</td></tr>\n";
else
	echo "<tr><td >Running</td><td bgcolor=lightgreen>". date("H:i:s", $state['time']) ."</td></tr>\n";
echo "<tr><td ><font size=2>Uptime</td><td><font size=2>". timeDiff($state['bootup'], array('to' => 0, 'parts' => 2, 'precision' => 'minute', 'distance' => false, 'seperator' => ', ')) ."</font></td></tr>\n";

echo "<tr><td colspan=2 ><font size=2>{$state['message_time']} | {$state['message']}</font></td></tr>\n";

echo "</table>";

echo "<!-- ";
echo "The status array contains:\n" . print_r($state, true) . "\n";
echo "-->";


?>
