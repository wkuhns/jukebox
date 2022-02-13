<?php
function review_track_updates($ftype) {
  // Set proposed artist ID
  if ($ftype == 'import') {
    $query = "update mp3file,artist set t_artistid=artist.uid
    	where t_artist = dispname and t_title is not null
    	and mp3file.file like '/music/import%'";
  } else {
    $query = "update track,artist set t_artistid=artist.uid
    	where t_artist = dispname and t_title is not null'";
  }
  doQuery($query);

  if ($ftype == 'import') {
    $query = "select mp3file.uid, t_artistid, title, t_title, dispname, t_artist
    	from mp3file left join artist on mp3file.artistid=artist.uid
    	where mp3file.file like '/music/import%'
      and mp3file.t_title is not null limit 10";
  } else {
    $query = "select track.uid, t_artistid, title, t_title, dispname, t_artist
    	from track, artist where track.artistid=artist.uid
    	and track.t_title is not null limit 10";
  }
  $result = doQuery($query);
  echo "<table>";
  echo "<tr><th>uid</th><th>artist</th>
    	<th>Old Title</th><th>New Title</th>
    	<th>Old Artist</th><th>New Artist</th>
      <th>OK</th><th>No Match</th>
    </tr>\n";
  while ($myrow = mysqli_fetch_row($result)) {
    $uid = $myrow[0];
    $artistid = $myrow[1];
    $oldtrack = $myrow[2];
    $newtrack = $myrow[3];
    $oldartist = $myrow[4];
    $newartist = $myrow[5];
    echo "<tr><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>
          	<td>
          	<input name=view type=hidden value=utils>
          	<input name=ftype type=hidden value=$ftype>
    			<input name=action type=hidden value=review_track_updates>
          <input name=trackuid type=hidden value=$uid>$uid
          <td><input name=artistuid type=hidden value=$uid>$artistid</td>
          </td>
          	<td>$oldtrack</td>
            <td><input name=newtitle type=text size=50 value=\"$newtrack\"></td>
          	<td>$oldartist</td>
            <td><input name=newartist type=text size=50 value=\"$newartist\"></td>
            <td><input type=checkbox name=allgood onchange=submit()></td>
            <td><input type=checkbox name=nogood onchange=submit()></td>
    </tr></form>\n";
  }
  echo "</table>";
}
function review_artists() {

  $query = "select uid,firstname,lastname,dispname
    from artist
    where status = 0
    order by uid
    limit 25";
  $result = doQuery($query);

  if (mysqli_num_rows($result) > 0) {

    echo "<hr><p>Artist names needing review. If the value in the 'First' column
     should actually be in the 'Last' column (for proper alpha sorting) click 'F->L'.
     When happy, click OK.";

    echo "<table>";
    echo "<tr><th>uid</th><th>First</th><th>Last</th><th>Displayed Name</th><th>Swap</th><th>F->L</th><th>OK</th></tr>\n";
    while ($myrow = mysqli_fetch_row($result)) {
      $first = $myrow[1];
      $last = $myrow[2];
      $disp = $myrow[3];
      echo "<tr><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>
        <td>
        <input name=view type=hidden value=utils>
        <input name=action type=hidden value=artist_review>
      <input name=auid type=hidden value=$myrow[0]>$myrow[0]
      </td>
        <td><input name=first onchange=submit() type=text size=50 value=\"$myrow[1]\"></td>
        <td><input name=last onchange=submit() type=text size=50 value=\"$myrow[2]\"></td>
        <td><input name=disp onchange=submit() type=text size=60 value=\"$myrow[3]\"></td>
        <td><input type=checkbox name=swap onchange=submit()></td>
        <td><input type=checkbox name=unfirst onchange=submit()></td>
        <td><input type=checkbox name=allgood onchange=submit()></td>
      </tr></form>\n";
    }
    echo "</table>";
  }

  // ********************* View unchecked lastname matches ****************************

  $query = "select
    artist.uid,artist.firstname,artist.lastname,artist.dispname,
    a2.uid,a2.firstname,a2.lastname,a2.dispname
    from artist,artist as a2
    where artist.lastname=a2.lastname
    and artist.uid != a2.uid
    and artist.status = 1
    order by artist.lastname
    limit 10";
  $result = doQuery($query);

  if (mysqli_num_rows($result) > 0) {

    echo "<hr><p>Artist names needing review. If the value in the 'First' column
     should actually be in the 'Last' column (for proper alpha sorting) click 'F->L'.
     When happy, click OK.";

    echo "<table>";
    echo "<tr><th>Uid</th><th>First</th><th>Last</th><th>Displayed Name</th><th>F->L</th><th>OK</th></tr>\n";
    while ($myrow = mysqli_fetch_row($result)) {
      $first = $myrow[1];
      $last = $myrow[2];
      $disp = $myrow[3];
      echo "
        <tr>
        <form action=" .
      htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>
        <td>
        <input name=view type=hidden value=utils>
        <input name=action type=hidden value=artist_review>
        <input name=auid type=hidden value=$myrow[0]>$myrow[0]
        </td>
        <td><input name=first onchange=submit() type=text size=50 value=\"$myrow[1]\"></td>
        <td><input name=last onchange=submit() type=text size=50 value=\"$myrow[2]\"></td>
        <td><input name=disp onchange=submit() type=text size=60 value=\"$myrow[3]\"></td>
        <td><input type=checkbox name=unfirst onchange=submit()></td>
        <td><input type=checkbox name=allgood onchange=submit()></td>
        </tr>
        <tr>
        <td>$myrow[4]</td>
        <td>$myrow[5]</td>
        <td>$myrow[6]</td>
        <td>$myrow[7]</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        </tr>
      </form>\n";
    }
    echo "</table>";
  }
}

// Check possible artist / title text in track titles. Extract artist if user selects.

function reviewTrackArtists() {

//  $query = "select track.uid, track.title, trim(substring_index(track.title,'/',1)) as f1, trim(substring_index(track.title,'/',-1)) as f2 from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%' and track.tstatus=0 limit 1";
  $query = "select track.uid, track.title, trim(substring_index(track.title,'/',1)) as f1, trim(substring_index(track.title,'/',-1)) as f2 from track,artist where track.artistid=artist.uid and track.title like '%/%' and track.tstatus=0 limit 1";
  //$query = "select track.uid, track.title, trim(substring_index(track.title,'-',1)) as f1, trim(substring_index(track.title,'-',-1)) as f2 from track,artist where track.artistid=artist.uid and track.title like '%-%' and track.tstatus=0 limit 1";
  $result = doQuery($query);

  if (mysqli_num_rows($result) > 0) {
    $myrow = mysqli_fetch_row($result);
    echo "<hr><p>Track title is <b>'$myrow[1]'</b>.<br> If the artist name is '$myrow[2]' click 'First'. If the artist name is '$myrow[3]' click 'Second'.
     Otherwise, click OK.";
    $f1 = htmlentities($myrow[2], ENT_QUOTES);
    $f2 = htmlentities($myrow[3], ENT_QUOTES);
    echo "[$f2]<br>";
    echo "<form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>
      <input name=view type=hidden value=utils>
      <input name=action type=hidden value=reviewTrackArtists>
      <input name=f1 type=hidden value='$f1'>
      <input name=f2 type=hidden value='$f2'>
      <input name=trackid type=hidden value=$myrow[0]>
      <input type=submit name=rta-btn value=First>
      <input type=submit name=rta-btn value=Second>
      <input type=submit name=rta-btn value=OK>
      </form>\n";
  }

  // ********************* View unchecked lastname matches ****************************

  $query = "select
    artist.uid,artist.firstname,artist.lastname,artist.dispname,
    a2.uid,a2.firstname,a2.lastname,a2.dispname
    from artist,artist as a2
    where artist.lastname=a2.lastname
    and artist.uid != a2.uid
    and artist.status = 1
    order by artist.lastname
    limit 10";
  $result = doQuery($query);

  if (mysqli_num_rows($result) > 0) {

    echo "<hr><p>Artist names needing review. If the value in the 'First' column
     should actually be in the 'Last' column (for proper alpha sorting) click 'F->L'.
     When happy, click OK.";

    echo "<table>";
    echo "<tr><th>Uid</th><th>First</th><th>Last</th><th>Displayed Name</th><th>F->L</th><th>OK</th></tr>\n";
    while ($myrow = mysqli_fetch_row($result)) {
      $first = $myrow[1];
      $last = $myrow[2];
      $disp = $myrow[3];
      echo "
        <tr>
        <form action=" .
      htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>
        <td>
        <input name=view type=hidden value=utils>
        <input name=action type=hidden value=artist_review>
        <input name=auid type=hidden value=$myrow[0]>$myrow[0]
        </td>
        <td><input name=first onchange=submit() type=text size=50 value=\"$myrow[1]\"></td>
        <td><input name=last onchange=submit() type=text size=50 value=\"$myrow[2]\"></td>
        <td><input name=disp onchange=submit() type=text size=60 value=\"$myrow[3]\"></td>
        <td><input type=checkbox name=unfirst onchange=submit()></td>
        <td><input type=checkbox name=allgood onchange=submit()></td>
        </tr>
        <tr>
        <td>$myrow[4]</td>
        <td>$myrow[5]</td>
        <td>$myrow[6]</td>
        <td>$myrow[7]</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        </tr>
      </form>\n";
    }
    echo "</table>";
  }
}
function purge_artists() {
  // Duplicate artists

  $success = true;
  while ($success == true) {
    // Limit to 1 because each match shows up twice
    $query = "SELECT artist.uid,a2.uid
    	from artist,artist as a2
    	where artist.dispname=a2.dispname
    	and artist.uid != a2.uid
      order by artist.uid
    	limit 1";
    $result = doQuery($query);
    if ($myrow = mysqli_fetch_row($result)) {
      delete_artist($myrow[0], $myrow[1]);
      $result = doQuery($query);
      // Rebuild 'Collected Tracks'
      build_collected_tracks($myrow[0]);
      $success = true;
    } else {
      $success = false;
    }
  }
}

// Replace all instances of artist2 with artist 1. Delete artist 2
function delete_artist($a1, $a2) {
  global $ldb;

  deleteCollectedTracks($a2);

  $q2 = "update artistlink set artistid = $a1 where artistid=$a2";
  // echo "$q2<br>\n";
  doQuery($q2);
  $q2 = "update track set artistid = $a1 where artistid=$a2";
  // echo "$q2<br>\n";
  doQuery($q2);
  $q2 = "update talink set aid = $a1 where aid=$a2";
  // echo "$q2<br>\n";
  doQuery($q2);
  $q2 = "update mp3file set artistid = $a1 where artistid=$a2";
  // echo "$q2<br>\n";
  doQuery($q2);
  $q2 = "delete from artist where uid=$a2";
  // echo "$q2<br>\n";
  doQuery($q2);
  // exit;
}
function review_albums() {
  global $ldb;

  $query = "select album.uid,album.title,dispname from album,album as a2, artistlink,artist
    where album.stitle=a2.stitle
    and album.uid != a2.uid
    and album.title != 'Unknown Album'
    and album.title != 'greatest hits'
    and album.title != 'best of'
    and artistlink.albumid=album.uid and artist.uid=artistlink.artistid
    and album.status & " . NONDUP . " = 0
    and album.status & " . PLAYLIST . " = 0
    and a2.status & " . NONDUP . " = 0
    and a2.status & " . PLAYLIST . " = 0
    		limit 1 ";
  //echo "$query<br>\n";
  $result = doQuery($query);

  // If none, look for two unknown albums by same artist
  if (mysqli_num_rows($result) == 0) {
    $query = "select album.uid,album.title,artist.dispname from album,album as a2, artistlink, artistlink as al2, artist
      where album.title = a2.title
      and album.uid != a2.uid
      and artistlink.albumid=album.uid
      and artist.uid=artistlink.artistid
      and al2.albumid=a2.uid
      and artistlink.artistid=al2.artistid
      and album.status & " . NONDUP . " = 0
      and album.status & " . PLAYLIST . " = 0
      limit 1 ";
    //echo "$query<br>\n";
    $result = doQuery($query);
    if (mysqli_num_rows($result) == 0) {
      exit();
    }
    $myrow = mysqli_fetch_row($result);
    $albumid = $myrow[0];
    $title = addslashes($myrow[1]);
    $artist = addslashes($myrow[2]);
    $query = "select album.uid,album.title,dispname from album,artistlink,artist
      where album.title='$title'
      and album.uid != $albumid
      and album.status & " . PLAYLIST . " = 0
      and artistlink.albumid=album.uid
      and artist.uid=artistlink.artistid
      and artist.dispname='$artist'";
    // echo "$query<br>\n";
  } else {
    $myrow = mysqli_fetch_row($result);
    $albumid = $myrow[0];
    $title = addslashes($myrow[1]);
    $artist = $myrow[2];
    // Got results from different artists, not 'unknown album'
    $query = "select album.uid,album.title,dispname from album,artistlink,artist
      where album.stitle=left('$title',30)
      and album.uid != $albumid
      and album.status & " . PLAYLIST . " = 0
      and artistlink.albumid=album.uid and artist.uid=artistlink.artistid";
    //echo "$query<br>\n";
  }
  // echo "$query<br>\n";
  $result = doQuery($query);
  if (mysqli_num_rows($result) == 0) {
    exit();
  }

  // Show first album, with link to mark as nondup ('OK' link)
  echo "<table>
    <tr>
    <td class=menu onclick=silentcmd('index.php?view=utils&action=nondup&albumid=$albumid','y')>OK</td>
    <td><a class=menu href=javascript:MakeAlbumWindow($albumid,'n')>$title</a></td>
    <td>$artist</td>
    </tr>\n";

  // Show albums with matching titles, each with 'combine' link
  while ($myrow = mysqli_fetch_row($result)) {
    echo "<tr>
      <td class=menu onclick=silentcmd('index.php?view=utils&action=merge&albumid=$albumid&dupid=$myrow[0]','y')>Combine</td>
      <td><a class=menu href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[1]</a></td>
      <td>$myrow[2]</td>
      </tr>\n";
  }
}

// Delete any existing 'Collected' playlists for this artist
function deleteCollectedTracks($artistid) {

  $query = "SELECT album.uid
    from album, artistlink
    where album.uid=artistlink.albumid
    and artistlink.artistid=$artistid
    and (album.status & 128) = 128
    and title like \"Collected %\"";
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    echo "Playlist Exists: $myrow[0]<br>";
    deleteAlbum($myrow[0]);
  }
}

function build_collected_tracks($artistid) {
  global $dbi;

  $query = "select dispname from artist where uid=$artistid";
  $result = doQuery($query);
  if ($myrow = mysqli_fetch_row($result)) {
    $artistName = $myrow[0];
  } else {
    echo "Bad artist UID: $artistid<br>";
    return -1;
  }

  // Delete any existing 'Collected' playlists for this artist
  deleteCollectedTracks($artistid);

  // Get all talink records for this artist that don't reference albums by this artist.
  // Get distinct title to minimize duplicates - if there are duplicates, get one with a wav file if possible.
  // Note: it's possible that duplicates are OK - could be different performances. Have to ponder....

  // Probably also want to ignore hits that match a track on an album by the artist -
  // ignore tracks on 'Greatest hits of the 80s' if we have the original.
  $query = "select distinct track.title, listitem.trackid, artist.agenre, track.genre1,
    track.ftype & 1 as wav, artist.dispname
    from artist,artistlink,album,listitem,talink, track
    where artist.uid=artistlink.artistid
    and artistlink.albumid = album.uid
    and album.uid=listitem.albumid
    and track.uid=listitem.trackid
    and talink.tid=listitem.trackid
    and talink.aid = $artistid
    and talink.aid != artist.uid
    and album.status & 128 = 0 group by track.title order by wav desc";
  $result = doQuery($query);

  // we have no collected tracks playlists at this point
  $regularUid = 0;
  $christmasUid = 0;
  $i = 1;

  while ($myrow = mysqli_fetch_row($result)) {
    $trackTitle = $myrow[0];
    $trackUid = $myrow[1];
    $artistGenre = $myrow[2];
    $trackGenre = $myrow[3];
    $ftype = $myrow[4];

    // Is it a Christmas track?
    if ($trackGenre == 129) {
      if ($christmasUid == 0) {
        $statusmask = JUKEBOX | NEWDIR | PLAYLIST;
        $title = "Collected Christmas Tracks: " . $artistName;
        $christmasUid = createAlbum($title, $statusmask, $trackGenre, $artistid);
      }
      $collectionUid = $christmasUid;
    } else {
      // Not a Christmas track
      if ($regularUid == 0) {
        $statusmask = JUKEBOX | NEWDIR | PLAYLIST;
        $title = "Collected Tracks: " . $artistName;
        $regularUid = createAlbum($title, $statusmask, $artistGenre, $artistid);
      }
      $collectionUid = $regularUid;
    }

    // Add listitem
    $query = "Insert into listitem (trackid,albumid,seq) values ($trackUid,$collectionUid,$i)";
    doQuery($query);
    $i++;
    echo "$query<br>";
  }
}

// Mark this album as 'not a duplicate' - presumably it has the same or similar title as another album
function mark_nondup($albumid) {
  global $ldb;

  $query = "update album set status = status | " . NONDUP . " where uid=$albumid";
  doQuery($query);
}
function merge_albums($albumid, $dupid) {

  // For debugging, a place to write output
  $outfile = fopen("/tmp/jblog.txt", "w");

  // Combine albums. Many steps:
  // 1) Are artists different? If so, then artist for combined album should be 'Various'.
  // 2) Are there collection records pointing to second album but not first? If so, repoint them.
  // 3) If there are collection records pointing to both, delete the pointer to the second album.

  // For each track in the second album:
  // 4) Find unused sequence number
  // 5) Verify no files with same target name
  // 6) Move any files for this track, renaming as needed
  // 7) Update 'file' field in track record

  // When done with tracks:
  // 8) Delete second album artistlink
  // 9) Delete any collection links
  // 10) Delete the album record
  // 11) Delete the cover art if any
  // 12) Old dir should be empty - check and delete

  $query = "select cdtag from album where uid=$albumid";
  $result = doquery($query);
  if ($myrow = mysqli_fetch_row($result)) {
    $cdtag1 = $myrow[0];
  } else {
    echo "Invalid album id<br>";
    exit();
  }

  $query = "select cdtag from album where uid=$dupid";
  $result = doquery($query);
  if ($myrow = mysqli_fetch_row($result)) {
    $cdtag2 = $myrow[0];
  } else {
    echo "Invalid dupalbum id<br>";
    exit();
  }

  // 1: Check artists
  $query = "select artist.uid, a2.uid from artist, artist as a2, artistlink, artistlink as al2
  where artistlink.artistid=artist.uid
  and artistlink.albumid=$albumid
  and al2.artistid=a2.uid
  and al2.albumid=$dupid";
  fwrite($outfile, "$query\n");
  $result = doquery($query);
  $myrow = mysqli_fetch_row($result);
  // If artists are not the same update album to 'various'
  if ($myrow[0] != $myrow[1]) {
    $query = "update artistlink,artist set artistlink.artistid=artist.uid
      where artistlink.albumid=$albumid
      and artist.dispname='Various'";
    fwrite($outfile, "$query\n");
    $result = doquery($query);
  }
  // 2: Look for collection records pointing to second album where there is no record for the same collection pointing to the first album
  $query = "select a2.uid
  	from alistitem as a2
  	left join alistitem
  	on alistitem.alistid=a2.alistid
  	and alistitem.albumid=$albumid
  	where a2.albumid=$dupid
  	and alistitem.uid is null";
  fwrite($outfile, "$query\n");
  $result = doquery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $query = "update alistitem set albumid=$albumid where uid=$myrow[0]";
    fwrite($outfile, "$query\n");
    doquery($query);
  }
  // 3: Deferred - we'll get them at step 9

  // ****************** Big loop for each track in second album *****************************

  $query = "select track.uid,track.url, track.ftype from track, listitem where listitem.albumid=$dupid and listitem.trackid=track.uid order by listitem.seq";
  $tracks = doquery($query);
  while ($track = mysqli_fetch_row($tracks)) {

    // 4: Find first unused sequence number in first album
    $i = 1;
    $seq = 0;
    $max = 0;
    $query = "select listitem.seq from listitem where albumid=$albumid order by listitem.seq";
    fwrite($outfile, "$query\n");
    $result = doquery($query);
    while ($myrow = mysqli_fetch_row($result)) {
      if (($myrow[0] != $i) && ($seq == 0)) {
        $seq = $i;
        fwrite($outfile, "Set $seq as sequence number\n");
      }
      if ($myrow[0] > $max) {
        $max = $myrow[0];
      }
      $i++;
    }
    // If $seq is still zero then there were no 'holes' in the track sequence. We'll set it to $max + 1
    if ($seq == 0) {
      $seq = $max + 1;
    }
    fwrite($outfile, "Selected $seq as sequence number\n");
    // Check to make sure that there are no files in the way, then move file(s)
    $query = '';
    if ($track[2] & MP3) {
      $filepath = MakeURL($cdtag1, $seq, 'mp3');
      fwrite($outfile, "Moving $track[1] to $filepath\n");
      move_track($track[1], $filepath, $outfile);
      $query = "update track set seq = $seq, url = '$filepath' where uid=$track[0]";
    }
    if ($track[2] & M4A) {
      $filepath = MakeURL($cdtag1, $seq, 'm4a');
      fwrite($outfile, "Moving $track[1] to $filepath\n");
      move_track($track[1], $filepath, $outfile);
      $query = "update track set seq = $seq, url = '$filepath' where uid=$track[0]";
    }
    if ($track[2] & WAV) {
      $filepath = MakeURL($cdtag1, $seq, 'wav');
      fwrite($outfile, "Moving $track[1] to $filepath\n");
      move_track($track[1], $filepath, $outfile);
      $query = "update track set seq = $seq, url = '$filepath' where uid=$track[0]";
    }
    // 7: Update seq and url field in track record
    fwrite($outfile, "$query\n");
    doquery($query);
    // 7A: update listitem to point to new album
    $query = "update listitem set seq = $seq, albumid=$albumid where trackid=$track[0] and albumid=$dupid";
    fwrite($outfile, "$query\n");
    doquery($query);
  }
  // All done with tracks
  // 8: Delete second album artistlink records
  $query = "delete from artistlink where albumid=$dupid";
  fwrite($outfile, "$query\n");
  doquery($query);

  // 9: Delete second album collection links
  $query = "delete from alistitem where albumid=$dupid";
  fwrite($outfile, "$query\n");
  doquery($query);

  // 10: Delete second album record
  $query = "delete from album where uid=$dupid";
  fwrite($outfile, "$query\n");
  doquery($query);

  // 11: Delete second album cover art if any
  $cdtag2 = strtolower($cdtag2);
  $s1 = substr($cdtag2, 0, 1);
  $s2 = substr($cdtag2, 1, 1);
  $s3 = substr($cdtag2, 2, 1);
  $dir = sprintf("/music/%s/%s/%s/", $s1, $s2, $s3);
  $url = sprintf("/music/%s/%s/%s/cover.jpg", $s1, $s2, $s3);
  if (file_exists($url)) {
    $cmd = "rm $url";
    fwrite($outfile, "$cmd\n");
    system($cmd);
  }
  if (isEmptyDir($dir)) {
    $cmd = "rmdir $dir";
    fwrite($outfile, "$cmd\n");
    system($cmd);
  } else {
    $warning = "ERROR: Directory $dir is not empty\n";
    echo $warning;
    fwrite($outfile, $warning);
    exit();
  }
}
function build_tree($baseurl, $albumid) {
  global $ldb;
  $query = "select dispname, title, cdtag
  from album,artistlink,artist
  where album.uid=$albumid
  and artistlink.albumid=$albumid
  and artist.uid=artistlink.artistid";
  $result = doQuery($query);
  if (!$myrow = mysqli_fetch_row($result)) {
    echo "Error: $query: Invalid album $albumid";
    exit();
  }
  $artist = $myrow[0];
  $album = $myrow[1];
  $cdtag = $myrow[2];
  $artist = sanitize($artist);
  $album = sanitize($album);

  // Create artist directory if needed
  $path = $baseurl . $artist;
  if (!file_exists($path)) {
    echo "mkdir $path<br>\n";
    mkdir($path);
  }

  // Create album directory if needed
  $path = $path . "/" . $album;
  if (!file_exists($path)) {
    echo "mkdir $path<br>\n";
    mkdir($path);
  }

  // Create track symlinks
  $seq = 0;
  $query = "select ftype, listitem.seq, title,
		concat(mid(track.url,8,1),mid(track.url,10,1),mid(track.url,12,1)),
		mid(track.url,20,2)
  	from listitem,track
  	where listitem.albumid=$albumid
  	and listitem.trackid=track.uid";
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $title = sanitize($myrow[2]);
    // If this is a playlist, then cdtag must be constructed from track URL
    if ($myrow[3] != '') {
      $cdtag = $myrow[3];
    }
    if ($myrow[1] != '') {
      $seq = $myrow[1];
    } else {
      $seq++;
    }
    if ($myrow[0] & WAV) {
      $srcdir = MakeURL($cdtag, $myrow[1], 'wav');
      $mypath = sprintf("$path/%02d $title.wav", $myrow[1]);
      if (!file_exists($mypath)) {
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        // system ( $cmd );
      }
    }
    if ($myrow[0] & MP3) {
      $srcdir = MakeURL($cdtag, $myrow[4], 'mp3');
      $mypath = sprintf("$path/%02d $title.mp3", $seq);
      if (!file_exists($mypath)) {
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        system($cmd);
      }
    }
    if ($myrow[0] & M4A) {
      $srcdir = MakeURL($cdtag, $myrow[1], 'm4a');
      $mypath = sprintf("$path/%02d $title.m4a", $myrow[1]);
      if (!file_exists($mypath)) {
        $cmd = "ln -s $srcdir \"$mypath\"";
        echo "$cmd<br>\n";
        // system ( $cmd );
      }
    }
  }
}

// See if files are present
function check_tracks($cdtag, $albumid) {
  global $ldb;
  $query = "select track.uid, ftype, listitem.seq, track.title, track.url from listitem,track
  where listitem.albumid=$albumid
  and listitem.trackid=track.uid";
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $trackid = $myrow[0];
    $ftype = $myrow[1];
    $seq = $myrow[2];
    $title = $myrow[3];
    $trackurl = $myrow[4];

    if ($ftype & WAV) {
      $url = substr($trackurl, 0, strlen($trackurl) - 3) . 'wav';
      if (!file_exists($url)) {
        echo "Missing $title from $cdtag: $url<br>\n";
      }
    }
    if ($ftype & MP3) {
      $url = substr($trackurl, 0, strlen($trackurl) - 3) . 'mp3';
      if (!file_exists($url)) {
        echo "Missing $title from $cdtag: $url<br>\n";
      }
    }
    if ($ftype & M4A) {
      $url = substr($trackurl, 0, strlen($trackurl) - 3) . 'm4a';
      if (!file_exists($url)) {
        echo "Missing $title from $cdtag: $url<br>\n";
      }
    }
  }
}

function makeAllMp3() {
  $query = "select url,track.title,track.seq,album.title,dispname,track.genre1,album.status,cdtag,track.ftype,track.uid
  FROM album,listitem,track,artistlink,artist
  WHERE listitem.albumid=album.uid
  and track.uid=listitem.trackid
  and artistlink.artistid=artist.uid
  and artistlink.albumid=album.uid
  and (track.ftype & 1) = 1 and (track.ftype & 2) = 0
  and album.status & " . PLAYLIST . " = 0";
  // fwrite($outfile, $query);
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $ti = $myrow[9];
    // Check if mp3 file already exists
    //$seq = sprintf ( "%05d", $myrow [2] );
    $m4afile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'm4a';
    $wmafile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wma';
    $mp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'mp3';
    $srcfile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wav';
    $tracktitle = sanitize($myrow[1]);
    $albumtitle = sanitize($myrow[3]);
    $artistname = sanitize($myrow[4]);

    if (!file_exists($mp3file)) {
      if (file_exists($srcfile)) {
        $cmd = "lame --tt \"$tracktitle\" --tl \"$albumtitle\" --ta \"$artistname\" --tn \"$myrow[2]\" --tg \"$myrow[5]\" $myrow[0] \"$mp3file\"\n";
        echo "$cmd <br>\n";
        exec($cmd);
        $ftype = WAV | MP3;
        if (file_exists($wmafile)) {
          $ftype = $ftype | WMA;
        }
        if (file_exists($m4afile)) {
          $ftype = $ftype | M4A;
        }
        $query = "update track set ftype = $ftype where uid = $ti";
        echo "$query <br>";
        doQuery($query);
      }
    } else {
      // MP3 exists
      $query = "update track set ftype = ftype | 2 where uid = $ti";
      echo "$query <br>";
      doQuery($query);
    }
  }
}

function fixFileType() {
  $query = "select url, uid, ftype from track";
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $m4afile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'm4a';
    $wmafile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wma';
    $mp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'mp3';
    $capsmp3file = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'MP3';
    $wavfile = substr($myrow[0], 0, strlen($myrow[0]) - 3) . 'wav';
    $ftype = 0;
    $url = $myrow[0];
    if (file_exists($wmafile)) {
      $ftype = $ftype | WMA;
      $url = $wmafile;
    }
    if (file_exists($m4afile)) {
      $ftype = $ftype | M4A;
      $url = $m4afile;
    }
    if (file_exists($capsmp3file)) {
      rename($capsmp3file, $mp3file);
      echo "Reanmed $capsmp3file to $mp3file<br>";
    }
    if (file_exists($mp3file)) {
      $ftype = $ftype | MP3;
      $url = $mp3file;
    }
    if (file_exists($wavfile)) {
      $ftype = $ftype | WAV;
      $url = $wavfile;
    }
    if ($ftype != $myrow[2]) {
      echo "Track $myrow[0] old ftype $myrow[2], new ftype $ftype<br>";
      $query = "update track set ftype=$ftype, url=\"$url\" where uid=$myrow[1]";
      echo "$query<br>";
      doQuery($query);
    }
  }
}

// Identify artist on 'various' track
function findTrackArtist() {
  // Track has '/' - is artist named first?
  $query = "select trim(substring_index(track.title,'/',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '/' - is artist named second?
  $query = "select trim(substring_index(track.title,'/',-1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '-' - is artist named first?
  $query = "select trim(substring_index(track.title,'-',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%-%'";
  changeTrackArtist($query);

  // Track has '-' - is artist named second?
  $query = "select trim(substring_index(track.title,'-',-1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%-%'";
  changeTrackArtist($query);

  // Track has '/' and first field has 'And' - see if we can give credit to the first artist
  $query = "select trim(substring_index(trim(substring_index(track.title,'/',1)),' And ',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '/' and first field has 'and' - see if we can give credit to the first artist
  $query = "select trim(substring_index(trim(substring_index(track.title,'/',1)),' and ',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '/' and first field has ',' - see if we can give credit to the first artist
  $query = "select trim(substring_index(trim(substring_index(track.title,'/',1)),', ',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '/' and first field has '&' - see if we can give credit to the first artist
  $query = "select trim(substring_index(trim(substring_index(track.title,'/',1)),' & ',1)) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%'";
  changeTrackArtist($query);

  // Track has '/' and first field has ',' - see if it's last, first format
  $query = "select concat(trim(substring_index(trim(substring_index(track.title,'/',1)),',',-1)), ' ', trim(substring_index(trim(substring_index(track.title,'/',1)),',',1))) as dname, track.uid from track,artist where track.artistid=artist.uid and artist.dispname='various' and track.title like '%/%' and track.title like '%,%'";
  changeTrackArtist($query);

}

function changeTrackArtist($query) {
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $dname = $myrow[0];
    $trackid = $myrow[1];
    $query = "select uid from artist where dispname = \"$dname\"";
    $result2 = doQuery($query);
    if (mysqli_num_rows($result2) == 1) {
      $myrow2 = mysqli_fetch_row($result2);
      $query = "Update track set artistid=$myrow2[0] where uid=$trackid";
      echo "$query<br>";
      doQuery($query);
    } else {
      $dname2 = "The " . $dname;
      $query = "select uid from artist where dispname = \"$dname2\"";
      $result2 = doQuery($query);
      if (mysqli_num_rows($result2) == 1) {
        $myrow2 = mysqli_fetch_row($result2);
        $query = "update track set artistid=$myrow2[0] where uid=$trackid";
        doQuery($query);
        echo "$query<br>";
      }
    }
  }
}

function setWavDuration() {
  $query = "select uid, title, url, ftype from track where ftype & 1 = 1 and duration is null or duration=0";
  $result = doQuery($query);
  while ($myrow = mysqli_fetch_row($result)) {
    $fsize = filesize($myrow[2]);
    $duration = round($fsize / 176400);
    $query = "update track set duration=$duration where uid=$myrow[0]";
    doQuery($query);
  }
  // set album durations
  $query = "update album a inner join (select listitem.albumid, sum(track.duration) as duration from listitem, track where listitem.trackid=track.uid group by listitem.albumid) t on a.uid = t.albumid set a.duration = t.duration where a.duration < 1800";
  $result = doQuery($query);
}

function cleanUp() {

  // Set stitle field
  $query = "update album set stitle=substring(title,1,30)";
  doQuery($query);

  // Check for tracks with artist 'Various' and see if artist can be extracted from track title.
  findTrackArtist();

  // Set playing time for any new .wav files
  setWavDuration();

  // Set unused genres to rank 4
  $query = "update genre set genre.rank=3 where genre.rank=4";
  doQuery($query);
  $query = "update genre left join track on track.genre1=genre.uid or track.genre2=genre.uid set genre.rank=4 where track.uid is null";
  doQuery($query);

  // delete alistitems (collection links) pointing to missing collections or albums
  $query = "delete alistitem.* from alistitem left join alist on alistitem.alistid=alist.uid where alist.uid is null";
  doQuery($query);
  $query = "delete alistitem.* from alistitem left join album on alistitem.albumid=album.uid where album.uid is null";
  doQuery($query);

  // Delete ratings without collections or tracks
  $query = "delete ratings.* from ratings left join alist  on ratings.collid=alist.uid where alist.uid is null";
  doQuery($query);
  $query = "delete ratings.* from ratings left join track  on ratings.trackid=track.uid where track.uid is null";
  doQuery($query);

  // Delete track-artist links with no tracks or artists
  $query = "delete talink.* from  talink left join track on track.uid=talink.tid where track.uid is null";
  doQuery($query);
  $query = "delete talink.* from  talink left join artist  on artist.uid=talink.aid where artist.uid is null";
  doQuery($query);

  $query = "SELECT uid from alist where alist.uid > 2";
  $alistResult = doQuery($query);
  // For each list
  while ($alistRow = mysqli_fetch_row($alistResult)) {
    $query = "insert into ratings select alist.uid,track.uid, 50, (50 + rand() * 100)
    from alist,alistitem,listitem,
    track left join ratings on (track.uid=ratings.trackid and ratings.collid=$alistRow[0])
    where alistitem.alistid=alist.uid
    and alistitem.albumid = listitem.albumid
    and track.uid=listitem.trackid
    and alist.uid=$alistRow[0]
    and ratings.cscore is null
    order by alist.uid,alistitem.albumid,track.seq limit 1";
    $result = doQuery($query);
  }

  // Fix missing talink records
  $query = "insert into talink select null, track.uid, artistid, 1 from track left join talink on talink.tid=track.uid where talink.uid is null";
  doQuery($query);

  // Set 'unfiled' artist genre to most common non-unfiled album genre by that artist
  $query = "update artist a inner join (select artist.uid as auid, album.genre1 as ag, count(album.genre1) as g2 from album, artistlink, artist where album.uid=artistlink.albumid and artistlink.artistid=artist.uid and artist.agenre=126 and album.genre1 !=126 group by artist.uid,album.genre1 order by g2 desc) g on a.uid=g.auid set a.agenre=g.ag";
  doQuery($query);

  // Set 'Unfiled' albums to genre of artist if artist is not 'unfiled'
  $query = "update album, artistlink, artist set album.genre1=artist.agenre where album.uid=artistlink.albumid and artistlink.artistid=artist.uid and album.genre1=126 and artist.agenre !=126";
  doQuery($query);

  // Strip carriage returns from album titles.
  $query = "update album set title=left(title,length(title)-1) where title like '%\n'";
  doQuery($query);

}

function makeMultipleArtists() {
  global $dbi;
  $query = "SELECT dispname, uid, agenre from artist where dispname like \"%;%\" order by lastname";
  $martistResult = doQuery($query);
  // For every multi-artist artist record
  while ($martistRow = mysqli_fetch_row($martistResult)) {
    // UID of artist with multiple names
    $martistUid = $martistRow[1];
    $martistDname = $martistRow[0];
    $martistGenre = $martistRow[2];
    $names = explode(';', $martistDname);
    echo "<br>Multi-artist $martistUid: $martistDname<br>";

    // Get every album (artistlink)
    $query = "SELECT uid, albumid from artistlink where artistid = $martistUid";
    $alinkResult = doQuery($query);
    // For each album
    while ($alinkRow = mysqli_fetch_row($alinkResult)) {
      $alistUid = $alinkRow[0];
      $albumUid = $alinkRow[1];
      $rank = 1;
      echo "Album $albumUid artistlink $alistUid<br>";

      // Delete original artistlink
      $query = "DELETE from artistlink where uid = $alistUid";
      doQuery($query);
      echo "$query<br>";

      // Create new artistlinks for each artist
      foreach ($names as $name) {
        $artistUid = getartist($name, true);
        echo "Artisuid = $artistUid";
        if ($artistUid == "") {
          echo "Bad Artist: $name<br>";
          exit;
        }
        /*
        $name = trim($name);
        $sname = addslashes($name);
        $query = "SELECT uid from artist where dispname =  \"$sname\"";
        $artistResult = doQuery ( $query );
        if(mysqli_num_rows($artistResult) == 0){
        $query = "INSERT INTO artist (dispname, agenre) values (\"$sname\", $martistGenre)";
        $insertResult = doQuery($query);
        echo "$query<br>";
        $artistUid=mysqli_insert_id($dbi);
        }else{
        $artistRow = mysqli_fetch_row ( $artistResult );
        $artistUid=$artistRow[0];
        }
         */
        // We have UID of one artist. Create artistlink.
        echo "Name = $name UID = $artistUid<br>";
        $query = "INSERT INTO artistlink (artistid,albumid,rank) values ($artistUid,$albumUid,$rank)";
        doQuery($query);
        echo "$query<br>";
        $rank++;
      }
    }

    // Done with albums. Now do tracks.
    // Get every track
    $query = "SELECT uid from track where artistid = $martistUid";
    $trackResult = doQuery($query);
    // For each track
    while ($trackRow = mysqli_fetch_row($trackResult)) {
      $trackUid = $trackRow[0];
      $rank = 1;

      // Create new artistlinks for each artist
      foreach ($names as $name) {
        $artistUid = getartist($name, true);
        echo "Artist $artistUid track $trackUid<br>";
        if ($artistUid == "") {
          echo "Bad Artist1: $name<br>";
          exit;
        }

        //exit;
        /*
        $name = trim($name);
        $sname = addslashes($name);
        $query = "SELECT uid from artist where dispname =  \"$sname\"";
        $artistResult = doQuery ( $query );
        if(mysqli_num_rows($artistResult) == 0){
        $query = "INSERT INTO artist (dispname, agenre) values (\"$sname\", $martistGenre)";
        echo "$query<br>";
        $insertResult = doQuery($query);
        echo "$query<br>";
        $artistUid=mysqli_insert_id($dbi);
        }else{
        $artistRow = mysqli_fetch_row ( $artistResult );
        $artistUid=$artistRow[0];
        }
         */
        // We have UID of one artist. Create artistlink.
        // If it's the first, replace original artistid field
        if ($rank == 1) {
          $query = "UPDATE track set artistid = $artistUid where uid =  $trackUid";
          doQuery($query);
          echo "$query<br>";
        }
        echo "Name = $name UID = $artistUid<br>";
        $query = "INSERT INTO talink (aid,tid,rank) values ($artistUid,$trackUid,$rank)";
        doQuery($query);
        echo "$query<br>";
        $rank++;
      }
    }
    if ($martistUid == "") {
      echo "Bad Artist2: <br>";
      exit;
    }

    // Almost done with artist. Fix mp3file
    $query = "update mp3file set artistid = $artistUid where artistid=$martistUid";
    doQuery($query);
    // Finally, delete original multiple artist record
    $query = "DELETE from artist where uid=$martistUid";
    doQuery($query);
  }
}

// Set artist default genre to most common genre
//

// Set stitle field
// update album set stitle=title;

// non-referenced artists
// select artist.uid, dispname from artist left join artistlink on artistlink.artistid=artist.uid left join talink on artist.uid = talink.aid left join track on artist.uid=track.artistid left join mp3file on artist.uid=mp3file.artistid where talink.tid is null and artistlink.albumid is null and track.uid is null and mp3file.uid is null and artist.user='n' order by dispname;

// delete artist from artist left join artistlink on artistlink.artistid=artist.uid left join talink on artist.uid = talink.aid left join track on artist.uid=track.artistid left join mp3file on artist.uid=mp3file.artistid where talink.tid is null and artistlink.albumid is null and track.uid is null and mp3file.uid is null and artist.user='n'

// Tracks without links
// select track.uid,track.title,track.url from track left join listitem on listitem.trackid=track.uid where listitem.uid is null;

// Links without tracks
// delete listitem.* from listitem left join track on listitem.trackid=track.uid where track.uid is null;

// Links without albums
// select listitem.* from listitem left join album on listitem.albumid=album.uid where album.uid is null;

// Albums (not playlists) that are supposed to be on the jukebox but have no listitems
//select album.uid,title,cdtag from album left join listitem on listitem.albumid=album.uid where listitem.uid is null and (status & 128) = 0 and (status & 1) = 1;

// Artists who are not referenced
// delete artist.* from artist left join artistlink on artistid=artist.uid left join track on artist.uid=track.artistid left join mp3file on artist.uid=mp3file.artistid where artistlink.uid is null and track.uid is null and mp3file.uid is null and user = 'n';

// Artistlink without artist
// select artistlink.uid from artistlink left join artist on artistid=artist.uid where artist.uid is null;

// Artistlink without album
// select artistlink.uid from artistlink left join album on albumid=album.uid where album.uid is null;

// Delete duplicate collection links
//delete l1.* from alistitem, alistitem as l1 where alistitem.albumid=l1.albumid and alistitem.alistid=l1.alistid and alistitem.uid != l1.uid and alistitem.uid < l1.uid;

// create URLs for missing values
//select concat("/music/",lcase(substring(cdtag,1,1)),"/",substring(lcase(cdtag),3,1),"/",substring(lcase(cdtag),3,1),"/audio_",lpad(track.seq,2,0),".wav"), album.cdtag, album.title, track.title from album, listitem, track where album.uid=listitem.albumid and listitem.trackid=track.uid and track.ftype=0 order by cdtag limit 1;

//update album, listitem, track set track.url=concat("/music/",lcase(substring(cdtag,1,1)),"/",substring(lcase(cdtag),2,1),"/",substring(lcase(cdtag),3,1),"/audio_",lpad(track.seq,2,0),".wav") where album.uid=listitem.albumid and listitem.trackid=track.uid and track.ftype=0;

//select album.status & 128, album.cdtag, album.title, substring(track.url,1,12), track.title from album, listitem, track where album.uid=listitem.albumid and listitem.trackid=track.uid and substring(track.url,1,12) != concat("/music/",lcase(substring(cdtag,1,1)),"/",substring(lcase(cdtag),2,1),"/",substring(lcase(cdtag),3,1)) and album.status & 128 = 0 order by cdtag;
