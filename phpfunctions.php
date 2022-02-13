<?php

// General purpose PHP functions. No output when loaded
include_once "constants.php";

// almost all mysql queries are performed by this function.

/**
 * @brief Perform mysqli query, check for error, and return result
 * @todo update to place warning in yet-to-be-defined system-wide alert variable
 * @param sql SQL statement to be used
 * @param dbg Optional print sql statement for debugging purposes when there's no error
 */
function doQuery($sql, $dbg = FALSE) {
  global $dbi;
  if (!$result = mysqli_query($dbi, $sql)) {
    echo ("\n<br>Database error: " . mysqli_error($dbi));
    echo "\n<br>Source = $src";
    $dbgt = debug_backtrace();
    echo "\n<br>File = " . $dbgt[0][file] . " Line: " . $dbgt[0][line];
    echo "\n<br>sql = " . $sql;
  } else {
    if ($dbg) {
      echo "\n<br>SQL statement (debug specified): ";
      echo "\n<br>Source = $src";
      $dbgt = debug_backtrace();
      echo "\n<br>File = " . $dbgt[0][file] . " Line: " . $dbgt[0][line];
      echo "\n<br>sql = $sql<br>";
    }
    return ($result);
  }
}

// Handle form variable / cookie. If optional checkbox parameter is true AND we don't have a REQUEST, clear cookie
function handleFormVar($var, $default, $check = false) {
  $fval = $default;

  if ($_REQUEST[$var]) {
    $fval = $_REQUEST[$var];
    setcookie($var, "$fval", time() + 2000000);
  } else {
    // If it's an unset checkbox, don't retrieve cookie
    if ($check) {
      $fval = "";
      setcookie($var, "$fval", time() + 2000000);
    } else {
      // No form variable and not a checkbox. If cookie, retrieve it
      if ($_COOKIE["$var"]) {
        $fval = $_COOKIE["$var"];
      }
    }
  }
  return ($fval);
}

// open master database if not already opened.
// Check whether we're on cld.home network or outside
function getjukeboxdb() {

  global $dbi;

  // mysqli transition
  $dbi = mysqli_connect("localhost", "wkuhns", "", "jukebox");
  mysqli_query($dbi, 'SET NAMES utf16');
  $query = "set session sql_mode = 'traditional'";
  mysqli_query($dbi, $query);
}

function isEmptyDir($dir) {
  return (($files = @scandir($dir)) && count($files) <= 2);
}
function getnewalbumid() {
  $i = 1;
  $query = "select uid from album order by uid";
  echo "$query\n";
  $result = doquery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    if ($myrow[0] != $i) {
      continue;
    } else {
      $i++;
    }
  }
  return $i;
}

function makeMyDir($cdtag) {
  $cdtag = strtolower($cdtag);
  $d1 = substr($cdtag, 0, 1);
  $d2 = substr($cdtag, 1, 1);
  $d3 = substr($cdtag, 2, 1);

  // echo "$cdtag - $d1 $d2 $d3<br>\n";

  // Need to see if directory exists, create it if not
  if (!file_exists("/music/$d1")) {
    system("mkdir /music/$d1");
    chown("/music/$d1", "www-data");
    chgrp("/music/$d1", "webusers");
    chmod("/music/$d1", 0775);
  }
  if (!file_exists("/music/$d1/$d2")) {
    system("mkdir /music/$d1/$d2");
    chown("/music/$d1/$d2", "www-data");
    chgrp("/music/$d1/$d2", "webusers");
    chmod("/music/$d1/$d2", 0775);
  }
  if (!file_exists("/music/$d1/$d2/$d3")) {
    system("mkdir /music/$d1/$d2/$d3");
    chown("/music/$d1/$d2/$d3", "www-data");
    chgrp("/music/$d1/$d2/$d3", "webusers");
    chmod("/music/$d1/$d2/$d3", 0775);
  }
  return '/music/$d1/$d2/$d3';
}
function BaseURL($cdtag) {
  $cdtag = strtolower($cdtag);
  $s1 = substr($cdtag, 0, 1);
  $s2 = substr($cdtag, 1, 1);
  $s3 = substr($cdtag, 2, 1);
  $baseurl = sprintf("/music/%s/%s/%s", $s1, $s2, $s3);
  return ($baseurl);
}
function MakeURL($cdtag, $track, $ext) {
  $url = sprintf("%s/audio_%02d.%s", BaseURL($cdtag), $track, $ext);
  return ($url);
}
function MakeRoot($cdtag) {
  $cdtag = strtolower($cdtag);
  $url = sprintf("/music/%s/%s/%s/", substr($cdtag, 0, 1), substr($cdtag, 1, 1), substr($cdtag, 2, 1));
  return ($url);
}

$nptext_sql_flds = "track.uid as trackid,
	track.title as ttitle,
	album.uid as albumid,
	album.title as atitle,
	artist.dispname as adisp,
	artist.lastname as alast,
	cscore,
	artist.uid as artistid,
	ratings.pscore";

function nptext($myrow, $collid) {
  // Text for 'Now Playing'
  // select track.uid,track.title,album.uid,album.title,dispname,artist.lastname,rating......
  $lastname = urlencode($myrow[5]);
  $nptext = "<table><tr><td align=center><i>Now Playing: </i><b>$myrow[1]</b>";
  $nptext .= " on <a class=lsblue href=javascript:MakeAlbumWindow($myrow[2],'n')>$myrow[3]</a>";
//  $nptext .= " by <a class=menu href=index.php?srch=$lastname&view=home&allalbums=on&detail=all>$myrow[4]</a></td></tr>";
  $nptext .= " by <a class=lsblue href=index.php?view=artists&subview=artist&artistid=$myrow[7]>$myrow[4]</a></td></tr>";
  // Clickable rating links
  if ($myrow[6] != "") {
    $nptext .= "<tr><td align=center><table><tr><td>Rating: </td>";
    for ($rate = 0; $rate <= 100; $rate += 10) {
      $bclass = abs($rate - $myrow[6]) < 6 ? 'class=white' : 'class=blue';
      $bgc = abs($rate - $myrow[6]) < 6 ? 'blue' : 'white';
      $nptext .= "<td bgcolor=$bgc align=center onclick=shellcmd('rate=$rate&uid=$myrow[0]&collid=$collid')><b $bclass>$rate</b></td>";
    }
    $nptext .= "</tr></table></td></tr>";
  }
  $nptext .= "</table>";
  return $nptext;
}
function playtracks($result, $collid, $extchoice = 'wav') {
  $outfile = fopen("/home/music/scripts/playlist", "w");
  // fwrite ( $outfile, "$result, $collid, $extchoice" );
  while ($myrow = mysqli_fetch_array($result)) {
    // may have several files - play wav if present, m4p next, mp3 last.
    $ftype = $myrow[ftype];
    $url = $myrow[url];
    // Get 'Now Playing' text.....
    $nptext = nptext($myrow, $collid);
    // Write command to write np.txt file to output file
    fwrite($outfile, "echo \"$nptext\" > /tmp/np.txt ;");
    // if $extchoice is specified, play only that extension. Force $ext
    switch ($extchoice) {
    case 'wav':
      // Asked for wav - do we have it?
      if ($ftype & 1) {
        $ext = 1;
        $url = substr($url, 0, -3) . 'wav';
        break;
      }
    case 'mp3':
      if ($ftype & 2) {
        $ext = 2;
        $url = substr($url, 0, -3) . 'mp3';
        break;
      }
    case 'm4a':
      if ($ftype & 4) {
        $ext = 4;
        $url = substr($url, 0, -3) . 'm4a';
        break;
      }
    case 'wma':
      if ($ftype & 8) {
        $ext = 8;
        $url = substr($url, 0, -3) . 'wma';
        break;
      }
    }
    if ($ext == 1) {
      //$cmd = sprintf("taskset -c 0 play -v%02.2f %s\n", $myrow[volume], $url);
      $cmd = sprintf("play -v%02.2f %s\n", $myrow[volume], $url);
      fwrite($outfile, $cmd);
    } else {
      if ($ext == 4 || $ext == 8) {
        fwrite($outfile, "taskset -c 0 mplayer -vo null $url\n");
      } else {
        if ($ext == 2) {
          fwrite($outfile, "taskset -c 0 mpg123 $url\n");
        } else {
          fwrite($outfile, "WTF? $ext $url\n");
        }
      }
    }
  }

  fclose($outfile);
  // $cmd = "/home/music/scripts/playlist > /dev/null 2>&1 &";
  $cmd = "/home/music/scripts/playlist > /dev/null 2>&1";
  system('export HOME="/var/www"');
  system("$cmd");
  // exit;
}
function mp3path() {

  // See if MP3 player is mounted
  exec('mount | grep mp3', $junk, $ret);
  $mp3mounted = !$ret;

  if ($mp3mounted) {
    array(
      $chunks,
    );
    $chunks = explode(" ", $junk[0]);
    $ipath = $chunks[2];
    return ($ipath);
  } else {
    return ('');
  }
}
function mp3device($ipath) {
  if ($ipath != '') {
    $ipodctl = $ipath . "/iPod_Control";
    if ($ipod = file_exists($ipodctl)) {
      return ("ipod");
    } else {
      return ("mp3");
    }
  } else {
    return ('');
  }
}
function mp3space($ipath) {
  $df = ceil(disk_free_space("$ipath") / 1024 / 1024);
  $nt = ceil($df / 3);
  return ($nt);
}
function deleteipodtrack($ipath, $trackid) {
  $outfile = fopen("/tmp/jblog.txt", "w");
  $cmd = "gnupod_search -m $ipath -id=\"^$trackid\$\" --delete";
  fwrite($outfile, "$cmd\n");
  exec($cmd);
  $db = getjukeboxdb($db);
  $query = "delete from mp3player where playertrack=$trackid";
  fwrite($outfile, "$query\n");
  $result = doQuery($query);
  fclose($outfile);
}

// See whether MP3 player is mounted. Echo 'none' or MP3 player type.
function mp3check() {
  $ipath = mp3path();
  $device = mp3device($ipath);

  if ($device == '') {
    $mp3count = 0;
    echo "none:";
    exit();
  }

  $nt = mp3space($ipath);

  if ($device == 'ipod') {
    echo "iPod:$nt";
    getipodtracks($ipath);
  } else {
    echo "MP3:$nt";
  }
}
function ipodserial($ipath) {
  $ixml = `grep -A1 SerialNumber $ipath/iPod_Control/Device/SysInfoExtended`;
  $slines = explode("\n", $ixml);
  $iserial = substr($slines[1], 8);
  $iserial = substr($iserial, 0, strlen($iserial) - 9);
  return ($iserial);
}
function getipodtracks($ipath) {
  $outfile = fopen("/tmp/git", "w");

  $iserial = ipodserial($ipath);
  $icontents = `gnupod_search --view=iuatl -m $ipath `;
  $tracks = explode("\n", $icontents);

  for ($i = 3; $i < sizeof($tracks) - 1; $i++) {
    $track = $tracks[$i];
    // echo "$track<br>";
    fwrite($outfile, "$ipath $track\n");
    list($id, $url, $artist, $title, $album) = explode("|", $track);
    $artist = trim($artist);
    $title = trim($title);
    $album = trim($album);
    $url = trim($url);
    $query = "select playertrack,url,song,artist,album from mp3player where playerid=\"$iserial\" and playertrack=$id and song = \"$title\"";

    $result = doQuery($query);
    if (mysqli_num_rows($result) == 0) {
      $query = "insert into mp3player(playerid,playertrack,url,song,artist,album) values (\"$iserial\",$id,\"$url\",\"$title\",\"$artist\",\"$album\")";
      $result = doQuery($query);
    }
  }
  $query = "update mp3player,track,artist,listitem,album set mp3player.trackuid=track.uid
    where song=track.title and artist.uid=track.artistid and artist.dispname=mp3player.artist and track.uid=listitem.trackid and album.uid=listitem.albumid and album.status & 128 = 0 and album.title=mp3player.album";
  fclose(outfile);
}
function makemp3track($outfile, $device, $ipath, $ti, $pltitle) {
  // $outfile = fopen("/home/music/scripts/mp3list","a");
  // Get parent album (selection may have come from playlist)
  $query = "select url,track.title,track.seq,album.title,dispname,track.bgenre,album.status,cdtag,track.ftype
    FROM album,listitem,track,artistlink,artist
    WHERE listitem.albumid=album.uid
    and track.uid=listitem.trackid
    and artistlink.artistid=artist.uid
    and artistlink.albumid=album.uid
    and track.uid=$ti
    and album.status & " . PLAYLIST . " = 0";
  // fwrite($outfile, $query);
  $result = doQuery($query);
  $myrow = mysqli_fetch_row($result);
  // Check if mp3 file already exists
  $seq = sprintf("%05d", $myrow[2]);
  $mp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'm4a';
  if (!file_exists($mp3file)) {
    $mp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'mp3';
    $ext = 'mp3';
  } else {
    $ext = 'm4a';
  }
  // No mp3 or m4a. Could be wav or wma. In either case, make mp3.
  if (!file_exists($mp3file)) {
    $mp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'mp3';
    $srcfile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wav';
    if (file_exists($srcfile)) {
      fwrite($outfile, "lame --tt \"$myrow[1]\" --tl \"$myrow[3]\" --ta \"$myrow[4]\" --tn \"$myrow[2]\" $myrow[0] \"$mp3file\"\n");
      $query = "update track set ftype = ftype | " . MP3 . " where uid = $ti";
      $result2 = doQuery($query);
    } else {
      $srcfile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wma';
      if (file_exists($srcfile)) {
        fwrite($outfile, "ffmpeg -i \"$srcfile\" -ab 192k -map_metadata 0 -id3v2_version 3 -write_id3v1 1 \"$mp3file\"\n");
        $query = "update track set ftype = ftype | " . MP3 . " where uid = $ti";
        $result2 = doQuery($query);
      }
    }
    $ext = 'mp3';
  }
  if ($device == "ipod") {
    $cover = '';
    if ($myrow[6] & COVER) {
      $cover = "--artwork " . substr($myrow[0], 0, strlen($myrow[0]) - 12) . 'cover.jpg';
    }
    if ($pltitle == '') {
      fwrite($outfile, "gnupod_addsong -m $ipath $cover $mp3file\n");
    } else {
      fwrite($outfile, "gnupod_addsong -m $ipath $cover -p \"$pltitle\" $mp3file\n");
    }
  } else {
    fwrite($outfile, "cp $mp3file $ipath/$myrow[7]$seq.$ext\n");
  }
}
function move_track($src, $dest, $outfile) {
  if (!file_exists($dest)) {
    $cmd = "mv $src $dest";
    system($cmd);
  } else {
    $warning = "ERROR: Can't move $src - Destination file $dest already exists\n";
    echo $warning;
    exit();
  }
}
function getartist($artist, $create) {
  global $dbi;
  $artist = trim($artist);
  // Try to find matching artist. Create artist if none AND create is set
  $sqlartist = addslashes($artist);
  $query = "select uid,dispname from artist where dispname = '$sqlartist'";
  //echo "$query<br>\n";
  $result = doQuery($query);
  if ($myrow = mysqli_fetch_row($result)) {
    echo "artist [$myrow[1]] matches\n";
    return $myrow[0];
  }
  if ($create) {
    $f = strpos($artist, " ");
    $first = addslashes(substr($artist, 0, $f));
    $last = addslashes(trim(substr($artist, $f)));
    $artist = addslashes($artist);
    //echo "and no jukebox artist matches. Creating artist [$first] [$last].\n";
    $query = "insert into artist (firstname,lastname,dispname) values (\"$first\",\"$last\",\"$artist\")";
    echo "$f $query<br>\n";
    $result = doQuery($query);
    return mysqli_insert_id($dbi);
  } else {
    return (0);
  }
}
function sanitize($string = '') {
  // Replace all weird characters with dashes
  // $string = preg_replace('/[^\w\-~_\.]+/u', '-', $string);
  $string = str_replace("&", "and", $string);
  $string = preg_replace('/[^\w\-~_\.]+/u', ' ', $string);

  // Only allow one dash separator at a time (and make string lowercase)
  // return mb_strtolower(preg_replace('/--+/u', '-', $string), 'UTF-8');
  $string = preg_replace('/--+/u', '-', $string);
  $string = str_replace(".", "", $string);
  return trim($string);
}

function deleteAlbum($uid) {
  // Could be album, could be playlist
  // For albums, delete tracks and all listitems that point to them
  // Let's be very sure that it's an album before we delete tracks....
  $query = "select status from album where uid=$uid";
  $result = doquery($query);
  $myrow = mysqli_fetch_row($result);
  $pl = $myrow[0] & PLAYLIST;

  if (!$pl == PLAYLIST) {
    // Get tracks and listitems pointing to them
    $query = "select track.uid from listitem,track
      where listitem.trackid=track.uid
      and listitem.albumid = $uid";
    // echo "$query<br>\n";
    $result = doquery($query);
    while ($myrow = mysqli_fetch_row($result)) {
      $query = "delete from listitem where trackid=$myrow[0]";
      doquery($query);
      $query = "delete from track where uid=$myrow[0]";
      doquery($query);
      $query = "delete from mp3file where trackid=$myrow[0]";
      doquery($query);
      $query = "delete from talink where tid=$myrow[0]";
      doquery($query);
    }
    // Get cdtag and delete files (actually, move to import/trash)
    $query = "select cdtag from album where uid=$uid";
    $result = doquery($query);
    $myrow = mysqli_fetch_row($result);
    $path = MakeRoot($myrow[0]);
    $cmd = "mkdir /music/trash/$myrow[0]; mv $path* /music/trash/$myrow[0]/";
    echo "$cmd<br>\n";
    system($cmd);
  } // In both cases, delete album and artistlinks
  $query = "delete from album where uid=$uid";
  // echo "$query<br>\n";
  doquery($query);
  $query = "delete from artistlink where albumid=$uid";
  // echo "$query<br>\n";
  doquery($query);
  $query = "delete from listitem where albumid=$uid";
  // echo "$query<br>\n";
  doquery($query);
  $query = "delete from alistitem where albumid=$uid";
  // echo "$query<br>\n";
  doquery($query);
}

function createAlbum($title, $statusmask, $genre, $artistid) {

  $albumUid = getnewalbumid();
  $title = addslashes($title);
  $query = "INSERT INTO album (uid,title,status,genre1) values ($albumUid,\"$title\",$statusmask,$genre)";
  doQuery($query);
  echo "$query<br> \n";

  // Fix cdtag
  $cdtag = chr($albumUid % 26 + 65) . chr(($albumUid / 676) + 66) . chr((($albumUid % 676) / 26) + 65);
  $query = "update album set cdtag='$cdtag' where uid=$albumUid";
  doQuery($query);

  // Create artistlink
  $query = "insert into artistlink values (NULL,$artistid,$albumUid,1)";
  doQuery($query);

  // Create alistitem to add album to 'all playlists' or 'All Albums'
  if ($statusmask & PLAYLIST) {
    $query = "insert into alistitem (alistid,albumid) values (2,$albumUid)";
  } else {
    $query = "insert into alistitem (alistid,albumid) values (1,$albumUid)";
  }
  doQuery($query);
  return $albumUid;
}
