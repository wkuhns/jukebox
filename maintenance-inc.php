<?php
include 'maintfunctions.php';

/*
echo "<a href=utils/build-mp3-db.php?buildcmd=rebuild>Rebuild MP3 database</a><br>\n";

echo "<a href=utils/build-mp3-db.php?buildcmd=import>Import MP3 files</a><br>\n";

echo "<a href=utils/fixmp3.php>Clean up MP3 files</a><br>\n";

echo "<a href=utils/process-mp3-db.php>Process MP3 files</a><br>\n";

echo "<a href=utils/setmp3duration.php>Set track playing times</a><br>\n";
*/

echo "<h3>Review artists</h3>
	Check any new artists on the system. This is a two-step process. First, check that artist first name and last name are in the correct fields. The system alphabetizes by last name. Check spelling at the same time. Then, the system shows possible duplicate artists. If there's a duplicate, ensure that they're spelled identically so that they can be merged in the next step.
	<br><a href=index.php?view=$view&action=artist_review>Review Artists</a><br></h3><hr>\n";

echo "<h3>Purge Duplicate artists</h3>
	This function will combine artists with identical display names. Use it after you correct a mis-spelled duplicate artist. 
	<br><a href=index.php?view=$view&action=purge_artists>Purge Duplicate Artists</a><br><hr>\n";

echo "<h3>Review Possible Duplicate Albums</h3>
	Show albums with the same title that haven't been marked as 'No Dups'. Review albums. Manually delete redundant duplicates - if you do this, don't click 'OK', just click this link again. Click 'OK' if they're not duplicates, or click 'Combine' if each is a partial subset of tracks from the same album.   
		<br><a href=index.php?view=$view&action=album_review>Review Albums</a><br><hr>\n";

echo "<h3>Make mp3 files from wav files</h3>
	Create mp3 files for any wav files that don't have one. This should be done before building the iTunes tree. Could take a long time.
	<br><a href=index.php?view=$view&action=makeAllMp3>Make all MP3</a><br><hr>\n";

echo "<h3>Build iTunes Tree</h3>
	Build iTunes-format directory structure (artist/album/tracks) of symbolic links under /music/music for Windows users. Takes a while....
	<br><a href=index.php?view=$view&action=build_tree>Build Tree</a><br><hr>\n";

/*
echo "<a href=index.php?view=$view&action=review_track_updates&ftype=import>Review Track Updates</a><br>\n";

echo "<a href=index.php?view=$view&action=check_tracks>Check Tracks</a><br>\n";
*/

echo "<h3>Clean Up</h3>
	Perform a series of housekeeping tasks - deleting orphan records, fixing missing records, etc. 
	<br><a href=index.php?view=$view&action=cleanUp>cleanUp</a><br><hr>\n";

/*
echo "<a href=index.php?view=$view&action=fixFileType>Fix File Type</a><br>\n";

echo "<a href=index.php?view=$view&action=reviewTrackArtists>reviewTrackArtists</a><br>\n";
*/

echo "<h3>Break apart multiple artists</h3>
	For any artists with a semicolon, break into two artists.
	<br><a href=index.php?view=$view&action=makeMultipleArtists>makeMultipleArtists</a><br><hr>";

echo "<h3>Get Album Covers</h3>
	This will attempt to get album covers for any new albums missing covers. There's a 'coverfail' field that gets set if this doesn't work. Individual album covers can also be manually requested from the album pop-up window.
	<br><a href=index.php?view=$view&action=getCovers>Get Covers</a><br><hr>\n";


// For historical reference. Should be done artist-byartist now.
if ($_REQUEST ['action'] == 'build_collected_tracks') {
	$query = "select distinct talink.aid, count(talink.tid) as ct
		from artist,artistlink,album,listitem,talink, track 
		where artist.uid=artistlink.artistid 
		and artistlink.albumid = album.uid 
		and album.uid=listitem.albumid 
		and track.uid=listitem.trackid 
		and talink.tid=listitem.trackid 
		and talink.aid != artist.uid
		and talink.aid != 0 
		and talink.aid > 499    
		and album.status & 128 = 0 
		group by talink.aid having ct > 1";
		echo $query;
		exit;
	$result = doquery ( $query );
	while ($myrow=mysqli_fetch_row($result)){
		build_collected_tracks($myrow[0]);
		echo "New collection $myrow[0]<br>";
	}
	//build_collected_tracks(698);
}

if ($_REQUEST ['action'] == 'getCovers') {
	$query = "SELECT cdtag from album where cover='N' and coverfail=0 and (status & 128) = 0";
	$result = doquery ( $query );
	while ($myrow = mysqli_fetch_row ( $result )){
		$cmd = "/music/tools/coverart2 " . strtolower ( $myrow [0] );
		echo "<br>Trying $cmd<br>";
		system ( "$cmd" );
	}
}


if ($_REQUEST ['action'] == 'makeMultipleArtists') {
	makeMultipleArtists();
}

if ($_REQUEST ['action'] == 'cleanUp') {
	cleanUp();
}

if ($_REQUEST ['action'] == 'makeAllMp3') {
	makeAllMp3();
}

if ($_REQUEST ['action'] == 'fixFileType') {
	fixFileType();
}

// Update track data

if ($_REQUEST ['trackuid']) {
	$newtitle = trim ( $_REQUEST ['newtitle'] );
	$newartist = trim ( $_REQUEST ['newartist'] );
	$uid = $_REQUEST ['trackuid'];

	// echo "$newtitle $newartist";

	if ($_REQUEST ['allgood'] == 'on') {
		$artistid = getartist ( $newartist, TRUE );
		if ($_REQUEST ['ftype'] == 'import') {
			$query = "update mp3file set artistid=$artistid, t_artist = \"$newartist\", title = \"$newtitle\", t_title=NULL where uid=$uid";
		} else {
			$query = "update track set artistid=$artistid, t_artist = \"$newartist\", title = \"$newtitle\", t_title=NULL where uid=$uid";
		}
		echo $query;
		$result = doquery ( $query );
	}

	if ($_REQUEST ['nogood'] == 'on') {
		if ($_REQUEST ['ftype'] == 'import') {
			$query = "update mp3file set t_title=NULL where uid=$uid";
		} else {
			$query = "update track set t_title=NULL where uid=$uid";
		}
		echo $query;
		$result = doquery ( $query );
	}
}

// Make artist update if called by self
if ($_REQUEST ['auid']) {
	$first = trim ( $_REQUEST ['first'] );
	$last = trim ( $_REQUEST ['last'] );
	//$disp = trim ( $_REQUEST ['disp'] );
	$uid = $_REQUEST ['auid'];
	$astatus = 0;

	// 'allgood' is checkbox to approve artist
	if ($_REQUEST ['allgood'] == 'on') {
		$astatus = 1;
	}

	// 'unfirst is checkbox to unseparate first name
	if ($_REQUEST ['unfirst'] == 'on') {
		$last = $first . ' ' . $last;
		$first = "";
	}

	// 'swap is checkbox to swap first and last names, strip comma
	if ($_REQUEST ['swap'] == 'on') {
		$disp = $last;
		$last = $first;
		$first = $disp;
	}

	$disp = trim($first . " " . $last);

	$query = "update artist
  	set firstname=\"$first\",
  	lastname=\"$last\",
  	dispname=\"$disp\",
  	status=status + $astatus
    where uid = $uid";
	// echo $query;
	$result = doquery ( $query );
}

if ($_REQUEST ['action'] == 'reviewTrackArtists') {
	if ($_REQUEST['rta-btn']){
		$rtabtn = $_REQUEST['rta-btn'];
		$trackid = $_REQUEST['trackid'];
		$f1 = $_REQUEST['f1'];
		$f2 = $_REQUEST['f2'];

		if($rtabtn == 'First'){
			$artistid = getartist($f1,true);
			echo "Artist ID = $artistid<br>";
			$f2 = addslashes($f2);
			$query = "update track set artistid=$artistid, title='$f2', tstatus=(tstatus | 1) where uid=$trackid";
			doQuery ( $query );
			echo "$query<br>";
		}

		if($rtabtn == 'Second'){
			$artistid = getartist($f2,true);
			echo "Artist ID = $artistid<br>";
			$f1 = addslashes($f1);
			$query = "update track set artistid=$artistid, title='$f1', tstatus=(tstatus | 1) where uid=$trackid";
			doQuery ( $query );
			echo "$query<br>";
		}

		if($rtabtn == 'OK'){
			$query = "update track set tstatus=(tstatus | 1) where uid=$trackid";
			doQuery ( $query );
			echo "$query<br>";	
		}	
	}
	reviewTrackArtists();
}

if ($_REQUEST ['action'] == 'review_track_updates') {
	review_track_updates ( $_REQUEST ['ftype'] );
}

if ($_REQUEST ['action'] == 'artist_review') {
	review_artists ();
}

if ($_REQUEST ['action'] == 'purge_artists') {
	purge_artists ();
}

if ($_REQUEST ['action'] == 'album_review') {
	review_albums ();
}

if ($_REQUEST ['action'] == 'nondup') {
	mark_nondup ( $_REQUEST ['albumid'] );
}

if ($_REQUEST ['action'] == 'merge') {
	merge_albums ( $_REQUEST ['albumid'], $_REQUEST ['dupid'] );
}

if ($_REQUEST ['action'] == 'build_tree') {
	// Delete existing tree

	$cmd = "rm -r /music/music/*";
	system ($cmd);

	// Build tree for albums
	$query = "select uid from album where (status & " . PLAYLIST . ") != 128";
	$result=doquery($query);
	while ($myrow=mysqli_fetch_row($result)){
	 build_tree("/music/music/",$myrow[0]);
	}

	// Build tree for playlists
	$cmd = "mkdir /music/music/00playlists";
	system ($cmd);
	$query = "select uid from album where (status & " . PLAYLIST . ") = 128 and title not like 'Collected%'";
	$result = doquery ( $query );
	while ( $myrow = mysqli_fetch_row ( $result ) ) {
		build_tree ( "/music/music/00playlists/", $myrow [0] );
	}
}

if ($_REQUEST ['action'] == 'check_tracks') {
	$query = "select cdtag, uid from album where (status & " . PLAYLIST . ") != 128";
	$result = doquery ( $query );
	while ( $myrow = mysqli_fetch_row ( $result ) ) {
		check_tracks ( $myrow [0], $myrow [1] );
	}
}

?>