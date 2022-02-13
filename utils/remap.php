<?php
$db = mysql_connect("localhost", "wkuhns");
mysql_select_db("jukebox",$db);

include '../sessionheader.php';
//include '../phpfunctions.php';

// Create new directory structure under /music and move file into it. Create symlinks so jukebox won't die for now.

$sql = "select cdtag,uid,title from album where (status & 257 = 1) and (status & 128 = 0) order by uid";
$result = mysql_query($sql,$db);

//echo "$sql<br>\n";

while($myrow=mysql_fetch_row($result)){

  $cdtag = strtolower($myrow[0]);
  $albumid = ($myrow[1]);
  $atitle = ($myrow[2]);

  makeMyDir($cdtag);
  echo "$cdtag ...";
  
  $sql = "select url,mp3,wav,track.uid from listitem,track where listitem.albumid=$albumid and listitem.trackid=track.uid";
  $result2 = mysql_query($sql,$db);
  //echo "$sql<br>\n";
  
  while($myrow2=mysql_fetch_row($result2)){
    $base = substr($myrow2[0],0,11);
    $file = substr($myrow2[0],11,8);
    $tracktag = substr($myrow2[0],7,3);
    if($tracktag != $cdtag){
      echo "Error: track $myrow2[0] does not match album $cdtag";
      exit;
    }
    $fname = $base . $file . ".mp3";
    $d1 = substr($myrow2[0],7,1);
    $d2 = substr($myrow2[0],8,1);
    $d3 = substr($myrow2[0],9,1);
    if(file_exists($fname)){
      $destfile = "/music/$d1/$d2/$d3/$file.mp3";
      if(file_exists($destfile)){
    	echo "Error moving $myrow2[0]: $destfile exists<br>";
    	//exit;
      }
      $cmd = "mv $fname /music/$d1/$d2/$d3/";
      system($cmd);
      //echo "$cmd<br>\n";
      $sql = "update track set url = '/music/$d1/$d2/$d3/$file.mp3', ftype = (ftype | 2) where uid = $myrow2[3]";
      mysql_query($sql,$db);
    }

    $fname = $base . $file . ".m4a";
    if(file_exists($fname)){
      $destfile = "/music/$d1/$d2/$d3/$file.m4a";
      if(file_exists($destfile)){
    	echo "Error moving $myrow2[0]: $destfile exists<br>";
    	//exit;
      }
      $cmd = "mv $fname /music/$d1/$d2/$d3/";
      system($cmd);
      //echo "$cmd<br>\n";
      $sql = "update track set url = '/music/$d1/$d2/$d3/$file.m4a', ftype = (ftype | 4) where uid = $myrow2[3]";
      mysql_query($sql,$db);
    }

    $fname = $base . $file . ".wav";
    if(file_exists($fname)){
      $destfile = "/music/$d1/$d2/$d3/$file.wav";
      if(file_exists($destfile)){
    	echo "Error moving $myrow2[0]: $destfile exists<br>";
    	//exit;
      }
      $cmd = "mv $fname /music/$d1/$d2/$d3/";
      system($cmd);
      //echo "$cmd<br>\n";
      $sql = "update track set url = '/music/$d1/$d2/$d3/$file.wav', ftype = (ftype | 1) where uid = $myrow2[3]";
      mysql_query($sql,$db);
    }

  }

  if(file_exists("$base/cover.jpg")){
    $cmd = "mv $base/cover.jpg /music/$d1/$d2/$d3/";
    system($cmd);
    //echo "$cmd<br>\n";
    $sql = "update album set status = (status | 16) where uid = $albumid";
    mysql_query($sql,$db);
  }

  // Mark album as converted
  $sql = "update album set status = (status | 256) where uid = $albumid";
  mysql_query($sql,$db);

  $cmd = "ln -s /music/$d1/$d2/$d3/* $base";
  system($cmd);

  $cmd = "rm $base*.inf";
  system($cmd);

  echo "Moved album $d1$d2$d3 $atitle<br>\n";
  //exit;
}