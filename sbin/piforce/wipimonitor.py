#!/usr/bin/env python3

import sys
import os
import subprocess
import threading
import csv
import glob
import psutil
from queue import Queue
from pathlib import Path
from time import sleep

def checkprocess(process):
    for proc in psutil.process_iter():
        try:
            if process.lower() in proc.name().lower():
                return True
        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            pass
    return False

def exists(path):
    try:
        os.stat(path)
    except OSError:
        return False
    return True

def rompoll(threadname, q):
    global stop_thread
    stop_thread = False
    noromfile = False
    dropfolderpath = "/var/log/activerom"
    before = dict ([(f, None) for f in os.listdir (dropfolderpath)])
    while True:
        after = dict ([(f, None) for f in os.listdir (dropfolderpath)])
        added = [f for f in after if not f in before]
        removed = [f for f in before if not f in after]
        if added:
            for new in added:
               q.put(Path(new).name)
            before = after
        if removed:
            before = after
        sleep(1)

def main():
    
    with open('/sbin/piforce/openmode.txt', mode='r') as openfile:
        openmode = openfile.readline()
    with open('/sbin/piforce/ffbmode.txt', mode ='r') as ffbfile:
        ffbmode = ffbfile.readline()
    with open('/sbin/piforce/emumode.txt', mode ='r') as emufile:
        emumode = emufile.readline()
    with open('/sbin/piforce/servermode.txt', mode ='r') as srvfile:
        srvmode = srvfile.readline()
    with open('/sbin/piforce/zeromode.txt', mode ='r') as zerofile:
        zeromode = zerofile.readline()

    with open('/var/www/html/csv/romsinfo.csv', mode='r') as infile:
        reader = csv.reader(infile)
        gameinfo = {rows[1]:(rows[4],rows[14],rows[15]) for rows in reader}
    infile.close()
    print(gameinfo)

    DropFolderGlobPath = "/var/log/activerom/*"
    romfiles = glob.glob(DropFolderGlobPath)
    for f in romfiles:
        os.remove(f)

    rompollq = Queue()
    rompollthread = threading.Thread(target=rompoll, args=("RomPollingThread", rompollq))
    rompollthread.daemon = True
    rompollthread.start()

    while True:
        while not rompollq.empty():
            romqdata = rompollq.get_nowait()
            romdata = romqdata.split('!')
            game = romdata[0]
            activedimm = romdata[1]
            if (emumode == 'auto' and ('initial_d' in game or 'fzeroax' in game)):
                if not checkprocess('cardemu.py'):
                    if exists('/dev/COM1'):
                        compath = '/dev/'+os.readlink('/dev/COM1')
                        devices = glob.glob('/dev/ttyUSB*')
                        for device in devices:
                            if (device != compath):
                                emuport = device
                            break
                    else:
                        emuport = '/dev/ttyUSB0'
                if ('initial_d' in game):
                    if ('initial_d_3' in game):
                        IDMode = 'id3'
                    elif ('initial_d_2' in game):
                        IDMode = 'id2'
                    else:
                        IDMode = 'idas'
                    try:
                        cp.kill()
                    except:
                        pass
                    cp = subprocess.Popen(['python3', '/sbin/piforce/card_emulator/idcardemu.py', '-cp', emuport, '-m', IDMode], preexec_fn=os.setsid)
                if ('fzeroax' in game):
                    try:
                        cp.kill()
                    except:
                        pass
                    cp = subprocess.Popen(['python3', '/sbin/piforce/card_emulator/fzerocardemu.py', '-cp', emuport], preexec_fn=os.setsid)
            if ('monster_ride' in game or 'cycraft' in game):
                cycraftCommand = 'sudo /usr/lib/cycraft &'
                os.system(cycraftCommand)
            if (zeromode == 'hackon'):
                hackCommand = 'sudo python3 /sbin/piforce/timehack.py '+activedimm+' &'
                os.system(hackCommand)
            if (openmode == 'openon'):
                openjvsCommand1 = 'killall -9 openjvs'
                os.system(openjvsCommand1)
                openjvsCommand2 = 'sudo openjvs '+gameinfo[game][1]+' &'
                os.system(openjvsCommand2)
            if (ffbmode == 'ffbon'):
                ffbCommand1 = 'killall -9 openffb'
                os.system(ffbCommand1)
                ffbCommand2 = 'sudo openffb -h=0 -gp='+gameinfo[game][2]+' &'
                os.system(ffbCommand2)
            print(romdata)
        sleep(1)

if __name__ == "__main__": main()