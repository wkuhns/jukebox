<?php
// album.php is responsible for the album popup window for both albums and playlists.
// It may be invoked by itself when edit mode is set. In that case, it displays
// edit options. It also handles all of its own edit actions.
include 'sessionheader.php';

$uid = $_REQUEST [uid];
$action = $_GET ['action'];

if ($_POST ['action'] == "statusbox") {
	$master = $_POST ['master'] ? MASTER : 0;
	$ndups = $_POST ['ndups'] ? NONDUP : 0;
	$smask = $ndups | $master;
	$imask = NONDUP | MASTER;
	$query = "update album set status = status & ~$imask | $smask where uid = $uid";
	doquery ( $query );
}
// wrong cover photo - delete file and clear flag bit and fail count in album record
if ($action == 'dropcover') {
	$query = "select cdtag from album where uid=$uid";
	$result = doquery ( $query );
	$myrow = mysqli_fetch_row ( $result );
	$query = "update album set status = status & ~" . COVER . " , coverfail=99 where uid = $uid";
	doquery ( $query );
	$cmd = "rm " . MakeRoot ( $myrow [0] ) . "cover.jpg";
	system ( "$cmd" );
}

// get cover photo - invoke script in tools directory
if ($_GET [action] == 'getcover') {
	$query = "select cdtag from album where uid=$uid";
	$result = doquery ( $query );
	$myrow = mysqli_fetch_row ( $result );
	$cmd = "/music/tools/coverart2 " . strtolower ( $myrow [0] );
	system ( "$cmd" );
}

// if replaceartist is defined, update artist
if ($_POST ['replaceartist']) {
	$artistid = $_POST ['artistid'];
	$query = "update artistlink set artistid=$artistid where albumid=$uid";
	// echo $query;
	doquery ( $query );
}

// if atitle is defined, update album
if ($atitle = $_POST ['atitle']) {
	$atitle = str_replace ( '"', "'", $atitle );
	$query = "update album set title=\"$atitle\" where uid=$uid";
	doquery ( $query );
}

// if genre1 is defined, update album
$genre1 = $_REQUEST ['genre1'];
if ($genre1 != '') {
	$query = "update album set genre1=$genre1 where uid=$uid";
	doquery ( $query );
	if (! $_REQUEST ['pl']) {
		$query = "update listitem,track set track.genre1=$genre1 where listitem.albumid=$uid and track.uid=listitem.trackid";
		doquery ( $query );
	}
}

// if genre2 is defined, update album
if ($genre2 = $_POST ['genre2']) {
	$query = "update album set genre2=$genre2 where uid=$uid";
	doquery ( $query );
	if (! $_REQUEST ['pl']) {
		$query = "update listitem,track set track.genre2=$genre2 where listitem.albumid=$uid and track.uid=listitem.trackid";
		doquery ( $query );
	}
}

// if ttitle is defined, update track
if ($ttitle = $_POST ['ttitle']) {
	$trackid = $_POST ['trackid'];
	$ttitle = str_replace ( '"', "'", $ttitle );
	$query = "update track set title=\"$ttitle\" where uid=$trackid";
	// echo $query;
	doquery ( $query );
}

if ($action == 'delete_album') {
  deleteAlbum($uid);
	echo "<h1>Album / Playlist has been deleted</h1>";
	exit ();
}

// Done processing updates if any
// fetch album data

$query = "select album.uid,title,genre.gname,dispname,album.status,comment,tnum,tseries,artist.uid,1,cdtag,g2.gname 
  FROM album left join genre on genre.uid=album.genre1 left join genre as g2 on g2.uid=album.genre2 left join artistlink on artistlink.albumid=album.uid inner join artist on artistlink.artistid=artist.uid where album.uid=$uid";

$aresult = doquery ( $query );

$arow = mysqli_fetch_row ( $aresult );
$albumid = $arow [0];
$artistid = $arow [8];
$prisource = ! ($arow [4] & PLAYLIST);
$cover = $arow [4] & COVER;
$cdtag = $arow [10];
$pl = $arow [4] & PLAYLIST;

// Start HTML......

?>

<html>
<head>
<title>New Music Database</title>
<link rel=stylesheet type=text/css href=jukebox.css>
</head>

<?php
include_once 'jsfunctions.js';
// Script for processing playlist and mp3 updates from album window (this file)
// This javascript function silently invokes a php file (no output)
// It's passed the URL of the command to be executed (including the php filename).
// This function appends playlistid and albumid and one or more trackid values to the URL.
// If there's only one trackid, it appears as a form variable.
// If there's more than one trackid they appear as an array.
// When the php script completes, this page is reloaded.

?>
<script language="JavaScript">

  function albumcmd(cmd) {
    if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp=new XMLHttpRequest();
    }
    xmlhttp.onreadystatechange=function() {
      if (xmlhttp.readyState==4 && xmlhttp.status==200) {
        //window.opener.document.getElementById("mp3div").style.backgroundColor="lightgreen";
        window.location.reload();
      }
    }
    mycmd = cmd;
    mycmd += "&plistid=" + document.aform.plistid.value;
    mycmd += "&albumid=" + document.aform.albumid.value;
    var tidl = document.aform.trackid.length;
    if(tidl==undefined){
      mycmd += "&trackid[]=" + document.aform.trackid.value;
    }
    for (var i = 0; i < document.aform.trackid.length; i++){
      if (document.aform.trackid[i].checked){
        mycmd += "&trackid[]=" + document.aform.trackid[i].value;
      }
    }
    //window.opener.document.getElementById("mp3div").style.backgroundColor="yellow";
    xmlhttp.open("GET",mycmd,true);
    xmlhttp.send();
  }
  function MakeCDwindow(albumid) {
	    this.url = "/cgi-bin/cdburn.pl?albumid=" + albumid;
	    cdw = window.open(this.url,"cdwindow","toolbar=no,directories=no,menubar=no,scrollbars=yes,resizable=yes,width=550,height=400");
	    cdw.focus();
	  }
  
</script>

<?php

if ($newartist = $_POST ['newartist']) {
	$query = "select uid,dispname from artist where dispname like '%$newartist%'";
	$result = doquery ( $query );
	while ( $myrow = mysqli_fetch_row ( $result ) ) {
		echo "<form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>";
		echo "<input type=hidden name=uid value=$albumid>\n";
		echo "<input type=hidden name=artistid value=$myrow[0]>\n";
		echo $myrow [1];
		echo "<input name=replaceartist type=submit></form>";
	}
}

// Make container table: left cell is album art and edit options, right cell is track list
echo "<table width=100%>\n";

// If we're showing a playlist, use different heading and don't show album art
if ($pl) {
	if ($action == 'edit') {
		// Editing playlist
		echo "<tr><td colspan=2 align=center><form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>
      <input type=hidden name=uid value=$albumid>
      <input type=text name=atitle size=80 value=\"$arow[1]\" onchange=submit()></form></td></tr>\n";
	} else {
		// Just displaying playlist
    // If it's 'Collected Tracks:' then omit owner
    if(strncmp("Collected Tracks:",$arow[1],17)){
      // Not collected tracks
      echo "<tr><td colspan=2 align=center><h1>$arow[3]'s Playlist: $arow[1]</h1></td></tr>\n";
    }else{
      // Collected tracks
      echo "<tr><td colspan=2 align=center><h1>$arow[1]</h1></td></tr>\n";
    }
	}
	echo "<tr><td valign=top>\n";
} else {
	// Album, not playlist
	if ($action == 'edit') {
		// Editing album
		echo "<tr><td colspan=2><form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>
      <input type=hidden name=uid value=$albumid>Title: 
      <input type=text name=atitle size=60 value=\"$arow[1]\" onchange=submit()></form></td></tr>\n";
	} else {
		// Just displaying album
		echo "<tr><td colspan=2 align=center><h1>$arow[3]: $arow[1]</h1></td></tr>\n";
	}
	echo "<tr>\n";
	
	if ($action == 'edit') {
		echo "<td colspan=2 ><form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>";
		echo "<input type=hidden name=uid value=$albumid>\n";
		echo "Artist: <input name=newartist size=40>\n";
		echo "<input type=submit></td></tr>";
	}
	
	// Album art
	if ($cover) {
		if ($_GET [coversize] == 'full') {
			echo "<td valign=top align=center><a href=album.php?uid=$albumid><img src=" . MakeRoot ( $cdtag ) . "cover.jpg></a><br>\n";
		} else {
			echo "<td valign=top align=center><a href=album.php?coversize=full&uid=$albumid><img width=160 src=" . MakeRoot ( $cdtag ) . "/cover.jpg></a><br>\n";
		}
		echo "Click if this is the<br><a class=lsblue href=album.php?action=dropcover&uid=$albumid>Wrong Cover</a><br>\n";
	} else {
		echo "<td>\n";
		echo "Click to <a class=lsblue href=album.php?action=getcover&uid=$albumid>Get Cover Art</a><br>\n";
	}
}

echo "<br>\n";

echo "Play Tracks:<br>";

// Give user random / sequential play buttons
printf ( "<img class=white border=0 src=images/rnote.gif onclick=shellcmd('album=$albumid&playmode=random&collid=$collectionid')>\n" );
printf ( "<img class=white border=0 src=images/snote.gif onclick=shellcmd('album=$albumid&playmode=seq&collid=$collectionid')><br>\n" );

echo "<br>\n";

if ($action != 'edit') {
	echo "<b><a class=lsblue href=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . "?uid=$albumid&action=edit>EDIT</a>\n";
}
// echo "<b>Tape: $arow[6]$arow[7]<br>\n";

// ******************* Genre ******************************

echo "<br>\n";
echo "<br>\n";
if ($action == 'edit') {
	// If editing, allow genre change via pulldown selection boxes
	echo "<form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>";
	echo "<input type=hidden name=view value=$view>\n";
	echo "<input type=hidden name=pl value=$pl>\n";
	echo "<input type=hidden name=uid value=$albumid>\n";
	echo "<select name=genre1 onchange=submit()>\n";
	$query = "select uid,gname from genre where rank<=4 order by gname";
	$gresult = doquery ( $query );
	while ( $grow = mysqli_fetch_row ( $gresult ) ) {
		$sel = $arow [02] == $grow [1] ? 'selected' : '';
		echo "<option value=$grow[0] $sel>$grow[1]</option>\n";
	}
	echo "</select>\n";
	echo "</form>\n";
	// Genre2
	echo "<form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>";
	echo "<input type=hidden name=view value=$view>\n";
	echo "<input type=hidden name=pl value=$pl>\n";
	echo "<input type=hidden name=uid value=$albumid>\n";
	echo "<select name=genre2 onchange=submit()>\n";
	$query = "select uid,gname from genre where rank<=3 order by gname";
	$gresult = doquery ( $query );
	$sel = $arow [11] == '' ? 'selected' : '';
	echo "<option value=NULL $sel>--</option>\n";
	
	while ( $grow = mysqli_fetch_row ( $gresult ) ) {
		$sel = $arow [11] == $grow [1] ? 'selected' : '';
		echo "<option value=$grow[0] $sel>$grow[1]</option>\n";
	}
	echo "</select>\n";
	echo "</form>\n";
}
// show genres....
if ($arow [11] == '') {
	echo "<b>Genre: $arow[2]\n";
} else {
	echo "<b>Genre: $arow[2] / $arow[11]\n";
}

echo "<br><br><a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=17>Rock</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=80>Folk</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=8>Jazz</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=15>Rap</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=132>Indie Rock</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=13>Pop</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=129>Christmas</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=126>Unfiled</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=89>Bluegrass</a><br>\n";
echo "<a class=lsblue href=album.php?view=$view&uid=$albumid&genre1=2>Country</a><br>\n";
echo "<hr>";
echo "<form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>";
// echo "<input type=hidden name=view value=$view>\n";
// echo "<input type=hidden name=pl value=$pl>\n";
echo "<input type=hidden name=uid value=$albumid>\n";
echo "<input type=hidden name=action value=statusbox>\n";
echo "Master:<input type=checkbox " . ($arow [4] & MASTER ? "checked" : "") . " name=master value=checked>\n";
echo "No Dups:<input type=checkbox " . ($arow [4] & NONDUP ? "checked" : "") . " name=ndups value=checked>\n";
echo "<input type=submit>\n";
echo "</form>\n";

echo "</td>\n";

// Second cell
echo "<td valign=top>\n";

// Get and display tracks. If no plistid, then don't try and match tracks to playlist

// **** Broken if track.artistid is invalid *******************

if ($action == 'edit') {
	$sort = "track.title";
} else {
	$sort = "listitem.seq";
}
if ($plistid) {
  // we're looking at the currently selected system playlist
	$query = "select 
    track.title,listitem.seq,url,dispname,track.uid,track.artistid,
    track.duration,1, ftype,1,volume,
    if(cscore,cscore,50),genre.gname,g2.gname,l2.uid 
    FROM listitem,artist,album,genre,track 
    left join genre as g2 on g2.uid=track.genre2 
    left join listitem as l2 on l2.trackid=track.uid and l2.albumid=$plistid
    left join ratings on track.uid=ratings.trackid and ratings.collid=$collectionid 
    where genre.uid=track.genre1 
    and track.uid=listitem.trackid 
    and artist.uid=track.artistid 
    and album.uid=$albumid 
    and listitem.albumid = $albumid 
    order by $sort";
} else {
	$query = "select 
    track.title,listitem.seq,url,dispname,track.uid,track.artistid,
    track.duration,1, ftype,1,volume,
    if(cscore,cscore,50),genre.gname,g2.gname,NULL 
    FROM listitem,artist,album,genre,track 
    left join genre as g2 on g2.uid=track.genre2 
    left join ratings on track.uid=ratings.trackid and ratings.collid=$collectionid 
    where genre.uid=track.genre1 
    and track.uid=listitem.trackid 
    and artist.uid=track.artistid 
    and album.uid=$albumid 
    and listitem.albumid = $albumid 
    order by $sort";
}
$result = doquery ( $query );
//echo "$plistid $query<br>\n";

// Form mayhem: One giant form if not editing, individual track forms otherwise
// We need the big form in both cases to make javascript happy, so we end it right away if editing.

echo "<form name=aform>\n";
echo "<table border=0 cellpadding=0 cellspacing=0 width=100% bgcolor=white>\n";
echo "<input type=hidden name=plistid value=$plistid>\n";
echo "<input type=hidden value=$albumid name=albumid>\n";

// If not editing, we end this way at the bottom
if ($action == 'edit') {
	echo "<input type=hidden name=trackid value=''>\n";
	echo "</form>";
}
$bgc = '#dce4f9';

// Show tracks within form
$pltime = 0;
$bgc = 'white';
while ( $myrow = mysqli_fetch_row ( $result ) ) {
	
	$ext = $myrow [8];
	
	printf ( "<tr>\n" );
	// Delete button (X)
	if ($action == 'edit'){
    if($pl){
      printf ( "<td class=small bgcolor=$bgc width=20>
      <img border=0 src=images/b_drop.png 
      onclick=albumcmd('utilcmd.php?action=dropTrack&playlistuid=$albumid&trackuid=$myrow[4]')></a></td>\n" );
    }else{
      printf ( "<td class=small bgcolor=$bgc width=20>
      <img border=0 src=images/b_drop.png onclick=albumcmd('utilcmd.php?action=delete_track&trackuid=$myrow[4]')></a></td>\n" );
    }
	}

	// Play buttons: WAV on jukebox
	if (($ext & 1) == 1) {
		printf ( "<td class=small bgcolor=$bgc width=20><img border=0 src=images/note.gif onclick=shellcmd('play=wav&uid=$myrow[4]&collid=$collectionid')></a></td>\n" );
	} else {
		printf ( "<td bgcolor=$bgc width=20>&nbsp;</td>\n" );
	}
	
	// Play buttons: mp3 on jukebox
	if (($ext & 2) == 2) {
		printf ( "<td class=blue bgcolor=$bgc width=20 onclick=shellcmd('play=mp3&uid=$myrow[4]&collid=$collectionid')><b>M</b></td>\n" );
	} else {
		printf ( "<td class=small bgcolor=$bgc width=20>&nbsp;</td>\n" );
	}
	
	// Play buttons: m4a on jukebox
	if (($ext & 4) == 4) {
		printf ( "<td class=blue bgcolor=$bgc width=20 onclick=shellcmd('play=m4a&uid=$myrow[4]&collid=$collectionid')><b>A</b></td>\n" );
	} else {
		printf ( "<td bgcolor=$bgc width=20>&nbsp;</td>\n" );
	}
	
	// Play buttons: wma on jukebox
	if (($ext & 8) == 8) {
		printf ( "<td class=blue bgcolor=$bgc width=20 onclick=shellcmd('play=wma&uid=$myrow[4]&collid=$collectionid')><b>W</b></td>\n" );
	} else {
		printf ( "<td bgcolor=$bgc width=20>&nbsp;</td>\n" );
	}
	
	printf ( "<td class=small bgcolor=$bgc width=20>%s</td>\n", $myrow [1] );
	
	// If not editing, display checkbox to allow multiple track selection
	if ($action != 'edit') {
		printf ( "<td class=padded bgcolor=$bgc width=20><input type=checkbox name=trackid value=%s></td>\n", $myrow [4] );
	}
	
	// Show track title and optionally artist
	if ($action == 'edit') {
		// When editing, just show title as editable input
		echo "<td colspan=2 class=small align=center><form action=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . " method=post>
      <input type=hidden name=trackid value=$myrow[4]>
      <input type=hidden name=uid value=$albumid>
      <input type=text name=ttitle size=40 maxlength=80 value=\"$myrow[0]\" onchange=submit()></form></td>\n";
	} else {
		// if track artist <> album artist, display track title and artist
		$bold = '';
		$unbold = '';
		if ($myrow [14] != '' & ! $pl) {
			$bold = '<b>';
			$unbold = '</b>';
		}
		$rbold = '';
		$runbold = '';
		if ($myrow [11] > 50) {
			$rbold = '<b>';
			$runbold = '</b>';
		}
		printf ( "<td class=small  bgcolor=$bgc><a title=\"$myrow[12]/$myrow[13]\" href=%s target=streamwindow>$rbold [%s] $runbold</a>", 
      $myrow [2], $myrow [11] );
		if (! strcmp ( $artistid, $myrow [5] )) {
			printf ( "$bold%s$unbold</td>", $myrow [0] );
		} else {
			printf ( "$bold %s $unbold(<i>%s</i>)</td>", $myrow [0], $myrow [3] );
		}
	}
	// Display track play time
	$mins = sprintf ( "%2d", $myrow [6] / 60 );
	$secs = $myrow [6] - $mins * 60;
	printf ( "<td  class=small align=right bgcolor=$bgc width=50>%s:%02s</td></tr>\n", $mins, $secs );
	
	$pltime += $myrow [6];
	$playlist = $myrow [7];
	// Alternate colors for successive tracks
	if ($bgc == 'white') {
		$bgc = '#dce4f9';
	} else {
		$bgc = 'white';
	}
}

$mins = sprintf ( "%2d", $pltime / 60 );
$secs = $pltime - $mins * 60;
printf ( "<tr><td class=small style='color:black;' align=right bgcolor=$bgc colspan=9><b>Total Time: %s:%02d</b></td></tr>\n", $mins, $secs );
if ($bgc == 'white') {
	$bgc = '#dce4f9';
} else {
	$bgc = 'white';
}
echo "<tr>\n<td colspan=8 class=padded bgcolor=$bgc><hr></td></tr></table>\n";

// Close second cell
echo "</td>\n";

// Close container table
echo "</tr></table>\n";

echo "<tr>\n<td colspan=6 class=padded bgcolor=$bgc><hr></td></tr></table>\n";

if ($action == 'edit') {
	echo "<b><a class=lsblue href=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . "?uid=$albumid&pl=$pl&action=delete_album>Delete This " . ($pl ? 'Playlist' : 'Album') . "</a>\n";
} else {
	// Container table for playlist / CD / mp3 buttons
	echo "<table border=0 width=100% cellpadding=0 cellspacing=0>\n";
	echo "  <tr><td align=center class=padded align=center>Playlist ($plistname)</td><td class=padded align=center>CD Burner</td><td class=padded colspan=2 align=center>MP3 Player</td></tr>\n";
	
	// rest of form for playlist updates
	
	echo "  <tr><td class=padded align=center>\n";
	
	// Playlist add / edit
	// If we're currently editing a playlist.....
	if ($plistid) {
		// If this album is that playlist, allow deletion of selected tracks from playlist
		if ($plistid == $albumid) {
			echo "<input type=button name=playlist value='Delete Selected' 
        onclick=albumcmd(\"plistcmd.php?plcmd=DeleteSelected\")>\n";
			echo "<input type=button name=playlist value=Clear onclick=plistcmd(\"Clear\")>\n";
		} else {
			// Otherwise allow addition of tracks to selected playlist
			echo "<input type=button name=playlist value='Add Selected' onclick=albumcmd(\"plistcmd.php?plcmd=AddSelected\")>\n";
		}
	}
	echo "</td>\n";
	
	// Burn CD
	echo "<td class=padded align=center>\n";
	// echo "<form name=cdform action=/cgi-bin/cdburn.pl target=cdburn>\n";
	echo "<input type=button name=Burn value='Burn CD' onclick=MakeCDwindow($albumid)>\n";
	echo "</td>\n";
	
	// MP3
	echo "<td class=padded align=center>\n";
	// echo "<form action=mp3.php method=get target=plwindow>\n";
	
	$pltitle = urlencode ( $arow [1] );
	echo "<input type=button value='Add Selected'  onclick=albumcmd(\"mp3cmd.php?mp3cmd=AddSelected&pltitle=\")>\n";
	echo "<input type=button value='Add All' onclick=albumcmd(\"mp3cmd.php?mp3cmd=AddAll&pltitle=$pltitle\")>\n";
	echo "</form></td>\n</tr>\n";
}
echo "\n</table></table>";

?>

</body>

</html>
