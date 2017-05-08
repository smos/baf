<?php

use Pkj\Raspberry\PiFace\PiFaceDigital;
require 'vendor/autoload.php';

$dev = PiFaceDigital::create();
// Run once.
$dev->init();

// sleep(2);
// $dev->getLeds()[5]->turnOn();
// $dev->getLeds()[5]->turnOff();


// $dev->getInputPins();
// $dev->getOutputPins();
// $dev->getLeds();
// $dev->getRelays();
// $dev->getSwitches();


// Turn on relay 0
// $dev->getRelays()[0]->turnOn();
// sleep(1);
// $dev->getRelays()[0]->turnOn();

// Get 0/1 of input pin 3 (There are 8 pins, 0-7)
// $dev->getInputPins()[3]->getValue();

// Toggle a value on a output pin (5 in this example)
$dev->getOutputPins()[4]->toggle(); // 0
$dev->getLeds()[4]->turnOff();
sleep(1);
$dev->getOutputPins()[4]->toggle(); // 1
$dev->getLeds()[4]->turnOn();
sleep(1);
$dev->getOutputPins()[4]->toggle(); // 0
$dev->getLeds()[4]->turnOff();
