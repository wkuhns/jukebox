<?php
include '../sessionheader.php';
include '../constants.php';

require_once('/home/httpd/html/jukebox2/getid3/getid3.php');

// Go through mp3file and attempt to set relevant fields, making the best of ID3V1 and ID3V2 
// as well as any inferences from linked track / album / artist records

// This ONLY updates mp3file, which was originally built by various versions of build_mp3_db.php
// In the future this logic should be incorporated into build_mp3_db.php

$fmask = MP3;
$query = "select uid,url,title,ftype from track 
  where ((url like '%mp3') or (ftype & $fmask)) 
  and (duration is null or duration = 0)";
echo $query;
$result = doquery($query);
  echo "<br>\n";

while($mp3track = mysql_fetch_row($result)){
  $getID3 = null;
  $getID3 = new getID3;
  $getID3->option_no_iconv = true;

  $filepath = substr($mp3track[1],0,21) . ".mp3";
  if(!file_exists($filepath)){
    $query = "update track set ftype=ftype & ~$fmask where uid=$mp3track[0]";
    doquery($query);
    echo "File does not exist: $filepath<br>";
    exit;
  }
  // Analyze file and store returned data in $ThisFileInfo
  $ThisFileInfo = $getID3->analyze("$filepath");
  //$getID3->encoding = "ISO8859-1";
  //$getID3->option_no_iconv = true;
  //echo "<pre>";
  //print_r($ThisFileInfo);
  //echo "</pre>";
  //exit;

  $bitrate = $ThisFileInfo['audio']['bitrate'];           // audio bitrate
  $pts = $ThisFileInfo['playtime_string'];            // playtime in minutes:seconds, formatted string
  $hms = explode(':',$pts);
  $mins = $hms[0];
  $secs = $hms[1];
  $duration = $mins*60+$secs;

  // Data fro ID3V1 tag
  $id1_artist = $ThisFileInfo['tags']['id3v1']['artist'][0];
  $id1_album = $ThisFileInfo['tags']['id3v1']['album'][0];
  $id1_title = $ThisFileInfo['tags']['id3v1']['title'][0];
  $id1_track = $ThisFileInfo['tags']['id3v1']['track_number'][0];

  // Data fro ID3V2 tag
  $id2_artist = $ThisFileInfo['tags']['id3v2']['artist'][0];
  $id2_album = $ThisFileInfo['tags']['id3v2']['album'][0];
  $id2_title = $ThisFileInfo['tags']['id3v2']['title'][0];
  $id2_track = $ThisFileInfo['tags']['id3v2']['track_number'][0];

  $ftypes = $mp3track[3] & WAV ? 'wav ' : '';
  $ftypes .= $mp3track[3] & MP3 ? 'mp3 ' : '';
  $ftypes .= $mp3track[3] & M4A ? 'm4a' : '';
  
  //echo "Files: $ftypes<br>\n";
  //echo "Title: $mp3track[2]<br>\n";
  //echo "Track1: $id1_title<br>\n";
  //echo "Track2: $id2_title<br>\n";
  //echo "File: $mp3track[1]<br>\n";
  //echo "MP3 File: $filepath<br>\n";
  //echo "Bitrate: $bitrate<br>\n";
  //echo "Duration: $pts ($duration)<br>\n";
  if(is_numeric($duration) && is_numeric($bitrate)){
    $query = "update track set duration=$duration, bitrate=$bitrate where uid=$mp3track[0]";
    echo "$query<br>\n";
    doquery($query);
  }else{
    echo "Track $mp3track[0] ($filepath) has invalid duration/bitrate: $duration/$bitrate<br>";

  }
}

// WAV files too
$fmask = WAV;
$query = "select uid,url,title,ftype from track
  where ((url like '%wav') or (ftype & $fmask)) 
  and (duration is null or duration = 0)";
echo $query;
$result = doquery($query);
echo "<br>\n";

while($track = mysql_fetch_row($result)){
  $size = filesize($track[1]);
  $secs = intval($size/176400);
  $mins = intval($secs/60);
  $fmins = $secs - $mins*60;
  printf("%s: %d:%d<br>\n",$track[1],$mins,$fmins);
  $sql = "UPDATE track SET duration=$secs where uid=$track[0]";
  doquery($sql);
}



