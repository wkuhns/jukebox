<?php
include '../sessionheader.php';
include '../constants.php';

require_once('/home/httpd/html/jukebox2/getid3/getid3.php');

// Go through mp3file and attempt to set relevant fields, making the best of ID3V1 and ID3V2 
// as well as any inferences from linked track / album / artist records

// This ONLY updates mp3file, which was originally built by various versions of build_mp3_db.php
// In the future this logic should be incorporated into build_mp3_db.php

$query = "select 
  mp3file.uid,file,artist,album,mp3file.title,mp3file.track,
  artist.dispname,album.title,track.title,track.seq,
  a2.dispname,album.title,track.title,listitem.seq,
  track.ftype & " . WAV . "
  from mp3file left join track on track.uid=mp3file.trackid
  left join artist on track.artistid=artist.uid
  left join listitem on listitem.trackid=track.uid
  left join album on album.uid=listitem.albumid and album.status & " . PLAYLIST . "= 0
  left join artistlink on artistlink.albumid=album.uid
  left join artist as a2 on a2.uid=artistlink.artistid
  where mp3file.status = 0";
  echo $query;
$result = doquery($query);

echo "<table>";
echo "<tr>
  <th>Source</th>
  <th>Artist</th>
  <th>Album</th>
  <th>Title</th>
  <th>Track</th>
  </tr>\n";

while($mp3track = mysql_fetch_row($result)){
  $getID3 = null;
  $getID3 = new getID3;
  $getID3->option_no_iconv = true;

  // Analyze file and store returned data in $ThisFileInfo
  $ThisFileInfo = $getID3->analyze("$mp3track[1]");
  //$getID3->encoding = "ISO8859-1";
  //$getID3->option_no_iconv = true;
  //echo "<pre>";
  //print_r($ThisFileInfo);
  //echo "</pre>";
  //exit;

  // Data from mp3file table
  $mp3_artist = $mp3track[2];
  $mp3_album = $mp3track[3];
  $mp3_title = $mp3track[4];
  $mp3_track = $mp3track[5];

  // Data from jukebox 'track' record
  $trk_artist = $mp3track[6];
  $trk_album = $mp3track[7];
  $trk_title = $mp3track[8];
  $trk_track = $mp3track[9];

  if ($trk_album == "Unknown Album"){
    $trk_album = '';
  }
  if ($trk_artist == "Unknown Artist"){
    $trk_artist = '';
  }

  // Data from jukebox parent album
  $alb_artist = $mp3track[10];
  $alb_album = $mp3track[11];
  $alb_title = $mp3track[12];
  $alb_track = $mp3track[13];

  if ($alb_album == "Unknown Album"){
    $alb_album = '';
  }
  if ($alb_artist == "Unknown Artist"){
    $alb_artist = '';
  }

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

  if (strlen($id2_track) > 2){
    $id2_track = substr($id2_track,0,2);
  }
  $id2_track = $id2_track + 0;

  // Pick best of bunch: Title
  // Longest is probably best, but nonzero 1d3v2 wins
  $title = $mp3_title;
  if (strlen($trk_title) > strlen($title)){
    $title = $trk_title;
  }
  if (strlen($id1_title) > strlen($title)){
    $title = $id1_title;
  }
  if (strlen($id2_title) > 0){
    $title = $id2_title;
  }

  // Pick best of bunch: Album
  // Longest is probably best
  $album = $mp3_album;
  if (strlen($trk_album) > strlen($album)){
    $album = $trk_album;
  }
  if (strlen($id1_album) > strlen($album)){
    $album = $id1_album;
  }
  if (strlen($id2_album) > 0){
    $album = $id2_album;
  }

  // Pick best of bunch: Artist
  // track is probably best
  $artist = $mp3_artist;
  if (strlen($id1_artist) > strlen($artist)){
    $artist = $id1_artist;
  }
  if (strlen($trk_artist) > 0){
    $artist = $trk_artist;
  }
  if (strlen($id2_artist) > 0){
    $artist = $id2_artist;
  }


  // Pick best of bunch: Track Number
  // If no wav file, ID2 is best (if non-zero)
  $track = $trk_track;
  if ($mp3track[14] == 0 || $mp3track[14] == ''){
    if (is_numeric($id2_track) && $id2_track != 0){
      $track = $id2_track;
    }
  }

  if($album == $mp3_album && $title == $mp3_title && $artist == $mp3_artist && $track == $mp3_track){
    $query = "update mp3file set status = 1 where uid = $mp3track[0]";
  }else{
    echo "<tr><td colspan=5>$mp3track[1]</td></tr>\n";
    echo "<tr>
      <td>Album</td>
      <td>$alb_artist</td>
      <td>$alb_album</td>
      <td>$alb_title</td>
      <td>$alb_track</td>
      </tr>\n";

    echo "<tr>
      <td>Track</td>
      <td>$trk_artist</td>
      <td>$trk_album</td>
      <td>$trk_title</td>
      <td>$trk_track</td>
      </tr>\n";

    echo "<tr>
      <td>mp3 tbl</td>
      <td><b>$mp3_artist</b></td>
      <td><b>$mp3_album</b></td>
      <td><b>$mp3_title</b></td>
      <td><b>$mp3_track</b></td>
      </tr>\n";

    echo "<tr>
      <td>ID3V1</td>
      <td>$id1_artist</td>
      <td>$id1_album</td>
      <td>$id1_title</td>
      <td>$id1_track</td>
      </tr>\n";

    echo "<tr>
      <td>ID3V2</td>
      <td>$id2_artist</td>
      <td>$id2_album</td>
      <td>$id2_title</td>
      <td>$id2_track</td>
      </tr>\n";

    echo "<tr>
      <td>Chosen</td>
      <td><b>$artist</b></td>
      <td><b>$album</b></td>
      <td><b>$title</b></td>
      <td><b>$track</b></td>
      </tr>\n";

    echo "<tr><td colspan=5><hr></td></tr>\n";
    $title = str_replace('"',"'",$title);
    $album = str_replace('"',"'",$album);
    $artist = str_replace('"',"'",$artist);
    if ($track == ''){
      $track=0;
    }
    $query = "update mp3file set 
      status = 1, 
      artist = \"$artist\", 
      album = \"$album\", 
      title = \"$title\", 
      seq = $track 
      where uid = $mp3track[0]";
  }
  //echo "<tr><td colspan=5>$query</td></tr>\n";
  doquery($query);
}