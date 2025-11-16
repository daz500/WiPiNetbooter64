import subprocess, sys, os

result = subprocess.run(['hostname'], stdout=subprocess.PIPE)
hostname = (((result.stdout).decode('utf-8')).replace('\n',''))

hostnamecmd = 'sudo sed -i "s/'+hostname+'/'+sys.argv[1]+'/g" /etc/hostname'
hostscmd = 'sudo sed -i "s/'+hostname+'/'+sys.argv[1]+'/g" /etc/hosts'

os.system(hostnamecmd)
os.system(hostscmd)