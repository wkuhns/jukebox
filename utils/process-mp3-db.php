<?php
include '../sessionheader.php';
include_once '../constants.php';
require_once ('../getid3/getid3.php');

// Collection id for today's collection
$newcollid = NULL;

// Are we web or command line?
$web = TRUE;
$cr = '<br>\n';

if (! $action = $_GET [action]) {
	$action = $argv [1];
	$web = FALSE;
	$cr = '\n';
}

if (! $action) {
	if ($web) {
		echo "Usage: action=xxx $cr ";
	} else {
		echo "Usage: action=xxx\n";
	}
	exit ();
}

if ($action == 'link') {
	link_import_dups ();
	exit ();
}

if ($action == 'purge') {
	delete_import_dups ();
	exit ();
}

if ($action == 'md5') {
	getmd5 ();
	exit ();
}

if ($action == 'fix-unfiled') {
	$more = TRUE;
	while ( $more ) {
		$query = "select mp3file.genre, album.uid, album.cdtag 
			from album left join listitem on (listitem.albumid=album.uid) 
			left join mp3file on (mp3file.trackid=listitem.trackid) 
			where album.genre1=126 and mp3file.genre != 126 and mp3file.genre != 0 and mp3file.genre != '' 
			order by album.uid limit 1";
		$mp3files = mysql_query ( $query, $ldb );
		if (mysql_num_rows ( $mp3files ) == 1) {
			$mp3file = mysql_fetch_row ( $mp3files );
			$q2 = "update album set genre1 = $mp3file[0] where uid=$mp3file[1]";
			echo $q2 . "\n";
			mysql_query ( $q2, $ldb );
		}
	}
	exit ();
}

if ($action == 'fix-unfiled-tracks') {
	
	$query = "select uid,file from mp3file where genre = 126";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		$mp3 = $mp3file [1];
		$extension = strtolower ( substr ( $mp3, - 3 ) );
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
			// $id1_track = $ThisFileInfo ['tags'] ['id3v1'] ['track_number'] [0];
			$id1_genre = $ThisFileInfo ['tags'] ['id3v1'] ['genre'] [0];
			
			// Data fro ID3V2 tag
			$id2_artist = $ThisFileInfo ['tags'] ['id3v2'] ['artist'] [0];
			$id2_album = $ThisFileInfo ['tags'] ['id3v2'] ['album'] [0];
			$id2_title = $ThisFileInfo ['tags'] ['id3v2'] ['title'] [0];
			// $id2_track = $ThisFileInfo ['tags'] ['id3v2'] ['track_number'] [0];
			$id2_genre = $ThisFileInfo ['tags'] ['id3v2'] ['genre'] [0];
			
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
			if ($genre != '') {
				if (is_numeric ( $genreid )) {
					// Force 'unfiled' if unknown
					if ($genreid == 255 || $genre == '') {
						$genreid = 126;
					}
				} else {
					$genres = preg_split ( '/[^a-z]/i', $genre );
					$genre = $genres [0];
					echo "genre = $genre\n";
					
					$q2 = "select uid from genre where gname like '$genre%'";
					$grecs = mysql_query ( $q2, $ldb );
					if (mysql_num_rows ( $grecs ) > 0) {
						$grec = mysql_fetch_row ( $grecs );
						$genreid = $grec [0];
						$q2 = "update mp3file set genre = $genreid where uid = $mp3file[0]";
						echo "$q2\n";
						mysql_query ( $q2, $ldb );
					} else {
						$genreid = 126;
					}
				}
			}
			if ($genreid != 126) {
				echo "File = " . $mp3file [1] . "\n";
				echo "Title = $title\n";
				echo "Artist = $artist\n";
				echo "Album = $album\n";
				echo "track = $track\n";
				echo "genre = $genre\n";
				echo "genreid = $genreid\n";
				echo "duration = $duration\n\n";
			} else {
				echo ".";
			}
		} // end mp3
		if ($extension == "m4a") {
			$getID3 = null;
			$getID3 = new getID3 ();
			$getID3->option_no_iconv = true;
			$ThisFileInfo = $getID3->analyze ( "$mp3" );
			$artist = $ThisFileInfo ['tags'] ['quicktime'] ['artist'] [0];
			$album = $ThisFileInfo ['tags'] ['quicktime'] ['album'] [0];
			$title = $ThisFileInfo ['tags'] ['quicktime'] ['title'] [0];
			$track = $ThisFileInfo ['tags'] ['quicktime'] ['track_number'] [0];
			$genre = $ThisFileInfo ['tags'] ['quicktime'] ['genre'] [0];
			
			if (strlen ( $track ) > 2) {
				$track = substr ( $track, 0, 2 );
			}
			$track = $track + 0;
			
			// print_r ( $ThisFileInfo );
			
			echo "File = " . $mp3file [1] . "\n";
			echo "Title = $title\n";
			echo "Artist = $artist\n";
			echo "Album = $album\n";
			echo "track = $track\n";
			echo "genre = $genre\n";
			
			if ($genre != '') {
				$genres = preg_split ( '/[^a-z]/i', $genre );
				$genre = $genres [0];
				echo "genre = $genre\n";
				
				$q2 = "select uid from genre where gname like '$genre%'";
				$grecs = mysql_query ( $q2, $ldb );
				if (mysql_num_rows ( $grecs ) > 0) {
					$grec = mysql_fetch_row ( $grecs );
					$genreid = $grec [0];
					$q2 = "update mp3file set genre = $genreid where uid = $mp3file[0]";
					echo "$q2\n";
					mysql_query ( $q2, $ldb );
				} else {
					$genreid = 126;
				}
			}
			echo "File = " . $mp3file [1] . " genreid $genreid\n";
		}
	}
	
	exit ();
}

// Find import files that match master music files and copy to correct location
if ($action == 'hampton-merge') {
	$query = "select m1.uid,mp3file.uid,m1.file,mp3file.file,album.cdtag 
  from m1,mp3file,album
  where m1.md5=mp3file.md5 
  and album.uid=m1.albumid
  and m1.file not like \"/music/import%\" 
  and mp3file.file like \"/music/import%\"";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		$destuid = $mp3file [0];
		$srcuid = $mp3file [1];
		$destfile = $mp3file [2];
		$srcfile = $mp3file [3];
		$cdtag = $mp3file [4];
		if (! file_exists ( $srcfile )) {
			continue;
		}
		makeMyDir ( $cdtag );
		$cmd = "mv \"$srcfile\" \"$destfile\"";
		echo "$cmd $cr \n";
		system ( $cmd );
		$query = "update mp3file set file = \"$destfile\" where uid=$srcuid";
		echo "$query $cr \n";
		mysql_query ( $query, $ldb );
	}
}

// add echodata to tracks

if ($action == 'add_edata_tracks') {
	/*
	 * $query = " select
	 * track.uid, track.url, dispname, title
	 * from track,artist
	 * where ((dispname = 'Various' or dispname = 'Unknown Artist' or dispname = 'Compilation' or dispname = 'MYCHOTR')
	 * or track.title = 'unknown title')
	 * and artist.uid=track.artistid
	 * ";
	 * echo "\n\n$query $cr \n";
	 */
	$query = " select
  	mp3file.uid, file, dispname, title
  	from mp3file left join artist on artist.uid=mp3file.artistid
  	where ((dispname is null or dispname = 'Various' or dispname = 'Unknown Artist' or dispname = 'Compilation' or dispname = 'MYCHOTR')
  	or mp3file.title = 'unknown title')
  	and file like '/music/import%'
  	
  ";
	echo "\n\n$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		echo "File = $mp3file[1] $cr";
		$artist = $mp3file [2];
		$title = $mp3file [3];
		if (substr ( $title, 0, 5 ) == 'Track') {
			$title = '';
		}
		if (substr ( $title, 0, 13 ) == 'Unknown Title') {
			$title = '';
		}
		echo "File = $mp3file[1] Artist = $artist Title = $title\n";
		// getechodata will set artist and title, perhaps to ''
		getechodata ( $mp3file [1], $artist, $title );
		// If no match, abort
		if ($artist == '') {
			echo "No artist - aborting \n";
			continue;
		}
		if ($title == '') {
			echo "No title - aborting \n";
			continue;
		}
		$title = str_replace ( '"', "'", $title );
		$artist = str_replace ( '"', "'", $artist );
		$title = addslashes ( $title );
		$artist = addslashes ( $artist );
		// $query = "update track set t_artist = \"$artist\", t_title = \"$title\" where uid=$mp3file[0]";
		$query = "update mp3file set t_artist = \"$artist\", t_title = \"$title\" where uid=$mp3file[0]";
		echo "$query \n";
		mysql_query ( $query, $ldb );
	}
}

// Fix tracks where artist is 'various' (we know song title)

if ($action == 'fix_various') {
	$query = " select 
  	track.uid, track.url, dispname, title
  	from track,artist 
  	where (dispname = 'Various' or dispname = 'Unknown Artist' or dispname = 'Compilation' or dispname = 'MYCHOTR')
  	and artist.uid=track.artistid
  	";
	echo "\n$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		echo "File = $mp3file[1] $cr";
		$file_artist = $mp3file [2];
		$file_title = $mp3file [3];
		if (substr ( $file_title, 0, 5 ) == 'Track') {
			$file_title = '';
		}
		if (substr ( $file_title, 0, 13 ) == 'Unknown Title') {
			$file_title = '';
		}
		$artist = $file_artist;
		$title = $file_title;
		echo "File = $mp3file[1] Artist = $artist Title = $title\n";
		// getechodata will set artist and title, perhaps to ''
		getechodata ( $mp3file [1], $artist & $title );
		// If no match, abort
		if ($artist == '') {
			echo "No artist - aborting \n";
			continue;
		}
		if ($title == '') {
			echo "No title - aborting \n";
			continue;
		}
		
		$title = str_replace ( '"', "'", $title );
		$artist = str_replace ( '"', "'", $artist );
		// Title manipulation: title could be combined with artist, separated by /
		if (strpos ( $mp3file [3], '/' )) {
			$tparts = explode ( '/', $mp3file [3] );
			$tparts [0] = trim ( $tparts [0] );
			$tparts [1] = trim ( $tparts [1] );
			if (strtolower ( $title ) != strtolower ( $tparts [0] ) && strtolower ( $title ) != strtolower ( $tparts [1] )) {
				echo "Found title $title, correct title $tparts[0] or $tparts[1] - aborting \n";
				continue;
			}
		} else {
			// If title doesn't match, abort
			if ($file_title != '' && (strtolower ( $title ) != strtolower ( $file_title ))) {
				echo "Found title $title, correct title $file_title - aborting \n";
				continue;
			}
		}
		$artist = addslashes ( $artist );
		echo "Testing: $artist \n";
		$query = "select uid from artist where dispname = '$artist'";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			$title = addslashes ( $title );
			echo "New Artist = $artist $cr";
			$query = "update track set artistid = $myrow[0], title = \"$title\" where uid=$mp3file[0]";
			echo "$query $cr";
			mysql_query ( $query, $ldb );
		}
	}
}

// Fix tracks where track name is 'Track x'

if ($action == 'fix_track') {
	$query = " select
  	track.uid, track.url, dispname, title 
  	from track,artist 
  	where track.title like 'track%' 
  	and length(title) < 20 
  	and artist.uid=track.artistid
  	";
	echo "\n\n$query \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		// echo "File = $mp3file[1] \n";
		$artist = $mp3file [2];
		$title = $mp3file [3];
		echo "File = $mp3file[1] Artist = $artist Title=$title\n";
		// getechodata will set artist and title, perhaps to ''
		getechodata ( $mp3file [1], $artist, $title );
		// If no match, abort
		if ($artist == '') {
			echo "\nNo artist - aborting \n";
			continue;
		}
		if ($title == '') {
			echo "\nNo title - aborting \n";
			continue;
		}
		// If artists don't match, abort
		if ($artist != $mp3file [2] && $mp3file [2] != 'Various' && $mp3file [2] != 'Unknown Artist' && $mp3file [2] != 'Compilation' && $mp3file [2] != 'MYCHOTR') {
			echo "\nFound artist $artist, correct artist $mp3file[2] - aborting \n";
			continue;
		}
		$title = str_replace ( '"', "'", $title );
		
		$title = addslashes ( $title );
		$query = "update track set title = \"$title\" where uid=$mp3file[0]";
		echo "$query \n";
		mysql_query ( $query, $ldb );
	}
}

// Look up unknown track on echonest
// echonest api = 1OGOZYTRGZ0W2RUDC

if ($action == 'echonest') {
	$query = "select 
  	uid,file,artist,title from mp3file
    where trackid=0 
    and (title = '' or artist = '')
    and file like '/music/import/%'
    ";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		$artist = $mp3file [2];
		$title = $mp3file [3];
		$title = str_replace ( '"', "'", $title );
		$artist = str_replace ( '"', "'", $artist );
		// getechodata will set artist and title, perhaps to ''
		getechodata ( $mp3file [1], $artist, $title );
		// If we already have artist and doesn't match, abort
		if ($artist != '' && $artist != $mp3file [2] && $mp3file [2] != '') {
			echo "mp3 artist = $mp3file[2], guessed $artist - aborting $cr";
			continue (1);
		}
		// if artist was blank and now has a value......
		if ($artist != '' && $mp3file [2] == '') {
			$artist = addslashes ( $artist );
			$query = "update mp3file set artist = \"$artist\" where uid=$mp3file[0]";
			echo "$query $cr";
			mysql_query ( $query, $ldb );
		}
		// If we didn't have an artist and still don't....
		if ($artist == '' && $mp3file [2] == '') {
			// No Artist - mark for deletion
			$query = "update mp3file set artist = \"Unknown Artist\" where uid=$mp3file[0]";
			echo "$query $cr";
			mysql_query ( $query, $ldb );
		}
		
		// Now try title.....
		// If we already have title and doesn't match, abort
		if ($title != '' && $title != $mp3file [3] && $mp3file [3] != '') {
			echo "mp3 title = $mp3file[3], guessed $title - aborting $cr";
			continue (1);
		}
		if ($title != '' && $mp3file [2] == '') {
			$title = addslashes ( $title );
			$query = "update mp3file set title = \"$title\" where uid=$mp3file[0]";
			echo "$query $cr";
			mysql_query ( $query, $ldb );
		} else {
			// No title - mark for deletion
			$query = "update mp3file set title = \"Unknown Title\" where uid=$mp3file[0]";
			echo "$query $cr";
			mysql_query ( $query, $ldb );
		}
	}
}

if ($action == 'import') {
	
	// Find an mp3 file...
	
	array (
			$fileparts 
	);
	array (
			$fparts 
	);
	$i = 0;
	// $query = "select file,artistid,albumid,rdate,title,bgenre,track,trackid,uid,album,artist from mp3file where trackid=0 limit 1";
	
	// Version to process only files with titles and artists
	$query = "select file,artistid,albumid,rdate,title,genre,track,trackid,uid,album,artist,bitrate,duration from mp3file
    where trackid=0 
    and title != '' 
    and artist != '' 
    and file like '/music/import/%'
    ";
	
	// $query = "select file,artistid,albumid,rdate,title,bgenre,track,trackid,uid,album,artist from mp3file where trackid=0 and title != '' and album != '' and artist != '' limit 10";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		
		$file = $mp3file [0];
		$artistid = $mp3file [1];
		$albumid = $mp3file [2];
		$rdate = $mp3file [3];
		$title = $mp3file [4];
		$jbgenre = $mp3file [5];
		$track = $mp3file [6];
		$trackid = $mp3file [7];
		$mp3uid = $mp3file [8];
		$album = $mp3file [9];
		$artist = $mp3file [10];
		$bitrate = $mp3file [11];
		$duration = $mp3file [12];
		
		echo "$file $cr \n";
		echo "Album '$album' by $artist \n";
		
		// Get genre
		// $query = "select uid from genre where gname = '$jbgenre'";
		// $result = mysql_query ( $query, $ldb );
		// if (mysql_num_rows ( $result ) == 1) {
		// $myrow = mysql_fetch_row ( $result );
		// $jbgenre = $myrow [0];
		// } else {
		// unfiled
		// $jbgenre = 126;
		// }
		if ($jbgenre == '') {
			$jbgenre = 126;
		}
		if ($track == 0) {
			$track = 1;
		}
		
		// Get artist id
		if ($artistid == 0 || $artistid == '') {
			// Get artist if matching but don't create new artist
			$artistid = getartist ( $artist, TRUE );
			if ($artistid == 0) {
				echo "No match for artist $artist $cr \n";
				continue;
			}
		}
		
		// Get album id
		if (($albumid == 0 || $albumid == '') && ($artistid != 0 && $artistid != '')) {
			$albumid = getalbum ( $album, $artistid, $jbgenre, TRUE );
		}
		
		if ($albumid == 0 || $albumid == '') {
			echo "No album ID.... $cr \n";
			continue;
		}
		
		// Get cdtag
		$query = "select cdtag,genre1 from album where uid=$albumid";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			$cdtag = $myrow [0];
			$jbgenre = $myrow [1];
			$cdtag = strtolower ( $cdtag );
		} else {
			echo "Failed to read album $albumid $cr \n";
			echo "$query $cr \n";
			continue;
		}
		
		// We now have an album record and directory.
		
		// Three possibilities:
		// 1) this is a new track - get track number and copy it into directory
		// 2) this is an MP3/m4a version of an existing WAV file - delete this and its record
		// 3) this is a duplicate of an existing mp3/m4a file - delete this and its record
		
		// If trackid is set, then it's either 2 or 3.
		
		$extension = substr ( $file, - 3 );
		echo "Extension = $extension $cr \n";
		
		if ($trackid == 0) {
			
			// New file. copy it and create track / listitem records
			$track = gettrack ( $albumid, $cdtag, $track );
			makeMyDir ( $cdtag );
			$target = MakeURL ( $cdtag, $track, $extension );
			echo "New $extension: Moving $file to $target\n";
			system ( "mv \"$file\" $target" );
			chown ( $target, "www-data" );
			chgrp ( $target, "webusers" );
			chmod ( $target, 0664 );
			
			$title = addslashes ( $title );
			$ftype = $extension == 'mp3' ? MP3 : M4A;
			$query = "insert into track (url,genre1,artistid,seq,rating,title,ftype,bitrate,duration) values ('$target',$jbgenre,$artistid,$track,50,'$title',$ftype,$bitrate,$duration)";
			$result = mysql_query ( $query, $ldb );
			$trackid = mysql_insert_id ();
			echo "$query\n";
			
			$query = "insert into listitem (albumid,trackid,seq) values ($albumid,$trackid,$track)";
			$result = mysql_query ( $query, $ldb );
			echo "$query\n";
			
			// Update mp3file record to reflect new url
			$query = "update mp3file set file='$target', trackid=$trackid, albumid=$albumid where uid = $mp3uid";
			$result = mysql_query ( $query, $ldb );
			echo "$query\n\n";
		} else {
			echo "Duplicates existing physical file: Deleting $file\n";
			// system ( "rm \"$file\" " );
			// Delete mp3file record
			$query = "delete from mp3file where uid = $mp3uid";
			// $result = mysql_query ( $query, $ldb );
			echo "$query\n";
			exit ();
		}
	}
	
	// delete_import_dups ();
	exit ();
}

// ********************************** Functions *******************************

// Get md5 sums for files with length matches
function getmd5() {
	global $ldb;
	global $cr;
	
	// $query = "select mp3file.uid,mp3file.file,m2.file
	// from mp3file,mp3file as m2
	// where m2.filesize=mp3file.filesize
	// and m2.uid != mp3file.uid
	// and mp3file.md5 is null
	// limit 1";
	$query = "select mp3file.uid,mp3file.file, md5 
  from mp3file 
  where mp3file.md5 is null;
  ";
	$result = mysql_query ( $query, $ldb );
	while ( $myrow = mysql_fetch_row ( $result ) ) {
		$md5 = md5_file ( "$myrow[1]" );
		$q2 = "update mp3file set md5 = '$md5' where uid=$myrow[0]";
		if ($md5 != '0') {
			mysql_query ( $q2, $ldb );
			echo "Update: $myrow[2] $q2 \n";
		} else {
			echo "Fail: $myrow[2] $q2 \n";
		}
	}
}
// Given a file, get track title and artist from echonest
function getechodata($file, &$artist, &$title) {
	array (
			$echodata 
	);
	$cmd = "codegen.Linux-i686 \"$file\" 30 40 > /tmp/echo_data 2>/dev/null";
	system ( $cmd );
	$cmd = "curl -F \"query=@/tmp/echo_data\" -F \"api_key=N6E4NIOVYMTHNDM8J\" \"http://developer.echonest.com/api/v4/song/identify\"";
	exec ( $cmd, $echodata );
	// echo "$echodata[0] $cr";
	// Get artist
	$artist = strstr ( $echodata [0], '"artist_name": "' );
	$eot = strpos ( $artist, '",' ) - 16;
	$artist = substr ( $artist, 16, $eot );
	// echo " $artist $cr";
	// Get song title
	$title = strstr ( $echodata [0], '"title": "' );
	$eot = strpos ( $title, '",' ) - 10;
	$title = substr ( $title, 10, $eot );
}

// Delete import files that match existing tracks....
function delete_import_dups() {
	global $ldb;
	$query = "select uid,file from mp3file 
  where trackid != 0 
  and title != ''  
  and artist != '' 
  and file like '/music/import/%'";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "rm \"$mp3file[1]\" " );
		echo "rm \"$mp3file[1]\"  $cr \n";
		$query = "delete from mp3file where uid = $mp3file[0]";
		$result = mysql_query ( $query, $ldb );
		// echo "$query $cr \n";
	}
	// Now delete import files with md5 matching non-import files
	$query = "select mp3file.uid, mp3file.file,m2.file 
  from mp3file,mp3file as m2 
  where mp3file.md5=m2.md5
  and mp3file.md5 is not null 
  and mp3file.uid != m2.uid
  and mp3file.file like '/music/import/%'
  and m2.file not like '/music/import/%'";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "rm \"$mp3file[1]\" " );
		echo "rm \"$mp3file[1]\"  $cr \n";
		$query = "delete from mp3file where uid = $mp3file[0]";
		$result = mysql_query ( $query, $ldb );
	}
	// Now delete import files with md5 matching hampton /home/shared files
	$query = "select mp3file.uid, mp3file.file,m2.file 
  from mp3file,mp3file as m2 
  where mp3file.md5=m2.md5
  and mp3file.md5 is not null 
  and mp3file.uid != m2.uid
  and mp3file.file like '/music/import/music/%'
  and m2.file not like '/music/import/music/%'";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "rm \"$mp3file[1]\" " );
		echo "rm \"$mp3file[1]\"  \n";
		$query = "delete from mp3file where uid = $mp3file[0]";
		$result = mysql_query ( $query, $ldb );
	}
	// Now delete import files with md5 matching other import files
	$query = "select mp3file.uid, mp3file.file,m2.file 
  from mp3file,mp3file as m2 
  where mp3file.md5=m2.md5
  and mp3file.md5 is not null 
  and mp3file.uid != m2.uid
  and mp3file.file like '/music/import/%'
  and m2.file like '/music/import/%'
  and m2.uid > mp3file.uid";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "rm \"$mp3file[1]\" " );
		echo "rm \"$mp3file[1]\"  \n";
		$query = "delete from mp3file where uid = $mp3file[0]";
		echo "$query\n";
		$result = mysql_query ( $query, $ldb );
	}
	// move import unknown artist & unknown title to trash
	$query = "select uid,file from mp3file where title = 'Unknown Title'  and artist = 'Unknown Artist' and file like '/music/import/%'";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "mv \"$mp3file[1]\" \"/music/trash\" " );
		echo "mv \"$mp3file[1]\"  \n";
		$query = "update mp3file set file= \"/music/trash$mp3file[1]\" where uid = $mp3file[0]";
		$result = mysql_query ( $query, $ldb );
		// echo "$query $cr \n";
		// exit;
	}
}

// Replace import files that match existing tracks with symlinks to the track file
function link_import_dups() {
	global $ldb;
	$query = "select mp3file.uid,file,url 
  	from mp3file, track
  	where mp3file.trackid=track.uid and file like '/music/import/%'";
	echo "$query $cr \n";
	$mp3files = mysql_query ( $query, $ldb );
	while ( $mp3file = mysql_fetch_row ( $mp3files ) ) {
		system ( "rm \"$mp3file[1]\" " );
		system ( "ln -s \"$mp3file[2]\" \"$mp3file[1]\" " );
		$query = "delete from mp3file where uid = $mp3file[0]";
		$result = mysql_query ( $query, $ldb );
		exit ();
	}
}

// Get collection ID for collection with today's date as name. Create if necessary.
function getcollectionid() {
	global $ldb;
	global $newcollid;
	if ($newcollid == NULL) {
		$cname = date ( "Ymd" );
		$query = "select uid from alist where title = '$cname'";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			$newcollid = $myrow [0];
		} else {
			$query = "insert into alist (title,type,ownerid) values ('$cname','Collection',0)";
			$result = mysql_query ( $query, $ldb );
			$newcollid = mysql_insert_id ();
		}
	}
	return $newcollid;
}
function getalbum($album, $artistid, $jbgenre, $create) {
	global $ldb;
	// If no album, create an album titled 'Unknown Album' for this artist
	if ($album == '') {
		$album = "Unknown Album";
		// echo "Skipping $mp3 [$title] for no album\n";
		// continue;
	}
	
	// Find album if any, create if none
	$sqlalbum = addslashes ( $album );
	$query = "select album.uid,title,cdtag from album,artistlink where artistlink.albumid=album.uid and title like '$sqlalbum%' and artistid=$artistid";
	echo "$query $cr \n";
	$result = mysql_query ( $query, $ldb );
	if ($myrow = mysql_fetch_row ( $result )) {
		echo "Album  [$myrow[1]] matches\n";
		$albumid = $myrow [0];
		return $albumid;
	}
	if ($create) {
		// No album: create one.
		$album = addslashes ( $album );
		$i = getnewalbumid ();
		$statusmask = JUKEBOX | NEWDIR;
		$query = "INSERT INTO album (uid,title,status,genre1) values ($i,\"$album\",$statusmask ,$jbgenre)";
		echo "$query $cr \n";
		
		// Fix cdtag
		$result = mysql_query ( $query, $ldb );
		$albumid = mysql_insert_id ();
		$cdtag = chr ( $albumid % 26 + 65 ) . chr ( ($albumid / 676) + 66 ) . chr ( (($albumid % 676) / 26) + 65 );
		$query = "update album set cdtag='$cdtag' where uid=$albumid";
		echo "$query $cr \n";
		$result = mysql_query ( $query, $ldb );
		
		// Create artistlink
		$query = "insert into artistlink values (NULL,$artistid,$albumid,1)";
		$result = mysql_query ( $query, $ldb );
		
		// Create alistitems to add album to today's collection and 'all albums'
		$cid = getcollectionid ();
		$query = "insert into alistitem (alistid,albumid) values ($cid,$albumid)";
		$result = mysql_query ( $query, $ldb );
		$query = "insert into alistitem (alistid,albumid) values (1,$albumid)";
		$result = mysql_query ( $query, $ldb );
		
		echo "No album for [$album]. Created new album and directory. [$cdtag]\n";
		// Create directory if needed
		makeMyDir ( $cdtag );
		return $albumid;
	} else {
		return (0);
	}
}
function gettrack($albumid, $cdtag, $seq) {
	global $ldb;
	
	// Got albumid and cdtag. Since directory already exists, we need to figure out track number for new mp3.
	// It may have a track number. If so, we use it. Else, we assign next higher seq number
	if ($seq == '') {
		$query = "select max(seq) from listitem where albumid=$albumid";
		$result = mysql_query ( $query, $ldb );
		if ($myrow = mysql_fetch_row ( $result )) {
			$track = $myrow [0];
			$track ++;
		} else {
			$track = 1;
		}
	} else {
		$track = $seq;
	}
	// Need to see if file exists. If so, try to find a good one.
	$target1 = MakeURL ( $cdtag, $track, 'mp3' );
	$target2 = MakeURL ( $cdtag, $track, 'm4a' );
	$j = 0;
	while ( (file_exists ( "$target1" ) || file_exists ( "$target2" )) && ($j ++ < 100) ) {
		$track ++;
		$target1 = MakeURL ( $cdtag, $track, 'mp3' );
		$target2 = MakeURL ( $cdtag, $track, 'm4a' );
	}
	return $track;
}

?>

</body>

</html>
