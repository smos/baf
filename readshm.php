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

include("vars.php");
include("functions.php");


$p1_array = read_p1_shm($shm_p1_key, $seg_size);
echo "The data inside P1 shared memory is: " . print_r($p1_array, true) . "\n";

$shm_raw_id = open_shm($shm_raw_key, $seg_size, "a");
$my_string = rtrim(shmop_read($shm_raw_id, 0, $seg_size), "\0");
if (!$my_string) {
    echo "Couldn't read from shared memory block\n";
}
echo "The data inside shared memory $shm_raw_id was: \n" . $my_string . "\n";

$shm_state_id = open_shm($shm_state_key, $seg_size, "a");
$my_string = rtrim(shmop_read($shm_state_id, 0, $seg_size), "\0");
if (!$my_string) {
    echo "Couldn't read from shared memory block\n";
}
echo "The data inside shared memory $shm_state_id was: \n" . $my_string . "\n";

// close_shm ($shm_id)

?>
