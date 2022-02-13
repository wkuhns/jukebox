<?php

echo "<h1>About Jukebox</h1>";
echo "<p>Jukebox is a MySQL/PHP application that organizes and plays music from a Linux server.";
echo "<h2>Artists</h2>";

// Artists
$query = "SELECT count(*) from artist where user='N'";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$artistCount = number_format($myrow[0]);

// Artists with albums
$query="SELECT count(*) 
	from(SELECT artist.uid, count(distinct artistlink.uid) 
	as acount from artist left join genre on genre.uid=artist.agenre 
	left join artistlink on artistlink.artistid=artist.uid  
	where artist.user='N' 
	group by artist.uid 
	having acount > 0) a";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$artistWithAlbumCount = number_format($myrow[0]);

echo "<p>There are <b>$artistCount</b> artists on the Jukebox. Of these, <b>$artistWithAlbumCount</b> have their own albums. The rest appear on albums with other artists.";

echo "<h2>Albums</h2>";
// Total albums
$query = "SELECT count(*) from album where (status & 128) = 0";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$albumCount = number_format($myrow[0]);

// Playlists
$query = "SELECT count(*) from album where title like 'Collected %'";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$collectionCount = number_format($myrow[0]);

// Full albums
$query = "SELECT count(*) from album where (status & 128) = 0 and duration > 1800";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$fullAlbumCount = number_format($myrow[0]);

echo "<p>There are <b>$albumCount</b> albums and <b>$collectionCount</b> 'Collected Tracks' playlists on the jukebox. The 'Collected Tracks' playlists are system-generated and contain tracks by an artist that are on albums by other artists. <br>Of the albums, <b>$fullAlbumCount</b> are 'full length' - 30 minutes or more of playing time. The other albums contain some, but likely not all, of the tracks from the album. These were mostly created when importing MP3 files.";

echo "<h2>Tracks</h2>";
// Tracks
$query = "SELECT count(*) from track";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$trackCount = number_format($myrow[0]);

// Tracks with WAV files
$query = "SELECT count(*) from track where (ftype & 1) = 1 ";
$result = doQuery($query);
$myrow = mysqli_fetch_row($result);
$wavCount = number_format($myrow[0]);

echo "<p>There are <b>$trackCount</b> tracks (songs) on the jukebox. Of these, <b>$wavCount</b> are full-fidelity uncompressed WAV files.";

echo "<h2>Genres</h2>";
// Genres
$query = "select count(distinct album.uid) as g, genre.gname from album, genre where album.genre1=genre.uid and album.duration>1800 group by genre.uid order by g desc";
$result = doQuery($query);
$genreCount = mysqli_num_rows($result);

echo "<p>There are albums from <b>$genreCount</b> different musical genres on the jukebox. The top ten genres for full albums are:";

echo "<table>";
echo "<tr><th class=ul align=left>Genre</th><th class=ul align=left>Albums</th></tr>";
for($i=0; $i<10; $i++){
	$myrow = mysqli_fetch_row($result);
	echo "<tr><td class=ul>$myrow[1]</td><td class=ul>$myrow[0]</td></tr>";
}
echo "</table>";

echo "<p>Most genres are assigned based on data from an on-line resource that is less than perfect, so there are many mis-categorized albums. <br>In particular, the 'Other' and 'Unfiled' genres contain albums that should be properly categorized.";





?>