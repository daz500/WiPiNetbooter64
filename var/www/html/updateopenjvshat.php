<?php

include 'menu.php';
echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
echo '<section><center><p>';
echo '<h1><a href="openjvs.php">OpenJVS HAT Update</a></h1><br>';

$confirm = $_GET["confirm"];

echo '<div class="box2"></p>';
echo '<b>This will add support for the OpenJVS HAT</b><br><br>';
echo '<b>The Pi will restart after the update</b><br><br>';
echo '<b>Are you sure?</b><br><br>';
echo '<form action="updateopenjvshat.php?confirm=yes" method="post">';
echo '<button type="submit" class="dropbtn" value="Confirm">Confirm</button> <a href="openjvs.php" style="font-weight:normal" class="dropbtn">Cancel</a></form></div><br>';

if ($confirm == "yes"){

echo '<br>';
ini_set('output_buffering', false);
    $handle = popen('sudo bash /root/openjvs-hat/hatupdate.sh', 'r');
    while(!feof($handle)) {
      $buffer = fgets($handle);
      echo "$buffer";
      flush();
}
pclose($handle);
echo '<br><br><font color="green"><b>HAT Update Applied<br>Rebooting ...</b></font>';
$rebootcommand = escapeshellcmd("sudo python /sbin/piforce/reboot.py");
shell_exec($rebootcommand . '> /dev/null 2>/dev/null &');
}

?>