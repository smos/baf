# baf
Back and Forth ESS
Seth Mos <seth.mos@dds.nl>

To steer energy demand for higher self use of Solar energy using a battery, a charger and a inverter.

Instructions are not entirely complete yet, but it's a start. Basic Raspberry module things.

Features
- Supports more then 1 Inverter/Charger.
- Supports different size inverters/Chargers.
- Operates remotely from the P1 reader using the arduino nano P1 reader.
- WebUI to show current operations and P1 meter readings.
- Has idle timers to prevent flip-flopping of AC relays (inverters/chargers).
- Allows for diverse battery configurations, adjust voltages accordingly.
- Charge and Discharge power taper at end of range. (adjustable)
- Auxilary charger for balancing cells and emergency low-level charging (adjustable voltage diff trigger).

Bill of Materials (euro)
- Raspberry Pi (~50)
- ADCPi from ABElectronics Uk (~15)
- ServoPi from ABElectronics Uk (~15)
- PiFace Digital from PiFace UK (~25)
- (2) PWM controller that takes 0-5V input like the following. (~30) https://www.aliexpress.com/item/DC-10-50V-PWM-DC-Motor-Speed-Controller-3000w-Max-12V-24V-36V-40V-50V-60A/1830078283.html
- (2) 5 Volt 4 Channel Arduino Relay board with optocoupler. (~7) https://www.aliexpress.com/item/Free-shipping-4-channel-relay-module-4-channel-relay-control-board-with-optocoupler-Relay-Output-4/32702042620.html
- Fuse holder (3D Printed). http://www.thingiverse.com/thing:1787609
- 1.5/2.5mm2 wiring for DC
- 1m 0.75mm2 wiring for AC with ground
- 4 AC Outlets (~8)
- (2) 5S balance leads for the relay boards
- (4) 8S Balance leads for the battery monitoring
- DIY PCB, 8 100K resistors, flatcable with single connectors

Instructions
- Requires a Arduino Nano with Ethernetshield to connect to the P1 port of the utility smart meter. If you use something else for your power readings, that's fine, it just needs to post the data to a page of the webserver on the Raspberry. The arduino code is under arduino/ and you need to modify the IP address it POSTs too. It's currently set to 192.168.11.238.

- It's being developed on a original RPI B with 512MB ram. Everything lives in the pi user home directory under ~/baf/
If you are the pi user you can just "git clone https://github.com/smos/baf.git"
For the webpages to be reachable you need to make a link to the www directory in the webroot. "sudo ln -s /home/pi/baf/www /var/www/html/baf"

- The ADC board is from ABElectronics UK. https://www.abelectronics.co.uk/ and uses i2c. The DAC board is also from AB Electronics and also uses i2c but on a different address. Luckily they have a nice library as well.
Check out the sources from github using "cd ~/baf/;git clone https://github.com/abelectronicsuk/ABElectronics_Python_Libraries.git" 
"sudo apt-get install python-smbus php5-dev screen"
"sudo adduser pi i2c"
"sudo modprobe i2c-dev"
"sudo modprobe i2c-bcm2708"
Remove the modules from the module blacklist and add them to /etc/modules, alternatively you can use raspi-config to enable these.
"sudo nano /etc/modprobe.d/raspi-blacklist.conf"
After reboot they should show up
"sudo i2cdetect -y 0"
"sudo i2cdetect -y 1"
Add the following path to the .profile of the pi user.
"export PYTHONPATH=${PYTHONPATH}:~/baf/ABElectronics_Python_Libraries/ADCPi/"

- PHP SPI class "cd ~/baf/;git clone git://github.com/frak/php_spi.git ~/baf/php_spi"
"cd ~/baf/php_spi/"
"phpize"
"./configure --enable-spi"
"make test"
"make install"
"sudo make install"

- It uses PiFace for 8 relay outputs using the PHP PiFace toolkit from https://github.com/peec/raspberry-piface-api
Follow the instruction there and install under the ~/baf/ directory. The previous php_spi needs to work though.
Make sure that you build the vendor directory and composer.json in the ~/baf/ direcory. In the future I want to move this to the AB IO board as it's better suited for the purpose.

- Add users to the groups. Add pi to the www-data group and www-data to the pi group, you can "sudo nano /etc/group"

- Run the controller
"su -l pi -c "screen -d -m -S essloop php ~/baf/closedloop.php"
Add the command to /etc/rc.local too so that it starts on boot, it logs to syslog.


Other
- I built a PHP script to sort 18650 batteries from left to right to get mostly equal sized Ah cells for the given configuration
http://iserv.nl/files/pics/ess/cellsort.php
- Here is a screenshot of the WebUI status http://iserv.nl/files/pics/ess/baf3000.png
- The Voltage divider is a bit finicky, as it depends on the battery Voltage you want to measure and it's easiest to use the Divider calculator on the website from ABE.
- Here is what the ESS looks like with some of it screwed to a board. http://iserv.nl/files/pics/raspberry/20170313_223826_th.jpg
- I designed some 18650 battery holders and put them on. http://www.thingiverse.com/thing:2169732
- I designed a generic PCB mount for the relay boards. http://www.thingiverse.com/thing:2169739


Log example of a battery draining (with a lightbulb) and stopping:
Apr 25 22:33:16 bramenstruik php: Set pwm to 0.13, step 532 channel 0
Apr 25 22:33:18 bramenstruik php: Set pwm to 0.11, step 451 channel 0
Apr 25 22:33:20 bramenstruik php: Set pwm to 0.16, step 655 channel 0
Apr 25 22:33:22 bramenstruik php: Set pwm to 0.1, step 410 channel 0
Apr 25 22:35:37 bramenstruik php: No generation, battery empty, idle
Apr 25 22:35:37 bramenstruik php: Disable inverters index 1 DC
Apr 25 22:35:37 bramenstruik php: Set pwm to 0, step 0 channel 0
Apr 25 22:35:38 bramenstruik php: Disable battery relay
Apr 25 22:35:40 bramenstruik php: Battery cell voltage below minimum 3.45 but above critical 3.2, continue

