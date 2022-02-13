<?php
// Collection of functions that either don't generate HTML output or involve shell commands
include "sessionheader.php";

// Skip currently playing track
if ($_GET[Eject] == 'x') {
  // header("HTTP/1.0 204 No Response");
  // system("echo `ps aux | grep scripts/playlist | grep -v grep ` > /tmp/log");
  system("kill `ps aux | grep scripts/playlist | grep -v grep | head -1 | cut -c9-16` 2> /dev/null");
  system("killall sox mplayer mpg123 play 2> /dev/null");
  $outfile = fopen("/tmp/np.txt", "w");
  fwrite($outfile, "-- No Music Playing --");
  fclose($outfile);
  exit();
}

// Kill playlist altogether
if ($_GET[Skip] == 'x') {
  // header("HTTP/1.0 204 No Response");
  system("killall sox mpg123 play mplayer 2> /dev/null");
  // system("killall mpg123 2> /dev/null");
  // system("killall play 2> /dev/null");
  exit();
}

if ($_GET[rate]) {

  $query = "UPDATE ratings set cscore=$_GET[rate]
  where trackid = '$_GET[uid]' and collid=$_GET[collid]";

  // $query ="UPDATE track set rating=$_GET[rate] where uid = '$_GET[uid]'";
  // $outfile = fopen("/tmp/jblog.txt","w");
  // fwrite($outfile,$query);
  // fclose($outfile);
  $tresult = doquery($query);
  $query = "select $nptext_sql_flds
  FROM album,listitem,track left join artist on artist.uid=track.artistid
  left join ratings on track.uid=ratings.trackid and ratings.collid=$_GET[collid]
  where listitem.albumid=album.uid and listitem.trackid=track.uid
  and (album.status & 128) = 0 and track.uid=$_GET[uid]";
  $result = doquery($query);
  $myrow = mysqli_fetch_row($result);
  $nptext = nptext($myrow, $_GET[collid]);
  $outfile = fopen("/tmp/np.txt", "w");
  fwrite($outfile, $nptext);
  fclose($outfile);
}

// Play a selection based on criteria passed as sql selection

if ($_REQUEST[selection]) {

  header("HTTP/1.0 204 No Response");
  system("kill `ps aux | grep playlist | grep -v grep | head -1 | cut -c9-16` 2> /dev/null");
  system("killall mplayer mpg123 play 2> /dev/null");
  if ($_GET[sql]) {
    $query = stripslashes($_GET[sql]);
  }
  if ($_POST[playgenre]) {
    $query .= "and album.genre1 = $_POST[playgenre] order by rand()";
  }

  $outfile = fopen("/tmp/jblog.txt", "w");
  fwrite($outfile, "$query\n");
  fclose($outfile);

  $result = doquery($query, true);
  playtracks($result, $collectionid);
}

// Play a whole album, randomly or sequentially
if ($_GET[album]) {
  system("kill `ps aux | grep playlist | grep -v grep | head -1 | cut -c9-16` 2> /dev/null");
  system("killall mplayer mpg123 play 2> /dev/null");
  $playmode = $_GET[playmode];
  $uid = $_GET[album];
  // $nptext_sql_flds = "track.uid,track.title,album.uid,album.title,dispname,artist.lastname,rating";
  $query = "select $nptext_sql_flds,ftype,volume,url
  FROM album,listitem,track left join artist on artist.uid=track.artistid
  left join ratings on track.uid=ratings.trackid and ratings.collid=$_GET[collid]
  where listitem.albumid=album.uid
  and track.uid=listitem.trackid
  and album.uid = $uid ";

  if ($playmode == 'random') {
    $query .= "and (cscore is null or cscore > 10) order by rand()";
  } else {
    $query .= "and (cscore is null or cscore > 0) order by listitem.seq";
  }
  $outfile = fopen("/tmp/jblog.txt", "w");
  fwrite($outfile, "$query\n");
  fclose($outfile);

  $result = doquery($query);
  playtracks($result, $collectionid);
}

// Play a track (MP3 or wav file)
if ($_GET[play]) {
  system("kill `ps aux | grep playlist | grep -v grep | head -1 | cut -c9-16` 2> /dev/null");
  system("killall mplayer mpg123 play 2> /dev/null");
  $ext = $_GET[play];
  $query = "select $nptext_sql_flds,ftype,volume,url
    FROM album,listitem,track left join artist on artist.uid=track.artistid
    left join ratings on track.uid=ratings.trackid and ratings.collid=$_GET[collid]
    where listitem.albumid=album.uid and listitem.trackid=track.uid and (album.status & 128) = 0 and track.uid=$_GET[uid]";

  $result = doquery($query);
  // If specified extension is 'any'then don't pass it
  if ($ext == 'any') {
    playtracks($result, $collectionid);
  } else {
    playtracks($result, $collectionid, $ext);
  }
}

// Play a track given an extension and url (MP3 or wav file)
if ($_GET[playurl]) {
  system("kill `ps aux | grep playlist | grep -v grep | head -1 | cut -c9-16` 2> /dev/null");
  system("killall mplayer mpg123 play 2> /dev/null");
  $ext = $_GET[playurl];
  $url = $_GET[url];
  $ext = lcase($ext);
  if ($ext == 'wav') {
    // $cmd = "taskset -c 0 play $url > /dev/null 2>&1 &\n";
    $cmd = "taskset -c 0 aplay -D hw:0,3 $url > /dev/null 2>&1  &\n";
  }
  if ($ext == 'm4a') {
    $cmd = "taskset -c 0 mplayer -vo null $url > /dev/null 2>&1  &\n";
  }
  if ($ext == 'mp3') {
    $cmd = "taskset -c 0 mpg123 $url > /dev/null 2>&1  &\n";
  }
  if ($ext == 'wma') {
    $cmd = "taskset -c 0 mplayer -vo null $url > /dev/null 2>&1  &\n";
  }

  system('export HOME="/var/www"');
  system("$cmd");
  $outfile = fopen("/tmp/jblog.txt", "w");
  fwrite($outfile, $cmd);
  fclose($outfile);
}
