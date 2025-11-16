#!/bin/bash

if [[ "$1" == "start" ]];
then
   SERVICE="host_debug_server"
   OUTPUT=$(ps aux | grep -v grep | grep  $SERVICE)
   #echo $OUTPUT
   if [ "${#OUTPUT}" -gt 0 ] ;
   then
      echo -e "\n\tDragonMinded Netboot Web server is already \e[32mRUNNING\e[0m"
      echo -e "\thttp://netbooter.local:8080\n"
   else
      echo -e "\n\tStarting Netboot Web Server..."
      python /sbin/netboot/host_debug_server -p 8080 --config /sbin/netboot/config.yaml > /dev/null 2>&1 &
      echo -e "\n\tDragonMinded Netboot Web server is now \e[32mRUNNING\e[0m"
      echo -e "\thttp://netbooter.local:8080\n"
   fi

elif [[ "$1" == "stop" ]];
then
   for procs in $(pgrep -f host_debug_server) ; do kill -9 $procs > /dev/null 2>&1  ; done
   echo -e "\n\tDragonMinded Netboot Web server is \e[31mNOT RUNNING\e[0m\n"
else
   echo -e "\nUsage: $0 <start|stop>\n"
fi
