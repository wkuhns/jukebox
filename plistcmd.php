<?php
// Collection of playlist functions that don't generate HTML output

include_once ("sessionheader.php");
//header("HTTP/1.0 204 No Response");

$outfile = fopen("/tmp/jblog.txt","w");

//print_r($_GET);

// This is the currently selected playlist. Might NOT be the one that the user has clicked on in album window.
$plistid = $_COOKIE["plistid"];

// This is the ID of the album in the album window where the click originated. May be same as $plistid.
$albumid = $_GET['albumid'];

// Safety check: $plistid MUST be a playlist. Also see whether $albumid is.

$sql = "select pl.status, album.status from album, album as pl where pl.uid=$plistid and album.uid=$albumid";
$result = doQuery($sql);
$myrow = mysqli_fetch_row($result);
$plist_is_plist = $myrow[0] & 128;
$album_is_plist = $myrow[1] & 128;
if($plist_is_plist == 0){
  echo "Playlist $plistid is not a playlist";
  exit;
}

// Called from album window. Add tracks to playlist
if ($_GET[plcmd] == "AddSelected"){
  // How many items already? Set counter
  $query = "select max(listitem.seq) from listitem,album where album.uid = listitem.albumid and album.uid=$plistid";
  //echo $query;
  $result = doQuery($query);
  $myrow = mysqli_fetch_row($result);
  $i = $myrow[0];
  foreach ($_GET[trackid] as $ti) {
    // Check for dup
    $query = "select trackid from listitem where albumid=$plistid and trackid=$ti";
    $duptrk = doQuery($query);
    if(mysqli_num_rows($duptrk) == 0){  
      $i++;
      $sql = "Insert into listitem (trackid,albumid,seq) values ($ti,$plistid,$i)";
      fwrite($outfile,$sql);
      doQuery($sql);
      //echo "$sql<br>";
    }
  }
}


// Delete selected tracks from playlist
if ($_GET[plcmd] == "DeleteSelected"){
  foreach ($_GET[trackid] as $ti) {
    $sql = "DELETE FROM listitem where albumid=$albumid and trackid=$ti";
    //echo $sql;
    fwrite($outfile,$sql);
    doQuery($sql);
  }
}

// Delete all tracks from playlist
if ($_GET[plcmd] == "Clear"){
  $sql = "Delete from listitem where albumid=$albumid";
  //echo $sql;
  doQuery($sql);
}

fclose($outfile);

exit;

// fetch playlist data
  $aresult = doQuery("select 
    title,bgenre,cdslot,dispname,album.uid,prisource,comment,tnum,tseries,albumflag,cdflag,(bgenre & 1073741824),source,artist.uid 
    FROM album,artistlink,artist 
    where artist.uid=artistlink.artistid 
    and album.uid=artistlink.albumid 
    and cdtag = '$pltag' 
    order by lastname,firstname,title");
      //echo "$query<br>";
  
  $arow = mysqli_fetch_row($aresult);
  $albumid = $arow[4];
  $playlistname = $arow[0];
  $artistid = $arow[13];
  $prisource = $arow[5];
  echo "<h2>$arow[0]</h2>\n";
  
  // Get and display tracks
  //$query = "select title,listitem.seq,url,dispname,track.uid,artistid,duration,wav,mp3,volume FROM track,listitem,artist where track.uid=listitem.trackid and artist.uid=track.artistid and listitem.albumid = $albumid order by listitem.seq";

  $query = "select title,listitem.seq,url,dispname,track.uid,artistid,track.duration,wav,mp3,volume FROM track,listitem,artist where track.uid=listitem.trackid and artist.uid=track.artistid and listitem.albumid = $albumid order by lastname,firstname,title";

  //echo "$query<br>\n";

  $result = doQuery($query);
  
  // Form for playlist updates
  echo "<form name=plform action=playlist.php method=get target=plwindow>\n";
  echo "<table border=0 cellpadding=0 cellspacing=0 width=100%>\n";
  echo "<input type=hidden value=$pltag name=pltag>\n"; 
  echo "<input type=hidden value=$pltag name=cdtag>\n"; 
  echo "<input type=hidden name=hdr value=no>\n";
  echo "<tr>\n";
  $bgc='#dce4f9';
  echo "<td colspan=6 class=padded bgcolor=$bgc><hr></td>\n";
  echo "</tr>\n";
  
  // Show tracks within form
  $pltime = 0;
  $bgc='white';
  while($myrow = mysqli_fetch_row($result)){
  
    printf("<tr>\n");
    // Play buttons
    if ($myrow[7] == 1){
      printf("<td class=small bgcolor=$bgc width=20><a href=\"/jukebox/shellcmd.php?volume=%02.2f&play=%s\">",$myrow[9],$myrow[2]);
      printf("<img border=0 src=\"note.gif\"></a></td>\n");
    }else{
      printf("<td class=small bgcolor=$bgc width=20>&nbsp;</td>\n");
    }
    $base = substr($myrow[2],1,18);
    $mp3 = '/' . $base . '.mp3';
    if ($myrow[8] == 1){
      printf("<td class=small bgcolor=$bgc width=20><a href=\"/jukebox/shellcmd.php?play=%s\">",$mp3);
      printf("M</a></td>\n");
    }else{
      printf("<td class=small bgcolor=$bgc width=20>&nbsp;</td>\n");
    }
  
    printf("<td class=small bgcolor=$bgc width=20>%s</td>\n",$myrow[1]);
    printf("<td class=padded bgcolor=$bgc width=20><input type=checkbox name=trackid[] value=%s></td>\n",$myrow[4]);
    
    // if track artist <> album artist, display....
    if (!strcmp($artistid,$myrow[5])){
      printf("<td class=small  bgcolor=$bgc>%s</td>",$myrow[0]);
    }else{
      printf("<td class=small  bgcolor=$bgc>%s (%s)</td>",$myrow[0],$myrow[3]);
    }
    
    //Display track play time
    $mins = sprintf("%2d",$myrow[6]/60);
    $secs = $myrow[6]-$mins*60;
    printf("<td  class=small align=right bgcolor=$bgc width=50>%s:%02s</td></tr>\n",$mins,$secs);
    
    $pltime += $myrow[6];
    $playlist = $myrow[7];
    if ($bgc == 'white'){
      $bgc='#dce4f9';
    }else{
      $bgc='white';
    }
  }
  
