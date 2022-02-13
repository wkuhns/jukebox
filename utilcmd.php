<?php
// Collection of functions that don't generate HTML output

include ("sessionheader.php");

// For debugging, a place to write output
$outfile = fopen("/tmp/jblog.txt","w");

// This is the currently selected playlist. Might NOT be the one that the user has clicked on in album window.
$plistid = $_COOKIE["plistid"];

// This is the ID of the album in the album window where the click originated. May be same as $plistid.
$albumid = $_GET['albumid'];

// Drop a track from a playlist
if ($_GET['action'] == 'dropTrack'){
  $trackid = $_GET['trackuid'];
  $plid = $_GET['playlistuid'];
  if(is_numeric($trackid) && is_numeric($plid)){
    $query = "delete from listitem where trackid=$trackid and albumid=$plid";
    fwrite($outfile,$query);
    doquery($query);
  }
}

if ($_GET['action'] == 'delete_track'){
  // delete a track and all listitems that point to it
  $trackid = $_GET['trackuid'];
  
  $query = "select url from track where uid=$trackid";
  fwrite($outfile,$query);
  $result = doquery($query);
  $myrow = mysqli_fetch_row($result);
  $filepath = $myrow[0];

  $query = "delete from listitem where trackid=$trackid";
  fwrite($outfile,$query);
  doquery($query);

  $query = "delete from track where uid=$trackid";
  fwrite($outfile,$query);
  doquery($query);

  $query = "delete from mp3file where file='$filepath'";
  fwrite($outfile,$query);
  doquery($query);

  $query = "delete from ratings where trackid=$trackid";
  fwrite($outfile,$query);
  doquery($query);

  $query = "delete from talink where tid=$trackid";
  fwrite($outfile,$query);
  doquery($query);

  $cmd = "rm $filepath";
  fwrite($outfile,$cmd);
  system($cmd);
}

if ($_GET['action'] == "collectionadd"){
  $mycollectionid = $_GET['collectionid'];
  $query = "insert into alistitem (alistid,albumid) values ($mycollectionid,$albumid)";
  //fwrite($outfile,$query);
  doquery($query);
  // delete any old rating records...
  $query = "delete ratings.* from ratings,listitem  
  where collid=$mycollectionid 
  and ratings.trackid=listitem.trackid 
  and listitem.albumid=$albumid";
  //fwrite($outfile,$query);
  doquery($query);
  // Create new ratings records for all tracks
  $query = "insert into ratings select $mycollectionid,trackid,50,(50 + rand() * 100) 
  from listitem where listitem.albumid=$albumid";
  //fwrite($outfile,$query);
  doquery($query);

}

if ($_GET['action'] == "collectiondrop"){
  $mycollectionid = $_GET['collectionid'];
  $query = "delete from alistitem where alistid=$mycollectionid and albumid=$albumid";
  doquery($query);
  //fwrite($outfile,$query);

}
fclose($outfile);
