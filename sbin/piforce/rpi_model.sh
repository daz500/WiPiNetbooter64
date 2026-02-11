#!/bin/bash

echo "$(date +"%Y%m%d %H%M%S") Script $0 initiated"

# Get raspberry hardware model
model_version=$(cat /proc/device-tree/model | awk '{print $3}')

# Get the current model (last device used)
current_pi_version=$(cat /sbin/piforce/rpiversion.txt)

# Check if needs to be updated
if [ "$model_version" -eq "$current_pi_version" ]; then
    echo "$(date +"%Y%m%d %H%M%S") Raspberri PI $model_version model correct, nothing to do."
else
    echo "$(date +"%Y%m%d %H%M%S") New hardware detected, updating configuration from $current_pi_version to $model_version"
    cat /proc/device-tree/model | awk '{print $3}' > /sbin/piforce/rpiversion.txt
fi

echo -e "$(date +"%Y%m%d %H%M%S") Script finished\n"
