<?php
echo "<PRE>";
include("../classes/class.cddb.php");
$cddb = new cddb();
$cddb->connect();
$cddb->protocol(5);
print_r($cddb->genres());
print_r($cddb->help("discid"));
print_r($cddb->log());
print_r($cddb->status()); 
$info = $cddb->import();
$data = $cddb->query($info['discid'], $info['tracks'],$info['timing'], $info['length']);
print_r($data[0]);
print_r($cddb->read($data[0]['category'], $info['discid']));
print_r($cddb->motd());
print_r($cddb->version());
$cddb->disconnect();
print_r($cddb->messages);
echo "</PRE>";

