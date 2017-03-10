#!/usr/bin/python

from ABE_ADCPi import ADCPi
from ABE_helpers import ABEHelpers
import time
import os

"""
================================================
ABElectronics ADC Pi 8-Channel ADC demo
Version 1.0 Created 09/05/2014
Version 1.1 16/11/2014 updated code and functions to PEP8 format

Requires python smbus to be installed
run with: python demo-read_voltage.py
================================================


Initialise the ADC device using the default addresses and sample rate,
change this value if you have changed the address selection jumpers

Sample rate can be 12,14, 16 or 18
"""


i2c_helper = ABEHelpers()
bus = i2c_helper.get_smbus()
adc = ADCPi(bus, 0x68, 0x69, 16)

# clear the console
# os.system('clear')

# read from adc channels and print to screen
print ("1:%02f" % adc.read_voltage(1))
print ("2:%02f" % adc.read_voltage(2))
print ("3:%02f" % adc.read_voltage(3))
print ("4:%02f" % adc.read_voltage(4))
print ("5:%02f" % adc.read_voltage(5))
print ("6:%02f" % adc.read_voltage(6))
print ("7:%02f" % adc.read_voltage(7))
print ("8:%02f" % adc.read_voltage(8))

