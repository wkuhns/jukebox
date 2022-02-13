<?php
include_once '../sessionheader.php';
include_once '../constants.php';
require_once ('../getid3/getid3.php');

// Several options:
// action = rebuild - clear all records and reindex entire /music disk
// action = import - clear records in import directory and rebuild

// always look for .mp3, .MP3, .m4a, and .M4A files

array (
		$mp3files 
);
array (
		$id3data 
);

// Are we web or command line?
$web = TRUE;
$cr = '<br>';

if (! $action = $_GET [action]) {
	$action = $argv [1];
	$web = FALSE;
	$cr = '';
}

if (! $action) {
	if ($web) {
		echo "Usage: action=xxx $cr ";
	} else {
		echo "Usage: action=xxx\n";
	}
	exit ();
}

if ($action == 'test') {
	$query = "delete from mp3file";
	$result = mysql_query ( $query, $ldb );
	$cmd = "find /music/y/j/e -type f ";
}

if ($action == 'rebuild') {
	$query = "delete from mp3file";
	$result = mysql_query ( $query, $ldb );
	$cmd = "find /music -type f ";
}

if ($action == 'import') {
	$query = "delete from mp3file where file like \"/music/import/%\"";
	$result = mysql_query ( $query, $ldb );
	$cmd = "find -L /music/import -type f ";
}

if ($action == 'reimport') {
	$cmd = "find -L /music/import -type f ";
}

if ($action == 'bigimport') {
	$query = "update mp3file set status = 0";
	$result = mysql_query ( $query, $ldb );
	$cmd = "find -L /music -type f ";
}

if ($action == 'update') {
	$cmd = "find -L /music -type f ";
}
$cmd .= "\( -name \"*.mp3\" -o -name \"*.m4a\" -o -name \"*.m4p\" -o -name \"*.MP3\" -o -name \"*.M4A\" -o -name \"*.wma\" -o -name \"*.WMA\" \)";
$cmd .= " | grep -v \"/music/music\"";
echo "$cmd<br>";

exec ( $cmd, $mp3files );

$i = 0;
foreach ( $mp3files as $mp3 ) {
	// Already got? skip...
	$sqlmp3 = addslashes ( $mp3 );
	$query = "select uid from mp3file where file = '$sqlmp3'";
	echo "$query<br>\n";
	$result = mysql_query ( $query, $ldb );
	if ($myrow = mysql_fetch_row ( $result )) {
		$query = "update mp3file set status = 1 where uid=$myrow[0]";
		$result = mysql_query ( $query, $ldb );
		echo "Skipping $mp3 - already have record<br>\n";
		continue;
	}
	
	unset ( $id3data );
	array (
			$id3data 
	);
	$comment = "";
	$extension = strtolower ( substr ( $mp3, - 3 ) );
	if ($extension == "m4a") {
		// Get data from M4a. Strangely, the output goes to stderr
		exec ( "faad -i \"$mp3\" 2>&1", $id3data );
		// print_r($id3data);
		// There's an early line with the playing time in it in the following format:
		// LC AAC 242.064 secs, 2 ch, 44100 Hz
		// Detect that line and extract the playing time
		$duration = 0;
		$bitrate = 0;
		foreach ( $id3data as $line ) {
			if (substr ( $line, 0, 2 ) == 'LC') {
				$duration = intval ( substr ( $line, 6, 9 ) );
			}
			$lparts = explode ( ":", $line );
			switch ($lparts [0]) {
				case "title" :
					$title = ltrim ( rtrim ( substr ( $lparts [1], 0, 80 ) ) );
					break;
				
				case "artist" :
					$artist = ltrim ( rtrim ( substr ( $lparts [1], 0, 80 ) ) );
					break;
				
				case "album" :
					$album = ltrim ( rtrim ( substr ( $lparts [1], 0, 80 ) ) );
					break;
				
				case "track" :
					$track = ltrim ( rtrim ( substr ( $lparts [1], 0, 50 ) ) );
					break;
				
				case "date" :
					$year = ltrim ( rtrim ( substr ( $lparts [1], 0, 50 ) ) );
					break;
				
				case "genre" :
					$genre = ltrim ( rtrim ( substr ( $lparts [1], 0, 50 ) ) );
					break;
			}
		}
		$ftypemask = M4A;
	}
	if ($extension == "mp3") {
		$duration = 0;
		$bitrate = 0;
		$getID3 = null;
		$getID3 = new getID3 ();
		$getID3->option_no_iconv = true;
		$ThisFileInfo = $getID3->analyze ( "$mp3" );
		
		// Data fro ID3V1 tag
		$id1_artist = $ThisFileInfo ['tags'] ['id3v1'] ['artist'] [0];
		$id1_album = $ThisFileInfo ['tags'] ['id3v1'] ['album'] [0];
		$id1_title = $ThisFileInfo ['tags'] ['id3v1'] ['title'] [0];
		$id1_track = $ThisFileInfo ['tags'] ['id3v1'] ['track_number'] [0];
		$id1_genre = $ThisFileInfo ['tags'] ['id3v1'] ['genre'] [0];
		
		// Data fro ID3V2 tag
		$id2_artist = $ThisFileInfo ['tags'] ['id3v2'] ['artist'] [0];
		$id2_album = $ThisFileInfo ['tags'] ['id3v2'] ['album'] [0];
		$id2_title = $ThisFileInfo ['tags'] ['id3v2'] ['title'] [0];
		$id2_track = $ThisFileInfo ['tags'] ['id3v2'] ['track_number'] [0];
		$id2_genre = $ThisFileInfo ['tags'] ['id3v2'] ['genre'] [0];
		
		if (strlen ( $id2_track ) > 2) {
			$id2_track = substr ( $id2_track, 0, 2 );
		}
		$id2_track = $id2_track + 0;
		
		// Pick best of bunch: Title
		// Longest is probably best, but nonzero 1d3v2 wins
		$title = $id1_title;
		if (strlen ( $id2_title ) > 0) {
			$title = $id2_title;
		}
		
		// Pick best of bunch: Album
		// Longest is probably best
		$album = $id1_album;
		if (strlen ( $id2_album ) > 0) {
			$album = $id2_album;
		}
		
		// Pick best of bunch: Artist
		// track is probably best
		$artist = $id1_artist;
		if (strlen ( $id2_artist ) > 0) {
			$artist = $id2_artist;
		}
		
		// Pick best of bunch: Track Number
		$track = $id1_track;
		if (is_numeric ( $id2_track ) && $id2_track != 0) {
			$track = $id2_track;
		}
		
		// Pick best of bunch: Genre
		$genre = $id1_genre;
		if (strlen ( $id2_genre ) > 0) {
			$genre = $id2_genre;
		}
		
		$bitrate = $ThisFileInfo ['audio'] ['bitrate']; // audio bitrate
		$pts = $ThisFileInfo ['playtime_string']; // playtime in minutes:seconds, formatted string
		$hms = explode ( ':', $pts );
		$mins = $hms [0];
		$secs = $hms [1];
		$duration = $mins * 60 + $secs;
		
		$lb = strpos ( $genre, '[' ) + 1;
		$genreid = substr ( $genre, $lb, - 1 );
		// Force 'unfiled' if unknown
		if ($genreid == 255) {
			$genreid = 126;
		}
		$ftypemask = MP3;
	}
	if ($extension == "wma") {
		$duration = 0;
		$bitrate = 0;
		$getID3 = null;
		$getID3 = new getID3 ();
		$getID3->option_no_iconv = true;
		$ThisFileInfo = $getID3->analyze ( "$mp3" );
		// print_r ( $ThisFileInfo );
		
		// Data from wma tags
		$artist = $ThisFileInfo ['tags'] ['asf'] ['artist'] [0];
		$album = $ThisFileInfo ['tags'] ['asf'] ['album'] [0];
		$title = $ThisFileInfo ['tags'] ['asf'] ['title'] [0];
		$track = $ThisFileInfo ['tags'] ['asf'] ['track'] [0];
		$genre = $ThisFileInfo ['tags'] ['asf'] ['genre'] [0];
		
		if (strlen ( $track ) > 2) {
			$track = substr ( $track, 0, 2 );
		}
		$track = $track + 0;
		
		$bitrate = $ThisFileInfo ['audio'] ['bitrate']; // audio bitrate
		$pts = $ThisFileInfo ['playtime_string']; // playtime in minutes:seconds, formatted string
		$hms = explode ( ':', $pts );
		$mins = $hms [0];
		$secs = $hms [1];
		$duration = $mins * 60 + $secs;
		
		$lb = strpos ( $genre, '[' ) + 1;
		$genreid = substr ( $genre, $lb, - 1 );
		// Force 'unfiled' if unknown
		if ($genreid == 255) {
			$genreid = 126;
		}
		$ftypemask = WMA;
		echo "Title = $title<br>\n";
		echo "Artist = $artist<br>\n";
		echo "Album = $album<br>\n";
		echo "track = $track<br>\n";
		echo "year = $year<br>\n";
		echo "genre = $genre<br>\n";
		echo "duration = $duration<br>\n";
	}
	
	if ($artist == 'hampton') {
		$artist = $album;
		$album = 'Unknown Album';
	}
	$title = str_replace ( '"', "'", $title );
	$artist = str_replace ( '"', "'", $artist );
	$album = str_replace ( '"', "'", $album );
	
	echo "Title = $title<br>\n";
	echo "Artist = $artist<br>\n";
	echo "Album = $album<br>\n";
	echo "track = $track<br>\n";
	echo "year = $year<br>\n";
	echo "genre = $genre<br>\n";
	echo "duration = $duration<br>\n";
	$genre = $genre == "Children's Music" ? "Childrens" : $genre;
	$sqlgenre = addslashes ( $genre );
	$query = "select uid from genre where gname='$sqlgenre'";
	$result = mysql_query ( $query, $ldb );
	if ($myrow = mysql_fetch_row ( $result )) {
		$genreid = $myrow [0];
	} else {
		$genreid = 126;
	}
	
	echo " Genre: '$genre' [$genreid]<br>\n";
	
	if ($genreid == '') {
		$genreid = 126;
	}
	
	// Try to find matching artist.
	$jbartistid = '';
	$sqlartist = addslashes ( $artist );
	$query = "select uid,dispname from artist where dispname = '$sqlartist'";
	echo "$query<br>\n";
	$result = mysql_query ( $query, $ldb );
	if ($myrow = mysql_fetch_row ( $result )) {
		echo "Artist [$myrow[1]] matches<br>\n";
		$jbartistid = $myrow [0];
	}
	
	// Find album if any.
	$albumid = '';
	$sqlalbum = addslashes ( substr ( $album, 0, 80 ) );
	if ($jbartistid != '' && $album != '') {
		$query = "select album.uid,title,cdtag from album,artistlink where artistlink.albumid=album.uid and title = '$sqlalbum' and artistid=$jbartistid";
		// $query = "select album.uid,title,cdtag from album,artistlink where artistlink.albumid=album.uid and left(title,28) = '$sqlalbum' and artistid=$jbartistid";
		echo "$query\n";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			echo "Album  [$myrow[1]] matches<br>\n";
			$albumid = $myrow [0];
			$cdtag = $myrow [2];
			$cdtag = strtolower ( $cdtag );
		}
	}
	
	// Maybe 'Unknown Album'?
	if ($jbartistid != '' && $album == '') {
		$query = "select album.uid,title,cdtag from album,artistlink where artistlink.albumid=album.uid and title = 'unknown album' and artistid=$jbartistid";
		echo "$query\n";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			echo "Album  [$myrow[1]] matches<br>\n";
			$albumid = $myrow [0];
			$cdtag = $myrow [2];
			$cdtag = strtolower ( $cdtag );
		}
	}
	
	$trackid = '';
	$sqltitle = mysql_real_escape_string ( substr ( $title, 0, 250 ) );
	if ($jbartistid != '' && $albumid != '' && $title != '') {
		// Find track if any
		$query = "select track.uid from track left join listitem on listitem.trackid=track.uid where listitem.albumid=$albumid and left(track.title,80) = '$sqltitle'";
		echo "$query\n";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			echo "Track  [$myrow[0]] matches<br>\n";
			$trackid = $myrow [0];
		}
	}
	
	// Get filesize
	$filesize = filesize ( $mp3 );
	
	$query = "insert into mp3file (file,artist,artistid,album,albumid,rdate,title,genre,track,trackid,seq,filesize,bitrate,duration,status) ";
	$query .= "values (\"$mp3\",\"$artist\",'$jbartistid',\"$album\",'$albumid','$year',\"$title\",'$genreid','$track','$trackid','$seq',$filesize,$bitrate,$duration,1)";
	
	echo "$query<br><br>\n";
	$result = mysql_query ( $query, $ldb );
	
	if ($ftypemask == "WMA") {
		exit ();
	}
}

// Update mp3files that match a track URL or the .wav version of a track url.
$query = "update mp3file,track set mp3file.trackid=track.uid,mp3file.status=1 where mp3file.file=track.url and trackid=0";
$result = mysql_query ( $query, $ldb );
$query = "update mp3file,track set mp3file.trackid=track.uid,mp3file.status=1 where mp3file.file=concat(left(url,22),'mp3') and trackid=0 ";
$result = mysql_query ( $query, $ldb );

// delete records that don't point to files
$query = "delete from mp3file where status=0;";
$result = mysql_query ( $query, $ldb );

// Delete records for files in trash
$query = "delete from mp3file where file like '/music/trash/%'";
$result = mysql_query ( $query, $ldb );

$query = "select 
  album.title,mp3file.album,
  artist.dispname,mp3file.artist,
  track.title,mp3file.title
  from album,artist,track,mp3file
  where album.status & 128 = 0
  and album.uid=mp3file.albumid
  and mp3file.trackid=track.uid 
  and artist.uid=track.artistid
  and (
  (album.title != mp3file.album)
  or (artist.dispname != mp3file.artist)
  or (track.title != mp3file.title)
  )";

?>

</body>

</html>


