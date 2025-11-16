#!/bin/bash

echo -e "\n\nRun this script AFTER Mario Kart games are loaded into Triforce board!\n\n"
read -rsn1 -p"Press any key to continue";echo

ifconfig eth0 down
ifconfig eth0 192.168.29.104 netmask 255.255.255.0
ifconfig eth0 up
