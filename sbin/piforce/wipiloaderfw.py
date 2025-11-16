import os
import sys

def exists(path):
    try:
        os.stat(path)
    except OSError:
        return False
    return True

romfile = '/boot/config/firmware/'+sys.argv[1]
activedimm = sys.argv[2]
pidpath = '/var/log/'+activedimm

if exists(pidpath):
    lastpidfile = open(pidpath, "r")
    lastpid = lastpidfile.readline()
    lastpidfile.close()
else:
    lastpidfile = open(pidpath, "w")
    lastpidfile.close()

try:
    os.kill(int(lastpid), signal.SIGKILL)
except:
    pass
				
loaderCommand = 'sudo python3 /sbin/piforce/dm_netboot/netdimm_send '+activedimm+' '+romfile+' --disable-crc'
os.system(loaderCommand)

