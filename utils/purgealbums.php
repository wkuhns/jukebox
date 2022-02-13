<?php
include '../sessionheader.php';
include '../constants.php';

// Find albums with no tracks
// If there are no files either, purge albums and listitem/artistlink records and directories

$query = "select album.uid,album.title,album.cdtag 
	from album left join listitem on listitem.albumid=album.uid 
	left join mp3file on mp3file.albumid=album.uid 
	where listitem.uid is null and mp3file.uid is null";
// echo "$query\n";
$result = doquery ( $query );
// echo "<br>\n";

while ( $album = mysql_fetch_row ( $result ) ) {
	$albumid = $album [0];
	$cdtag = strtolower ( $album [2] );
	$cdt1 = substr ( $cdtag, 0, 1 );
	$cdt2 = substr ( $cdtag, 1, 1 );
	$cdt3 = substr ( $cdtag, 2, 1 );
	$dir = "/music/$cdt1/$cdt2/$cdt3";
	// echo "$dir\n";
	$purgeable = FALSE;
	if (file_exists ( $dir )) {
		if (rmdir ( $dir )) {
			$purgeable = TRUE;
		}
	} else {
		$purgeable = TRUE;
	}
	if ($purgeable == TRUE) {
		// There are no files on disk for this album. Go ahead and purge it.
		$query = "delete from alistitem where albumid=$albumid";
		doquery ( $query );
		$query = "delete from artistlink where albumid=$albumid";
		doquery ( $query );
		$query = "delete from album where uid=$albumid";
		doquery ( $query );
		echo "$cdtag: Purged empty album " . $album [1] . "\n";
	} else {
		echo "$cdtag: Album " . $album [1] . " is not empty\n";
	}
}


