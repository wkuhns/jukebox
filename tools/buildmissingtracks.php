<?php 
include '/home/httpd/html/jukebox2/phpfunctions.php';

$db = mysql_connect("localhost", "wkuhns");
mysql_select_db("jukebox",$db);

system("rm /music/tools/missingtracks");

$outfile = fopen("/music/tools/missingtracks","w");

$query = "select track.uid, track.ftype, listitem.seq, track.title, track.url, album.cdtag 
	from album,listitem,track 
	where album.status & 128 = 0
	and album.uid=listitem.albumid 
	and listitem.trackid=track.uid";
$result=mysql_query($query,$db);
while ($myrow = mysql_fetch_row($result)){
  $trackid = $myrow[0];
  $ftype = $myrow[1];
  $seq = $myrow[2];
  $title = $myrow[3];
  $trackurl = $myrow[4];
  $cdtag = $myrow[5];
  
  if ($ftype & 1){
    $url = substr($trackurl,0,strlen($trackurl)-3) . 'wav';
    if (!file_exists($url)){
      makeMyDir($cdtag);
      $cmd = "scp -i /root/.ssh/backup a1.smallbusinessworks.net:$url $url;chown www-data:webusers $url; chmod 0664 $url\n";
      fwrite($outfile,$cmd);
    }
  }
  if ($ftype & 2){
    $url = substr($trackurl,0,strlen($trackurl)-3) . 'mp3';
    if (!file_exists($url)){
      makeMyDir($cdtag);
      $cmd = "scp -i /root/.ssh/backup a1.smallbusinessworks.net:$url $url;chown www-data:webusers $url; chmod 0664 $url\n";
      fwrite($outfile,$cmd);
    }
  }
  if ($ftype & 4){
    $url = substr($trackurl,0,strlen($trackurl)-3) . 'm4a';
    if (!file_exists($url)){
      makeMyDir($cdtag);
      $cmd = "scp -i /root/.ssh/backup a1.smallbusinessworks.net:$url $url;chown www-data:webusers $url; chmod 0664 $url\n";
      fwrite($outfile,$cmd);
    }
  }
}
fclose($outfile);

$query = "select uid from album where (status & " . PLAYLIST . ") != 128";
$result=mysql_query($query,$db);
while ($myrow=mysql_fetch_row($result)){
  build_tree("/music/music/",$myrow[0]);
}


function build_tree($baseurl,$albumid){
  global $db;
  $query = "select dispname, title, cdtag
  from album,artistlink,artist
  where album.uid=$albumid
  and artistlink.albumid=$albumid
  and artist.uid=artistlink.artistid
  and album.status & " . PLAYLIST . " != 128";
  $result=mysql_query($query,$db);
  if (!$myrow = mysql_fetch_row($result)){
    echo "Error: $query: Invalid album $albumid";
    exit;
  }
  $artist = $myrow[0];
  $album = $myrow[1];
  $cdtag = $myrow[2];
  $artist = sanitize($artist);
  $album = sanitize($album);

  // Create artist directory if needed
  $path = $baseurl . $artist;
  if (!file_exists($path)){
    echo "mkdir $path<br>\n";
    mkdir($path);
  }

  // Create album directory if needed
  $path = $path . "/" . $album;
  if (!file_exists($path)){
    echo "mkdir $path<br>\n";
    mkdir($path);
  }

  // Create track symlinks
  $query = "select ftype, listitem.seq, title
  	from listitem,track 
  	where listitem.albumid=$albumid
  	and listitem.trackid=track.uid";
  $result=mysql_query($query,$db);
  while ($myrow = mysql_fetch_row($result)){
    $title = sanitize($myrow[2]);
    if ($myrow[0] & WAV){
      $srcdir = MakeURL($cdtag,$myrow[1],'wav');
      $mypath = sprintf("$path/%02d $title.wav",$myrow[1]);
      if (!file_exists($mypath)){
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        system($cmd);
      }
    }
    if ($myrow[0] & MP3){
      $srcdir = MakeURL($cdtag,$myrow[1],'mp3');
      $mypath = sprintf("$path/%02d $title.mp3",$myrow[1]);
      if (!file_exists($mypath)){
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        system($cmd);
      }
    }
    if ($myrow[0] & M4A){
      $srcdir = MakeURL($cdtag,$myrow[1],'m4a');
      $mypath = sprintf("$path/%02d $title.m4a",$myrow[1]);
      if (!file_exists($mypath)){
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        system($cmd);
      }
    }
  }
}

php?>