<?php
include '../sessionheader.php';
include '../constants.php';

// Find and purge artists with no references

$query = "select artist.uid,dispname from artist 
		left join artistlink on artist.uid=artistlink.artistid 
		left join track on artist.uid=track.artistid 
		left join mp3file on artist.uid=mp3file.artistid 
		where artistlink.uid is null 
		and track.uid is null 
		and mp3file.uid is null 
		and artist.user = 'N'";
echo "$query\n";
$result = doquery ( $query );
// echo "<br>\n";

while ( $artist = mysql_fetch_row ( $result ) ) {
	$artistid = $artist [0];
	echo "Purging " . $artist [1] . "\n";
	$query = "delete from artist where uid=$artistid";
	doquery ( $query );
	// echo "$query\n";
}


