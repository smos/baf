#!/usr/bin/python

import argparse
from ABE_ServoPi import PWM
import time
from ABE_helpers import ABEHelpers

"""
run with: python drive-pwm.py -c N -p N
"""

parser = argparse.ArgumentParser(description='Drive channel N at pwm 0-4096')
parser.add_argument('channel', type=int)
parser.add_argument('pwm', type=int)
args = parser.parse_args()

# create an instance of the ABEHelpers class and use it 
# to find the correct i2c bus
i2c_helper = ABEHelpers()
bus = i2c_helper.get_smbus()

# create an instance of the PWM class on i2c address 0x40
pwm = PWM(bus, 0x40)

# Set PWM frequency to 1 Khz and enable the output
pwm.set_pwm_freq(1000)
pwm.output_enable()

pwm.set_pwm(args.channel, 0, args.pwm)
