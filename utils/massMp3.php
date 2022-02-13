<?php
// General purpose PHP functions. No output when loaded
include_once ("../constants.php");

$ldb = mysql_connect ( "localhost", "wkuhns" );
mysql_select_db ( "jukebox", $ldb );
// $outfile = fopen ( "/tmp/mp3batch", "a" );
// Get parent album (selection may have come from playlist)
$query = "select url,track.title,track.seq,album.title,dispname,track.genre1,album.status,cdtag,track.ftype,track.uid
FROM album,listitem,track,artistlink,artist
WHERE listitem.albumid=album.uid
and track.uid=listitem.trackid
and artistlink.artistid=artist.uid
and artistlink.albumid=album.uid
and (track.ftype & 1) = 1 and (track.ftype & 2) = 0
and album.status & " . PLAYLIST . " = 0";
// fwrite($outfile, $query);
$result = mysql_query ( $query, $ldb );
while ( $myrow = mysql_fetch_row ( $result ) ) {
	$ti = $myrow [9];
	// Check if mp3 file already exists
	$seq = sprintf ( "%05d", $myrow [2] );
	$mp3file = substr ( $myrow [0], 0, strlen ( $myrow [0] ) - 3 ) . 'm4a';
	
	$mp3file = substr ( $myrow [0], 0, strlen ( $myrow [0] ) - 3 ) . 'mp3';
	$srcfile = substr ( $myrow [0], 0, strlen ( $myrow [0] ) - 3 ) . 'wav';
	if (file_exists ( $srcfile )) {
		// fwrite ( $outfile,
		// "lame --tt \"$myrow[1]\" --tl \"$myrow[3]\" --ta \"$myrow[4]\" --tn \"$myrow[2]\" --tg \"$myrow[5]\" $myrow[0] \"$mp3file\"\n" );
		$cmd = "lame --tt \"$myrow[1]\" --tl \"$myrow[3]\" --ta \"$myrow[4]\" --tn \"$myrow[2]\" --tg \"$myrow[5]\" $myrow[0] \"$mp3file\"\n";
		// echo $cmd . "\n";
		exec ( $cmd );
		$query = "update track set ftype = ftype | " . MP3 . " where uid = $ti";
		$result2 = mysql_query ( $query, $ldb );
	} else {
		// $srcfile = substr ( $myrow [0], 0, strlen ( $myrow [0] ) - 3 ) . 'wma';
		// if (file_exists ( $srcfile )) {
		// fwrite ( $outfile,
		// "ffmpeg -i \"$srcfile\" -ab 192k -map_metadata 0 -id3v2_version 3 -write_id3v1 1 \"$mp3file\"\n" );
		// $query = "update track set ftype = ftype | " . MP3 . " where uid = $ti";
		// $result2 = mysql_query ( $query, $ldb );
		// }
	}
	echo $myrow [4] . " track " . $myrow [2] . " " . $myrow [0] . "\n";
}


