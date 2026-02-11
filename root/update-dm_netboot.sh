#!/bin/bash

echo '***DragonMinded Netboot Update Script***'
echo ''
echo 'Testing Internet Connectivity ...'
echo ''
ping -q -c3 google.com > /dev/null

if [ $? -eq 0 ];
then
echo ''
echo 'Internet Connection Detected'
echo ''
echo 'Downloading Source Files ...'
echo ''
cd /sbin/netboot/
git pull --rebase --autostash
sudo python3 -m pip install -r requirements.txt --upgrade
echo ''
echo 'Install Complete'
echo ''
else
echo 'No Internet Connection Detected'
fi
