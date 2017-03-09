# baf
Back and Forth ESS
Seth Mos <seth.mos@dds.nl>

To steer energy demand for higher self use of Solar energy using a battery, a charger and a inverter.

Instructions are not entirely complete yet, but it's a start. Basic Raspberry module things.

Currently
- Supports more then 1 inverter
- Supports different size inverters
- Operates remotely from the P1 reader using the arduino nano P1 reader.
- WebUI to show current operations and P1 meter readings
- Has timers to prevent flip-flopping of relays
- Allows for diverse battery configurations, but currently limited to 8S Lithium

Instructions
- Requires a Arduino Nano with Ethernetshield to connect to the P1 port of the utility smart meter. If you use something else for your power readings, that's fine, it just needs to post the data to a page of the webserver on the Raspberry. The arduino code is under arduino/ and you need to modify the IP address it POSTs too. It's currently set to 192.168.11.238.

- It's being developed on a original RPI B with 512MB ram. Everything lives in the pi user home directory under ~/baf/
If you are the pi user you can just "git clone https://github.com/smos/baf.git"
For the webpages to be reachable you need to make a link to the www directory in the webroot. "sudo ln -s /home/pi/baf/www /var/www/html/baf"

- The ADC board is from ABElectronics UK. https://www.abelectronics.co.uk/ and uses i2c. The DAC board is also from AB Electronics and also uses i2c but on a different address. Luckily they have a nice library as well.
Check out the sources from github using "cd ~/baf/;git clone https://github.com/abelectronicsuk/ABElectronics_Python_Libraries.git" 
"sudo apt-get install python-smbus"
"sudo adduser pi i2c"
"sudo modprobe i2c-dev"
"sudo modprobe i2c-bcm2708"
Remove the modules from the module blacklist and add them to /etc/modules
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
When succesful, move the vendor directory over the main directroy. "mv ~/myphppiface/vendor ~/baf"

- Run the controller
"su -l pi -c "screen -d -m -S essloop php ~/baf/closedloop.php"
Add the command to /etc/rc.local too so that it starts on boot.

Other
I built a PHP script to sort 18650 batteries from left to right to get mostly equal sized Ah cells for the given configuration
http://iserv.nl/files/pics/ess/cellsort.php

Here is a screenshot of the WebUI status
http://iserv.nl/files/pics/ess/baf3000.png
