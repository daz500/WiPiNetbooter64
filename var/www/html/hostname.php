<?php

include 'menu.php';
$wifimode = file_get_contents('/sbin/piforce/wifimode.txt');
echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
echo '<link rel="stylesheet" href="css/sidebarstyles.css">';

if(isset($_POST["submit"]))
{
$hostname = $_POST["hostname"];

if (ctype_alnum($hostname)) {

 if($error == '')
 {
  $command = escapeshellcmd('sudo python3 /sbin/piforce/hostname.py '.$hostname);
  shell_exec($command);
  $error = '<font color="green"><b>Hostname Updated</b></font>';
  $rebootcommand = escapeshellcmd("sudo python /sbin/piforce/reboot.py");
  shell_exec($rebootcommand . '> /dev/null 2>/dev/null &');
 }
}
else{
  $error .= '<font color="red"><b>Hostname is invalid</b></font><br>';
}
}

echo '<section><center><h1>';
echo '<h1><a href="network.php">Update Hostname</a></h1>';
$wiredip = `ip -o -f inet addr show | awk '/eth0/ {print $4}'`;
$wirelessip = `ip -o -f inet addr show | awk '/wlan0/ {print $4}'`;
$wiredstatus =  `ip -o -f inet addr show | awk '/eth0/ {print $9}'`;
$wirelessstatus = `ip -o -f inet addr show | awk '/wlan0/ {print $9}'`;
$ssid = `iwgetid -r`;
if ($wiredstatus == "dynamic\n"){$wiredtype = "DHCP";}else{$wiredtype = "Static";}
if ($wirelessstatus == "dynamic\n"){$wirelesstype = "DHCP";}else{$wirelesstype = "Static";}
echo 'Wireless IP: <b>'.$wirelessip.' ('.$wirelesstype.')</b><br>';
echo 'Wired IP: <b>'.$wiredip.' ('.$wiredtype.')</b><br><br>';
if ($wifimode == 'hotspot'){
echo 'Current Wifi Mode: <b>HotSpot</b><br><br>';}
else {echo 'Current Wifi Mode: <b>Home WiFi</b><br>Current SSID: <b>'.$ssid.'</b><br><br>';}
if ($wifimode == 'hotspot'){
echo 'The Pi is currently set up in HotSpot mode broadcasting its own WiFi network<br><br>';
}
else {
echo 'The Pi is currently set up in Home WiFi mode<br><br>';
}
echo 'Use the form below to update the host name of the Pi<br><br>';
echo '<b>NOTE: You can only use letters and numbers</b><br><br>';
echo 'Once the hostname has changed, the URL you access it on will also change<br><br>';
echo 'It will become http://<b>hostname</b>.local<br><br>';
echo 'The Pi will reboot and update the settings.<br><br>';
echo '<div class="box2"><br>';
echo '<form method="post" id="form1">';
echo '<b><label for="ip">Hostname: </label>';
echo '<input type="text" size="10" id="ip" name="hostname"><br><br>';
echo '<input type="submit" name="submit" class="dropbtn" value="Apply and Reboot"><br><br></div>';
echo '<br><br>';
echo '</form>';
echo $error;
?>
</p><center></body></html>