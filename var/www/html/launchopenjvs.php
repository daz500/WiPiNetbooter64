<?php

header("Refresh: 2; url=openjvscontrol.php");
include 'menu.php';
echo '<html lang="en"><head><meta charset="utf-8"><title>WiPi Netbooter</title>';
echo '<link rel="stylesheet" href="css/sidebarstyles.css">';
echo '<section><center><p>';
$mapping = $_POST['mapping'];

echo '<br><br>Starting OpenJVS with mapping<br><b>'.$mapping.'</b>';

$opencommand1 = escapeshellcmd('sudo killall -9 openjvs');
shell_exec($opencommand1 . '> /var/log/openjvs/openjvs.log 2>&1');

$opencommand2 = escapeshellcmd('sudo openjvs '.$mapping);
shell_exec($opencommand2 . '>> /var/log/openjvs/openjvs.log 2>&1 &');

?>
