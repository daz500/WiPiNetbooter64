#!/bin/bash

echo "$(date +"%Y%m%d %H%M%S") Script $0 initiated"

# OpenJVS Hat config
cat /etc/openjvs/config | grep -v '#' | grep -q 'ttyAMA'
openjvshat=$?
# config.txt location
boot_config="/boot/firmware/config.txt"

# If OpenJVS Hat is enabled, proceed
if [ $openjvshat -eq 0 ]; then
    # Check the Raspberry Pi version
    version=$(cat /sbin/piforce/rpiversion.txt)

    # Check rpi version and work on openjvs configuration of serial ports
    if [ "$version" -eq 4 ]; then

        # Search for 'DEVICE_PATH /dev/ttyAMA*' in openjvs config
        if grep -v '#' /etc/openjvs/config | grep -q '^DEVICE_PATH /dev/ttyAMA3$'; then
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config is already using correct /dev/ttyAMA3 serial port on Raspberry PI 4."
        else
            # Replace any other ttyAMA device for 'DEVICE_PATH /dev/ttyAMA3'
            sed -i '/^DEVICE_PATH/ {s|^DEVICE_PATH .*|DEVICE_PATH /dev/ttyAMA3|}' /etc/openjvs/config
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config set to use /dev/ttyAMA3 serial por on Raspberry PI 4"
        fi

	# Check for uart3 and uart4 entries in config.txt 
	uart3_found=$(grep -x "dtoverlay=uart3" "$boot_config")
	uart4_found=$(grep -x "dtoverlay=uart4" "$boot_config")

	if [ -n "$uart3_found" ] && [ -n "$uart4_found" ]; then
	     echo "$(date +"%Y%m%d %H%M%S") OK: dtoverlay=uart3 and dtoverlay=uart4 entries found."
	else
 	    echo "$(date +"%Y%m%d %H%M%S") Validation failed:"
	    [ -z "$uart3_found" ] && echo " - dtoverlay=uart3 not found"
	    [ -z "$uart4_found" ] && echo " - dtoverlay=uart4 not found"

	    echo "$(date +"%Y%m%d %H%M%S") Removing old entries and adding dtoverlay=uart3 and dtoverlay=uart4 to config.txt"
	    sed -i '/^dtoverlay=uart/d' "$boot_config"
	    sed -i '/^dtoverlay=disable-bt/d' "$boot_config"
	    printf "\ndtoverlay=uart3\ndtoverlay=uart4\n" >> "$boot_config"

	    echo "$(date +"%Y%m%d %H%M%S") Rebooting"
	    sudo reboot

	fi

    elif [ "$version" -eq 5 ]; then

        # Search for 'DEVICE_PATH /dev/ttyAMA*' in openjvs config
        if grep -v '#' /etc/openjvs/config | grep -q '^DEVICE_PATH /dev/ttyAMA2$'; then
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config is already using correct /dev/ttyAMA2 serial port on Raspberry PI 5."
        else
            # Replace any other ttyAMA device for 'DEVICE_PATH /dev/ttyAMA2'
            sed -i '/^DEVICE_PATH/ {s|^DEVICE_PATH .*|DEVICE_PATH /dev/ttyAMA2|}' /etc/openjvs/config
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config set to use /dev/ttyAMA2 serial por on Raspberry PI 5"
        fi

	# Check for uart3 and uart4 entries in config.txt
	uart2_found=$(grep -x "dtoverlay=uart2-pi5" "$boot_config")
	uart3_found=$(grep -x "dtoverlay=uart3-pi5" "$boot_config")

	if [ -n "$uart2_found" ] && [ -n "$uart3_found" ]; then
	     echo "$(date +"%Y%m%d %H%M%S") OK: dtoverlay=uart2-pi5 e dtoverlay=uart3-pi5 entries found."
	else
 	    echo "Validation failed:"
	    [ -z "$uart2_found" ] && echo " - dtoverlay=uart2-pi5 not found"
	    [ -z "$uart3_found" ] && echo " - dtoverlay=uart3-pi5 not found"

	    echo "$(date +"%Y%m%d %H%M%S") Removing old entries and adding dtoverlay=uart2-pi5 and dtoverlay=uart3-pi5 to config.txt"
	    sed -i '/^dtoverlay=uart/d' "$boot_config"
	    sed -i '/^dtoverlay=disable-bt/d' "$boot_config"
	    printf "\ndtoverlay=uart2-pi5\ndtoverlay=uart3-pi5\n" >> "$boot_config"

	    echo "$(date +"%Y%m%d %H%M%S") Rebooting"
	    sudo reboot

	fi

   elif [ "$version" -eq 3 ]; then

        # Search for 'DEVICE_PATH /dev/ttyAMA*' in openjvs config
        if grep -v '#' /etc/openjvs/config | grep -q '^DEVICE_PATH /dev/ttyAMA0$'; then
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config is already using correct /dev/ttyAMA0 serial port on Raspberry PI 3."
        else
            # Replace any other ttyAMA device for 'DEVICE_PATH /dev/ttyAMA0'
            sed -i '/^DEVICE_PATH/ {s|^DEVICE_PATH .*|DEVICE_PATH /dev/ttyAMA0|}' /etc/openjvs/config
            echo "$(date +"%Y%m%d %H%M%S") OpenJVS config set to use /dev/ttyAMA0 serial por on Raspberry PI 3"
        fi

        ## Check for dtoverlay=disable-bt entry in config.txt
        disablebt_found=$(grep -x "dtoverlay=disable-bt" "$boot_config")

        if [ -n "$disablebt_found" ]; then
             echo "$(date +"%Y%m%d %H%M%S") OK: dtoverlay=disable-bt entry found."
        else
            echo "Validation failed:"
            [ -z "$disablebt_found" ] && echo " - dtoverlay=disable-bt not found"
	
            echo "$(date +"%Y%m%d %H%M%S") Removing old entries and adding dtoverlay=disable-bt to config.txt"
            sed -i '/^dtoverlay=uart/d' "$boot_config"
            printf "\ndtoverlay=disable-bt\n" >> "$boot_config"
	
            echo "$(date +"%Y%m%d %H%M%S") Rebooting"
            sudo reboot
	
        fi

    else
        echo "$(date +"%Y%m%d %H%M%S") Raspberri pi 3, 4 or 5 not detected, no action needed."
    fi
else
    echo "$(date +"%Y%m%d %H%M%S") OpenJVS Hat is not enabled."
fi

echo -e "$(date +"%Y%m%d %H%M%S") Script finished\n"
