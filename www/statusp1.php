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
$p1_array = read_p1_shm($shm_p1_key, $seg_size);

echo "<table id='statusp1' border=0 width=300>\n";
echo "<tr><td align=right width=100>";

if($p1_array['power_gen_cur'] > 0) {
	echo "<img src=images/p1_generation2.png height=30>";
} else {
	echo " ";
}
echo "</td><td align=center width=100><center> ";
echo ($p1_array['power_cons_cur'] + $p1_array['power_gen_cur']);
echo " Watt </td><td align=left width=100>";

if($p1_array['power_cons_cur'] > 0) {
	echo "<img src=images/p1_consumption2.png height=30>";
} else {
	echo "&nbsp; ";
}
echo "</td></tr>\n";

echo "<tr><td align=center colspan=3>";
echo "<img src=images/p1.jpg width=150>";
echo "</td></tr>";

echo "<tr><td align=center colspan=3 >";
echo "<table border=0 width=300>\n";
if((time() - $p1_array['time']) > 30)
	echo "<tr><td colspan=2 >Last Reading</td><td bgcolor=coral>". date("H:i:s", $p1_array['time']) ."</td></tr>\n";
else
	echo "<tr><td colspan=2 >Last Reading</td><td bgcolor=lightgreen>". date("H:i:s", $p1_array['time']) ."</td></tr>\n";

echo "<tr><td colspan=2>Consumption Tarif 1</td><td>{$p1_array['energy_cons_1']} kWh</td></tr>\n";
echo "<tr><td colspan=2>Consumption Tarif 2</td><td>{$p1_array['energy_cons_2']} kWh</td></tr>\n";
echo "<tr><td colspan=2>Generation Tarif 1</td><td>{$p1_array['energy_gen_1']} kWh</td></tr>\n";
echo "<tr><td colspan=2>Generation Tarif 2</td><td>{$p1_array['energy_gen_2']} kWh</td></tr>\n";
echo "<tr><td colspan=2>Consumption Gas</td><td>{$p1_array['gas_cons']} m&sup3;</td></tr>\n";
echo "</table>";
echo "</td></tr>";

echo "<!-- ";
echo "The data inside P1 shared memory is: " . print_r($p1_array, true) . "\n";
echo " -->";

echo "</table>";

?>
