<?php

$action = $_GET["action"];
$target = $_GET["target"];
$openmode = $_GET["openmode"];

if ($action == "launch"){
header("Refresh: 2; url=gamelist.php?display=all");
include 'menu.php';
echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
echo '<section><center>';
if ($openmode == 'openon'){
$end = $target.' openjvs';
$command = escapeshellcmd('sudo python3 /sbin/piforce/menulauncher.py '.$end);
shell_exec($command . '> /dev/null 2>/dev/null &');
}
else{
$end = $target.' default';
$command = escapeshellcmd('sudo python3 /sbin/piforce/menulauncher.py '.$end);
shell_exec($command . '> /dev/null 2>/dev/null &');
}
echo '<br>Sending On-Screen Menu...';
}

else{

function pinger($address){
        $command = "fping -c1 -t500 $address";
        exec($command, $output, $status);
        if($status === 0){
            return true;
        }else{
            return false;
        }
    }

$f = fopen("csv/dimms.csv", "r");
$headers = ($row = fgetcsv($f));
$onlinedimms = array();
while (($row = fgetcsv($f)) !== false) {
$dimmname = $row[0];
$ip = $row[1];

if (pinger($row[1]) == true){
   $onlinedimms[$ip] = $dimmname;
   $onlydimm = $ip;
   }
}

$count = count($onlinedimms);

if ($count == 1){
header("Refresh: 2; url=gamelist.php?display=all");
include 'menu.php';
echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
echo '<section><center>';
if ($openmode == 'openon'){
$end = $onlydimm.' openjvs';
$command = escapeshellcmd('sudo python3 /sbin/piforce/menulauncher.py '.$end);
shell_exec($command . '> /dev/null 2>/dev/null &');
}
else{
$end = $onlydimm.' default';
$command = escapeshellcmd('sudo python3 /sbin/piforce/menulauncher.py '.$end);
shell_exec($command . '> /dev/null 2>/dev/null &');
}
echo '<br>Sending On-Screen Menu...';
}

if ($count == 0){
   include 'menu.php';
   echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
   echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
   echo '<section><center>';
   echo '<div class="box2"></p>';
   echo '<b>No dimms available</b></p>';
   echo '<h1><form action="gamelist.php?display=all" method="post"><input type="submit" class="bigdropbtn" value="Main Menu"></form></h1>';
   echo '</div>';
}

if ($count > 1){
   include 'menu.php';
   echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
   echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
   echo '<section><center>';
   echo '<div class="box2"></p>';
   echo '<b>Multiple dimms found for Sega Naomi</b></p>';
   foreach($onlinedimms as $ipaddress => $name) {
      echo '<h1><form action="menulauncher.php?action=launch&target='.$ipaddress.'" method="post"><input type="submit" class="bigdropbtn" value="'.$name.'"></form></h1>';
   }
  echo '</div>';
}
}
?>