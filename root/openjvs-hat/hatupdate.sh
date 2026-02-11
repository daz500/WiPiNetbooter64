#!/bin/bash

VER=$(cat /proc/device-tree/model | awk '{print $3}')

if [ $VER = "3" ]; then
   echo "Pi 3 detected, adding config.txt entries"
if ! grep -Fxq "dtoverlay=disable-bt" /boot/firmware/config.txt; then
   echo -e "\n" | sudo tee -a /boot/firmware/config.txt
   echo "dtoverlay=disable-bt" | sudo tee -a /boot/firmware/config.txt
   sudo systemctl disable hciuart
else
   echo "Entries already found!"
   sudo systemctl disable hciuart
fi
   echo "Copying OpenJVS config file"
   sudo cp /root/openjvs-hat/config.pi3hat /etc/openjvs/config
fi
if [ $VER = "4" ]; then
   echo "Pi 4 detected, adding config.txt entries"
if ! grep -Fxq "dtoverlay=uart3" /boot/firmware/config.txt; then
   echo -e "\n" | sudo tee -a /boot/firmware/config.txt
   echo "dtoverlay=uart3" | sudo tee -a /boot/firmware/config.txt
   echo "dtoverlay=uart4" | sudo tee -a /boot/firmware/config.txt
else
   echo "Entries already found!"
fi
   echo "Copying OpenJVS config file"
   sudo cp /root/openjvs-hat/config.pi4hat /etc/openjvs/config
fi
if [ $VER = "5" ]; then
   echo "Pi 5 detected, adding config.txt entries"
if ! grep -Fxq "dtoverlay=uart2-pi5" /boot/firmware/config.txt; then
   echo -e "\n" | sudo tee -a /boot/firmware/config.txt
   echo "dtoverlay=uart2-pi5" | sudo tee -a /boot/firmware/config.txt
   echo "dtoverlay=uart3-pi5" | sudo tee -a /boot/firmware/config.txt
else
   echo "Entries already found!"
fi
   echo "Copying OpenJVS config file"
   sudo cp /root/openjvs-hat/config.pi5hat /etc/openjvs/config
fi

#sudo cp /root/openjvs-hat/cmdline.txt /boot/firmware/cmdline.txt
sudo sed -i 's/console=serial[^ ]*/console=ttyAMA0,115200/g' /boot/firmware/cmdline.txt
systemctl unmask serial-getty@tty1
systemctl enable serial-getty@tty1

echo "HAT Support added successfully"
