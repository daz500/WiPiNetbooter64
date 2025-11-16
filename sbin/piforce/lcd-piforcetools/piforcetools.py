#!/usr/bin/python
# Written by TravistyOJ (AKA Capaneus)

import os, collections, signal, sys, subprocess, socket
import csv
from Adafruit_CharLCDPlate import Adafruit_CharLCDPlate
from time import sleep

zerofile = open('/sbin/piforce/zeromode.txt')
zeromode = file.readline(zerofile)
zerofile.close
relayfile = open('/sbin/piforce/relaymode.txt')
relaymode = file.readline(relayfile)
relayfile.close
openfile = open('/sbin/piforce/openmode.txt')
openmode = file.readline(openfile)
openfile.close
colourfile = open('/sbin/piforce/lcdcolour.txt')
colourmode = file.readline(openfile)
colourfile.close

ips = [] # Array for dimm IPs that are read in from dimms.csv
rom_dir = "/boot/roms/"  # Set absolute path of rom files ending with trailing /
commands = ["Ping Netdimm", "Change Target"]

# Define a signal handler to turn off LCD before shutting down
def handler(signum = None, frame = None):
    lcd = Adafruit_CharLCDPlate()
    lcd.clear()
    lcd.stop()
    sys.exit(0)
signal.signal(signal.SIGTERM , handler)

# Determine hardware revision and initialize LCD
revision = "unknown"
cpuinfo = open("/proc/cpuinfo", "r")
for line in cpuinfo:
    item = line.split(':', 1)
    if item[0].strip() == "Revision":
        revision = item[1].strip()
if revision.startswith('a'):
    lcd = Adafruit_CharLCDPlate(busnum = 1)
else:
    lcd = Adafruit_CharLCDPlate()
lcd.begin(16, 2)
# SET YOUR DESIRED POWER ON LCD COLOR HERE.  'lcd.COLORNAME' where COLORNAME = RED, YELLOW, GREEN, TEAL, BLUE, VIOLET

if (colourmode != "nocolour"):
    if (colourmode == "blue"):
        lcd.backlight(lcd.BLUE)
    if (colourmode == "red"):
        lcd.backlight(lcd.RED)
    if (colourmode == "yellow"):
        lcd.backlight(lcd.YELLOW)
    if (colourmode == "green"):
        lcd.backlight(lcd.GREEN)
    if (colourmode == "teal"):
        lcd.backlight(lcd.TEAL)
    if (colourmode == "violet"):
        lcd.backlight(lcd.VIOLET)

lcd.message("     WiPi\n   Netbooter")
sleep(2)

# Try to import game list script, if it fails, signal error on LCD
#try:
#    from gamelist import games
#except (SyntaxError, ImportError) as e:
#    lcd.clear()
#    lcd.message("Game List Error!\n  Check Syntax")
#    sleep(5)
#    games = {}

with open('/var/www/html/csv/romsinfo.csv', mode='r') as file:
    reader = csv.reader(file)
    games = {rows[5]:(rows[1],rows[12]) for rows in reader}

with open('/var/www/html/csv/dimms.csv', 'r') as f:
    csv_reader = csv.reader(f, delimiter=',')
    next(csv_reader, None)
    for row in csv_reader:
        ips.append(row[1])

games = {k: v for k, v in games.iteritems() if v[1] != 'No'}

# Purge game dictionary of game files that can't be found
missing_games = []
for key, value in games.iteritems():
    if not os.path.isfile(rom_dir+value[0]):
        missing_games.append(key)
for missing_game in missing_games:
    del games[missing_game]

pressedButtons = []
curr_ip = 0
lcd.clear()
if len(games) is 0:
    lcd.message("NO GAMES FOUND!")
    sleep(1)
    iterator  = iter(commands)
    selection = iterator.next()
    mode = "commands"
    lcd.clear()
    lcd.message(selection)
else:
    iterator  = iter(collections.OrderedDict(sorted(games.items(), key=lambda t: t[0])))
    selection = iterator.next()
    lcdtext = selection.replace('\\n','\n')
    mode = "games"
    lcd.message(lcdtext)

while True:

    # Handle SELECT
    if lcd.buttonPressed(lcd.SELECT):
        if lcd.SELECT not in pressedButtons:
            pressedButtons.append(lcd.SELECT)
            if selection is "Change Target":
                curr_ip += 1
                if curr_ip >= len(ips):
                    curr_ip = 0
                lcd.message("\n"+ips[curr_ip])
            elif selection is "Ping Netdimm":
                lcd.clear()
                lcd.message("Pinging\n"+ips[curr_ip])
                response = os.system("ping -c 1 "+ips[curr_ip])
                lcd.clear()
                if response == 0:
                    lcd.message("SUCCESS!")
                else:
                    lcd.message("Netdimm is\nunreachable!")
                sleep(2)
                lcd.clear()
                lcd.message(selection)
            else:
                lcd.clear()                
                lcd.message("Connecting...")
                sleep(1)
                gamebin = games[selection][0]
                lcd.clear()
                lcd.message("Sending Game...")
                if (zeromode == "hackon"):
                    cmd = 'sudo python3 /sbin/piforce/wipiloader.py '+gamebin+' '+ips[curr_ip]+' '+relaymode+' '+zeromode+' '+openmode+' &'
                    os.system(cmd)
                    sleep(1)
                    lcd.clear()
                    lcd.message("Time Hack\nEnabled")
                    sleep(1)
                    lcd.clear()
                    lcd.message("Game Transfer\nStarted ...")
                    sleep(3)
                    lcd.clear()
                else:
                    cmd = 'sudo python3 /sbin/piforce/wipiloader.py '+gamebin+' '+ips[curr_ip]+' '+relaymode+' '+zeromode+' '+openmode
                    os.system(cmd)
                    lcd.clear()
                    lcd.message("Transfer\nComplete!")
                    sleep(2)
                    lcd.clear()
                lcd.message(lcdtext)

    elif lcd.SELECT in pressedButtons:
        pressedButtons.remove(lcd.SELECT)

    # Handle LEFT
    if lcd.buttonPressed(lcd.LEFT):
        if lcd.LEFT not in pressedButtons and len(games) > 0:
            pressedButtons.append(lcd.LEFT)
            mode      = "games"
            iterator  = iter(collections.OrderedDict(sorted(games.items(), key=lambda t: t[0])))
            selection = iterator.next()
            lcdtext = selection.replace('\\n','\n')
            previous  = None
            lcd.clear()
            lcd.message("Games")
            sleep(1)
            lcd.clear()
            lcd.message(lcdtext)            
    elif lcd.LEFT in pressedButtons:
        pressedButtons.remove(lcd.LEFT)

    # Handle RIGHT
    if lcd.buttonPressed(lcd.RIGHT):
        if lcd.RIGHT not in pressedButtons:
            pressedButtons.append(lcd.RIGHT)
            mode      = "commands"
            iterator  = iter(commands)
            selection = iterator.next()
            previous  = None
            lcd.clear()
            lcd.message("Commands")
            sleep(1)
            lcd.clear()
            lcd.message(selection)
    elif lcd.RIGHT in pressedButtons:
        pressedButtons.remove(lcd.RIGHT)

    # Handle UP
    if lcd.buttonPressed(lcd.UP):
        if lcd.UP not in pressedButtons and previous != None:
            pressedButtons.append(lcd.UP)
            if mode is "games":
                iterator = iter(collections.OrderedDict(sorted(games.items(), key=lambda t: t[0])))
            else:
                iterator = iter(commands)
            needle = iterator.next()
            selection = previous
            previous = needle
            while selection != needle and selection != previous:
                previous = needle
                try:
                    needle = iterator.next()
                except StopIteration:
                    break
            lcd.clear()
            lcdtext = selection.replace('\\n','\n')
            lcd.message(lcdtext)                
    elif lcd.UP in pressedButtons:
        pressedButtons.remove(lcd.UP)

    # Handle DOWN
    if lcd.buttonPressed(lcd.DOWN):
        if lcd.DOWN not in pressedButtons:
            pressedButtons.append(lcd.DOWN)            
            previous = selection
            try:
                selection = iterator.next()
            except StopIteration:
                if mode is "games":
                    iterator = iter(collections.OrderedDict(sorted(games.items(), key=lambda t: t[0])))
                else:
                    iterator = iter(commands)
                selection = iterator.next()
            lcd.clear()
            lcdtext = selection.replace('\\n','\n')
            lcd.message(lcdtext)
    elif lcd.DOWN in pressedButtons:
        pressedButtons.remove(lcd.DOWN)
