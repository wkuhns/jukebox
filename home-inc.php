<?php

// Included from index.php when view=home. Show nav options (artist starting with, search, genre)
// Display albums in current collection or as chosen via nav options

// Set defaults and get form variables

// Add additional sort columns

if ($sort == 'artist.lastname') {
  $sort2 = 'artist.lastname,artist.firstname,album.title';
}

if ($sort == 'album.title') {
  $sort2 = 'album.title';
}

if ($sort == 'album.cdtag') {
  $sort2 = 'album.cdtag';
}

if ($sort == 'genre.gname') {
  $sort2 = 'genre.gname,artist.lastname,artist.firstname,album.title';
}

// If not 'all albums', limit to current collection
if ($allalbums == "on") {

  $filter = " ";

} else {
  if ($notthis == "on") {
    $filter .= " and alistitem.albumid is null and album.status & 128 = 0 ";
  } else {
    $filter = " and alistitem.uid is not null ";
  }
}
$playfilter = $filter;

$maxlines = 50; // Maximum lines to display
if (!$rpage = $_REQUEST[rpage]) {
  $rpage = 1; // Results page to display
}

// ************************************ Top navigation bar in working pane ************************

// Navigation form
echo "<table width=100%><tr>\n";

// Artist 'starting with' selection ends up in 'detail'

// Show a clickable link for every character that starts an artist name
echo "<td class=black>\n";
echo "Artist starting with: ";
$query = "select distinct ucase(substr(lastname,1,1)) as li from artist";
$result = doquery($query);
while ($myrow = mysqli_fetch_row($result)) {
  if ($myrow[0] != $detail) {
    echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
      "?detail=$myrow[0]&genre=$genre_url>$myrow[0]</a> \n";
  } else {
    echo "<b class=white>$myrow[0]</b> \n";
  }
}
echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
  "?detail=all&genre=$genre_url>[All]</a> \n";

echo "</td>\n";

// Search box with 'songs' and 'all albums' checkboxes
echo "<td valign=bottom align=right class=black>";

echo "<form action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";
echo " Not This Collection: <input type=checkbox name=notthis onchange=submit() " . ($notthis == "on" ? "checked" : "") . ">\n";
echo " Songs: <input type=checkbox name=songs onchange=submit() " . ($songs == "on" ? "checked" : "") . ">\n";
echo " All Albums: <input type=checkbox name=allalbums onchange=submit() " . ($allalbums == "on" ? "checked" : "") . ">\n";
// so we can tell if form was submitted with unchecked checkboxes
echo "  <input type=hidden name=sform value=sform>";
echo "  <input type=text name=srch size=10 maxlength=80 value = '$srch'>\n";
echo "  <input type=submit value=Search>\n";
echo "</form>";
echo "</td>\n";
echo "</tr>\n";

// ***************** show genre checkboxes **************************
// separate form with genre_level in it
echo "<tr>\n";
echo "<form action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";

echo "<td colspan=2><table bgcolor=#d6e4f8 width=100%><tr>\n";

echo "<td rowspan=2 class=black>Limit by Genre<br>";
echo "  <input type=hidden name=gform value=gform>";
$chk = $genre_level == 1 ? 'checked' : '';
echo "1<input type=radio name=genre_level value=1 $chk onclick=submit()> ";
$chk = $genre_level == 2 ? 'checked' : '';
echo "2<input type=radio name=genre_level value=2 $chk onclick=submit()> ";
$chk = $genre_level == 3 ? 'checked' : '';
echo "3<input type=radio name=genre_level value=3 $chk onclick=submit()> ";
echo "</td>";

$query = "select uid,gname from genre where rank <= $genre_level order by gname";
$result = doquery($query);

$cols = 0;

while ($myrow = mysqli_fetch_row($result)) {
  if (in_array($myrow[0], $genre)) {
    $sel = 'checked';
    $genre_names[] = $myrow[1];
    $bold = "<b>";
    $unbold = "</b>";
  } else {
    $bold = "";
    $unbold = "";
    $sel = '';
  }
  echo "<td align=right class=black>$bold$myrow[1]$unbold<input type=checkbox name=genre[] value=$myrow[0] onchange=submit() $sel></td>";
  if ($cols++ == 7) {
    $cols = 0;
    echo "</tr><tr>\n";
  }
}
echo "</table></td></form>\n";

echo "</tr>";

// *********************** Build SQL statements ************************************

// User may be looking for either albums or songs, by search phrase or first letter of artist name.
// Results may be filtered by genre.

// We need three sql statements:
// - one to display the selected albums or songs (display_sql)
// - one to tell us how many there would be so we know how many pages (display_sql_ct)
// - another to provide a random playlist from them. (play_sql)

echo "<tr>\n";

// display_sql will be used to display selections to user. We need these fields
$display_sql_fields = "
	album.uid,
	album.title,
	artist.uid,
	artist.lastname,
	artist.firstname,
	alistitem.uid,
	album.cdtag,
	gname,
	album.status & " . PLAYLIST . " ";

// play_sql will provide the playlist

//  $play_sql = "select $nptext_sql_flds,ftype,volume,url
//    FROM artist,artistlink,genre,listitem,
//    track left join ratings on track.uid=ratings.trackid ";

$play_sql = "select $nptext_sql_flds,ftype,volume,url
		FROM artist, genre, listitem, artistlink, artist as a2,
		track left join ratings on track.uid=ratings.trackid ";

if ($allalbums != "on") {
  $play_sql .= "and ratings.collid=$collectionid, ";
} else {
  $play_sql .= ", ";
}
if ($allalbums == "on") {
  $play_sql .= "album left join alistitem on album.uid=alistitem.albumid ";
} else {
  $play_sql .= "album left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid ";
}

$play_sql .= "where artist.uid=track.artistid
  and artistlink.albumid=album.uid
  and a2.uid=artistlink.artistid
  and album.genre1=genre.uid
  and ((album.status & 1 = 1) or (album.title like 'Collected %'))
  and listitem.trackid=track.uid
  and listitem.albumid=album.uid ";

// Criteria applying to any/all sql statements
$criteria = "where artistlink.artistid=artist.uid
    and artistlink.albumid=album.uid
    and album.genre1=genre.uid
    and ((album.status & " . JUKEBOX . " = 1) or (album.title like 'Collected %'))";

// If we're playing within a collection, pay attention to collection owner's rating
// pscore is cscore + rand() * 100
if ($allalbums != "on" && $collectionid > 2 && $notthis != "on") {
  $play_sql .= "and (ratings.pscore is null or ratings.pscore > 100) ";
}

if ($songs == 'on') {
  // Displaying songs
  $display_sql_fields .= ", track.title, track.uid ";
  $display_sql_db = "from artist,artistlink,genre,listitem,track,album ";
  $display_sql_db .= "left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid ";
  $display_sql_ct = $criteria;
  $display_sql_ct .= "and listitem.trackid=track.uid ";
  $display_sql_ct .= "and listitem.albumid=album.uid ";
  if ($srch = $_REQUEST[srch]) {
    $filter .= " and (match track.title against ('$srch') or track.title like '$srch%') ";
    $playfilter .= " and (match track.title against ('$srch') or track.title like '$srch%') ";
  } else {
    if ($detail != 'all') {
      $filter .= " and lastname like '$detail%'";
      $playfilter .= " and artist.lastname like '$detail%'";
    }
  }
} else {
  // Displaying albums
  $display_sql_fields .= ", album.duration ";
  $display_sql_db = "from artist,artistlink,genre,album ";
  $display_sql_db .= "left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid ";

  $display_sql_ct .= $criteria;
  if ($srch = $_REQUEST[srch]) {
    $filter .= " and (match album.title against ('$srch')
      or match artist.dispname against ('$srch')
      or artist.dispname like '$srch%'
      or album.title like '$srch%'
      or album.cdtag='$srch')";
    $playfilter .= " and (match album.title against ('$srch')
      or match a2.dispname against ('$srch')
      or a2.dispname like '$srch%'
      or album.title like '$srch%'
      or album.cdtag='$srch')";
  } else {
    if ($detail != 'all') {
      $filter .= " and lastname like '$detail%'";
      $playfilter .= " and a2.lastname like '$detail%'";
    }
  }
}

// Start building display phrase
if ($songs == 'on') {
  $sphrase = "Showing songs ";
} else {
  $sphrase = "Showing albums ";
}

if ($allalbums == "on") {
  $sphrase .= "from All Albums ";
} else {
  $sphrase .= "from '$collection_name' ";
}

if ($srch) {
  $sphrase .= "with album or artist matching '$srch' ";
} else {
  if ($detail != 'all') {
    $sphrase .= "where artist starts with $detail ";
  }
}

// If one or more genres specified, add genre to filter criteria and display phrase
if (is_array($genre_names)) {
  $glength = count($genre_names);
  // If single genre don't need parentheses
  $sphrase .= " with genre = ";
  if ($glength == 1) {
    $gfilter = " and genre.uid = $genre[0]";
    $sphrase .= "$genre_names[0] ";
  } else {
    // list genres in parenthesis with 'or'
    $gfilter = " and (";
    $sphrase .= "( ";
    for ($i = 0; $i < $glength; $i++) {
      $gfilter .= "genre.uid = $genre[$i] ";
      $sphrase .= "$genre_names[$i] ";
      if ($i < ($glength - 1)) {
        $sphrase .= "or ";
        $gfilter .= "or ";
      }
    }
    $gfilter .= ") ";
    $sphrase .= ") ";
  }
  $display_sql_ct .= $gfilter;
  $play_sql .= $gfilter;
}

$display_sql_ct .= $filter;

$play_sql .= $playfilter;

$display_sql_ct .= " order by $sort2 ";
$play_sql .= " order by rand() limit 75";
//echo "[Play: $play_sql]<br>";
//exit;
$sphrase .= "sorted by $sort";

$usort = urlencode($sort);

// Check results. If more than maxlines, reformulate query and display page selection links
$display_sql = "select" . $display_sql_fields . $display_sql_db . $display_sql_ct;
//echo "[Display: $display_sql]";

// Example display_sql

/*

Play
select track.uid,track.title,album.uid,album.title,dispname,artist.lastname,cscore,artist.uid,ftype,volume,url
FROM artist,artistlink,genre,listitem,
track left join ratings on track.uid=ratings.trackid and ratings.collid=3,
album left join alistitem on album.uid=alistitem.albumid and alistitem.alistid = 3
where artistlink.artistid=artist.uid and artistlink.albumid=album.uid and album.genre1=genre.uid and ((album.status & 1 = 1) or (album.title like 'Collected %'))and listitem.trackid=track.uid and listitem.albumid=album.uid and track.pscore > 100 and ratings.cscore > 20 and alistitem.albumid is null order by rand() limit 75]

Display
select album.uid, album.title, artist.uid, artist.lastname, artist.firstname, alistitem.uid, album.cdtag, gname, album.status & 128 , album.duration
from artist,artistlink,genre,
album left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=3
where artistlink.artistid=artist.uid and artistlink.albumid=album.uid and album.genre1=genre.uid and ((album.status & 1 = 1) or (album.title like 'Collected %'))and alistitem.albumid is null order by artist.lastname,artist.firstname,album.title

select album.uid, album.title, artist.uid, artist.lastname, artist.firstname, alistitem.uid,
album.cdtag, gname, album.status & 128 , track.title, track.uid
from artist,artistlink,genre,listitem,track,
album left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=3
where artistlink.artistid=artist.uid
and artistlink.albumid=album.uid
and album.genre1=genre.uid
and album.status & 1 = 1
and listitem.trackid=track.uid
and listitem.albumid=album.uid
and alistitem.uid is not null
order by artist.lastname,artist.firstname,album.title
 */

$count_sql = "select count(album.uid)" . $display_sql_db . $display_sql_ct;

// $t1 = microtime(TRUE);
$result = doquery($count_sql);
// echo microtime(TRUE) - $t1;
// echo $count_sql;

$myrow = mysqli_fetch_row($result);

$rlines = $myrow[0];
$lastpage = intval($rlines / $maxlines) + 1;
$gstring = "";
foreach ($genre as $g) {
  $gstring .= "&genre[]=$g";
}

if ($rlines > $maxlines) {
  $display_sql .= " limit " . (($rpage - 1) * $maxlines) . ", " . $maxlines;
}
$result = doquery($display_sql);

// *********** show display phrase and 'Play' button **************************

$play_sql = urlencode($play_sql);

echo "<td class=black><input type=button value=Play onclick=shellcmd('selection=all&sql=$play_sql')>\n";
echo "<i>$sphrase.</i>";

// Navigate lotsa pages
if ($lastpage > 1) {
  echo " <b>There are $lastpage pages of results.</b> Select Page: \n";

  if ($rpage > 1) {
    $tpage = $rpage - 1;
    echo "<input type=button value='<<' onclick=\"window.location.href='index.php?srch=$srch&rpage=1&sort=$usort$gstring';\">\n";
    echo "<input type=button value='<' onclick=\"window.location.href='index.php?srch=$srch&rpage=$tpage&sort=$usort$gstring';\">\n";
  }

  echo "<input name=rpage value=$rpage size=4 onchange='submit();'>";

  if ($rpage < $lastpage) {
    $tpage = $rpage + 1;
    echo "&nbsp;<input type=button value='>' onclick=\"window.location.href='index.php?srch=$srch&rpage=$tpage&sort=$usort$gstring';\">\n";
    echo " <input type=button value='>>' onclick=\"window.location.href='index.php?srch=$srch&rpage=$lastpage&sort=$usort$gstring';\">\n";
  }

  echo "</td>";

  echo "</tr>";
}
echo "</table>\n";

// ***************** Working pane header - page selection and column heads ********************

echo "<table cellspacing=0 bgcolor=darkgrey> \n";

// echo "$display_sql";

$srch = urlencode($srch);
echo "<tr>
  <th align=left class=ul><a class=menu href=" .
htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
"?srch=$srch&sort=album.cdtag>Tag</a></th>
  <th align=left class=ul><a class=menu href=" .
htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
"?srch=$srch&sort=genre.gname>Genre</a></th>
	<th align=left class=ul bgcolor=lightgrey>Collection</th>
	<th style='text-align:center;' class=ul bgcolor=lightgrey>Play</th>
  <th align=left class=ul><a class=menu href=" .
htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
"?srch=$srch&sort=album.title>Album</a></th>
  <th align=left class=ul bgcolor=lightgrey><a class=menu href=" .
htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
  "?srch=$srch&sort=artist.lastname>Artist</a></th>";
if ($songs == 'on') {
  echo "<th align=left class=ul><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) .
    "?srch=$srch&sort=album.cdtag>Track</a></th>";
}
echo "</tr>\n";

// Working pane contents

while ($myrow = mysqli_fetch_row($result)) {
  // Playlists in italics
  if ($myrow[8] == PLAYLIST) {
    $it1 = "<i>";
    $it2 = "</i>";
  } else {
    $it1 = "";
    $it2 = "";
  }

  // Full albums bold
  if ($myrow[9] > 1800) {
    $b1 = "<b>";
    $b2 = "</b>";
  } else {
    $b1 = "";
    $b2 = "";
  }
  // Show artists and albums
  printf("<tr>\n");
  // Background is white for albums in current collection, light grey for others
  $bgc1 = $myrow[5] == '' ? "lightgrey" : "ivory";
  printf(" <td class=ul bgcolor=white>$it1 $b1 $myrow[6] $b2 $it2</td>\n");
  printf(" <td class=ul bgcolor=white>$it1 $myrow[7] $it2</td>\n");

  // Don't allow add/drop from all albums or all playlists
  if ($collectionid <= 2) {
    echo "<td class=ul >&nbsp;</td>\n";
  } else {
    if ($myrow[5] != '') {
      printf(
        " <td class=ul style='background-color:$bgc1;'
      onclick=silentcmd('utilcmd.php?collectionid=$collectionid&action=collectiondrop&albumid=$myrow[0]','y')>
      $it1 <b>Drop</b> $it2</td>\n");
    } else {
      printf(
        " <td class=ul style='background-color:$bgc1;'
      onclick=silentcmd('utilcmd.php?collectionid=$collectionid&action=collectionadd&albumid=$myrow[0]','y')>
      $it1 <b>Add</b> $it2 </td>\n");
    }
  }
  echo "<td class=ul>";
  echo "<img border=0 height=12px src=images/rnote.gif onclick=shellcmd('album=$myrow[0]&playmode=random&collid=$collectionid')>\n";
  echo "<img border=0 height=12px src=images/snote.gif onclick=shellcmd('album=$myrow[0]&playmode=seq&collid=$collectionid')>\n";
  echo "</td>";

  // Album name is clickable - pops open album window.
  printf(
    " <td class=ul bgcolor=white>$it1 <a class=menu
    href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[1]</a> $it2</td>\n");
  printf(" <td class=ul bgcolor=white>$it1
		<a class=menu href=index.php?view=artists&subview=artist&artistid=$myrow[2]>$myrow[4] $myrow[3]</a></td>\n");

  if ($songs == 'on') {
    printf(
      " <td class=ul style='color: blue;' bgcolor=white onclick=shellcmd('play=any&uid=$myrow[10]')>$myrow[9]</td>\n");
  }
  printf("</tr>\n");
}
