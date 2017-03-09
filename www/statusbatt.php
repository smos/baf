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

$shm_batt_id = open_shm($shm_batt_key, $seg_size, "a");
$batt_array = unserialize(rtrim(shmop_read($shm_batt_id, 0, $seg_size), "\0"));

echo "<table id='statusbatt' width=500>";
echo "<tr><td align=center colspan=3 width=300>Battery ". sprintf("%1.2f", $batt_array['total']) ." Volt</td><td width=180>&nbsp;</td></tr>\n";
echo "<tr><td align=center colspan=3 >";
echo "<table border=0 cellspacing=2 cellpadding=0><tr>\n";
echo "<td width=40><center><font size='3'>{$batt_array['level']}%</td><td bgcolor=white width=10>&nbsp;</td>";
foreach($batt_array['cells'] as $cell) {
	echo "<td width=40 bgcolor='white' ><center><font size='2'>". sprintf("%1.2f", $cell) ."</td>";
}
echo "</tr><tr>";
echo "<td height=100 valign=bottom style='border: 1px solid black;'> <table><tr><td bgcolor=lightgreen height={$batt_array['level']} width=50></td></table> </td>";
echo "<td height=100 valign=bottom ><table><tr><td width=10></td></table> </td>";
foreach($batt_array['cells'] as $cell) {
	$bgcolor = "lightgreen";
	if($cell == $batt_array['cell_max'])
		$bgcolor = "lightsteelblue";
	if($cell == $batt_array['cell_min'])
		$bgcolor = "khaki";
	$height = round(($cell-$cfg['batt_volt_crit_min']) / (($cfg['batt_volt_crit_max']-$cfg['batt_volt_crit_min']) / 100));
	echo "<td width=40 height=100 valign=bottom style='border: 1px solid black;'><table><tr><td bgcolor={$bgcolor} height={$height} width=50></td></tr></table> </td>";
}
echo "</tr></table>";
echo "</td><td>&nbsp;</td></tr>";
/* 
echo "<tr><td><pre>";
echo "The data inside shared memory is: \n" . print_r($batt_array, true) . "\n";
echo "</pre></td></tr>";
*/

echo "</table>";

?>
