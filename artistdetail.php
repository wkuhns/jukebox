<?php

// Included from index.php when view=artists. Show nav options (artist starting with, search, genre)

$artistid = $_REQUEST[artistid];

if ($_REQUEST[submit]) {
  $lastname = trim($_REQUEST[lastname]);
  $firstname = trim($_REQUEST[firstname]);
  $genre = $_REQUEST[genre];
  $status = $_REQUEST[status];

  $dispname = $firstname . " " . $lastname;
  $dispname = trim($dispname);
  $dispname = addslashes($dispname);
  $lastname = addslashes($lastname);
  $firstname = addslashes($firstname);
  $query = "UPDATE artist
	set lastname = \"$lastname\",
	firstname = \"$firstname\",
	dispname = \"$dispname\",
	status = $status,
	agenre = $genre
  where uid=$artistid";
  doquery($query);
}
// *********************** Build SQL statements ************************************

// Albums by this artist
$query = "SELECT count(albumid) from album, artistlink
	where album.uid=artistlink.albumid
	and (album.status & 128) = 0
	and artistid=$artistid";
//echo "$query <br>";

$result = doquery($query);
$myrow = mysqli_fetch_row($result);
$albumcount = $myrow[0];
$rlines = $myrow[0];

$query = "SELECT dispname, firstname, lastname, status, agenre, gname
	from artist, genre
	where artist.uid=$artistid
	and genre.uid=artist.agenre";
//echo "$query <br>";
$result = doquery($query);
$myrow = mysqli_fetch_row($result);
$dispname = $myrow[0];
$firstname = $myrow[1];
$lastname = $myrow[2];
$status = $myrow[3];
$genreUid = $myrow[4];
$genre = $myrow[5];

// *********************** Build SQL statements ************************************

// We need three sql statements:
// - one to display the selected albums (display_sql)
// - one to tell us how many there would be so we know how many pages (display_sql_ct)
// - another to provide a random playlist from them. (play_sql)

//echo "<tr>\n";

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

$play_sql = "SELECT $nptext_sql_flds,ftype,volume,url
	FROM artist,artistlink,genre,listitem,track
	left join ratings on track.uid=ratings.trackid,album
	left join alistitem on album.uid=alistitem.albumid ";

// Criteria applying to any/all sql statements
$criteria = "where artistlink.artistid=artist.uid
    and artistlink.albumid=album.uid
    and album.genre1=genre.uid
    and album.status & " . JUKEBOX . " = 1 ";

$play_sql .= $criteria;
$play_sql .= "and listitem.trackid=track.uid ";
$play_sql .= "and listitem.albumid=album.uid ";
//$play_sql .= "and track.pscore > 100 ";
$play_sql .= "and ratings.cscore > 20 ";

// echo $play_sql;

// Displaying albums
$display_sql_fields .= ", album.duration ";
$display_sql_db = "from artist,artistlink,genre,album ";
$display_sql_db .= "left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid ";
$display_sql_ct .= $criteria;
$filter .= " and artist.uid=$artistid and artistlink.rank = 1";

$display_sql_ct .= $filter;
$play_sql .= $filter;

$display_sql_ct .= " order by album.status & 128, album.title ";
$play_sql .= " order by rand() limit 75";
//echo "$play_sql";

// Check results. If more than maxlines, reformulate query and display page selection links
$display_sql = "select" . $display_sql_fields . $display_sql_db . $display_sql_ct;
//echo "$display_sql";

// Example display_sql

/*
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
//echo $count_sql;

$myrow = mysqli_fetch_row($result);

$rlines = $myrow[0];
$lastpage = intval($rlines / $maxlines) + 1;

if ($rlines > $maxlines) {
  $display_sql .= " limit " . (($rpage - 1) * $maxlines) . ", " . $maxlines;
}

$result = doquery($display_sql);

// *********** show artist name and 'Play' button **************************

$play_sql = urlencode($play_sql);

// Full width table. Working area on left, images on right. will have one row.
echo "<table><tr>";
// div to hold two tables - left side of working area
echo "<td class=black valign=top><div><table>";
echo "<tr><td class=black align=center colspan=5><b class=large>$dispname</b></td></tr>";
echo "<tr><td class=black colspan=4>Most common genre for this artist is '$genre'. ";
echo "<td class=black align=right><a href=index.php?view=artists&subview=artist&artistid=$artistid&edit=true>Edit</a> details for this artist.</td></tr>";
echo "<tr><td class=black align=left colspan=5><input type=button value=Play onclick=shellcmd('selection=all&sql=$play_sql')> Play random selections by $dispname.</td></tr>";

if ($_REQUEST[edit]) {
  // Editing fields
  echo "<tr><td class=black>";
  echo "<form action=index.php method='get'>\n";
  echo "<input type=hidden name=view value=artists>";
  echo "<input type=hidden name=subview value=artist>";
  echo "<input type=hidden name=artistid value=$artistid>";
  echo "First Name: <input max=50 name=firstname value=\"$firstname\">";
  echo "Last Name: <input max=50 name=lastname value=\"$lastname\">";

  $statusvals = array('Needs Review', 'Last/First Correct', 'Proper Format', 'Confirmed');
  echo "Status: <select name=status>";
  for ($s = 0; $s < 4; $s++) {
    $sel = $status == $s ? 'selected' : '';
    echo "<option value=$s $sel>$statusvals[$s]</option>\n";
  }
  echo "</select>\n";

  echo "Genre: <select name=genre>";
  $query = "select uid,gname from genre where rank<=4 order by gname";
  $gresult = doquery($query);
  while ($grow = mysqli_fetch_row($gresult)) {
    $sel = $genre == $grow[1] ? 'selected' : '';
    echo "<option value=$grow[0] $sel>$grow[1]</option>\n";
  }
  echo "</select>\n";

  echo "<input type=submit name=submit value=Submit>";
  echo "</td></tr>";
  echo "</form>";
}

echo "<tr><td class=black colspan=5>There ";

switch ($albumcount) {
case 0:
  echo "are no albums";
  break;
case 1:
  echo "is 1 album";
  break;
default:
  echo "are $albumcount albums";
}

echo " by $dispname on the Jukebox:";

echo "</td></tr>\n";

// Navigate lotsa pages
if ($lastpage > 1) {
  echo "<tr><td class=black colspan=5>\n";
  echo "<form name=navform action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";

  echo " <b>There are $lastpage pages of results.</b> Select Page: \n";

  if ($rpage > 1) {
    $tpage = $rpage - 1;
    echo "<input type=button value='<<'
			onclick=\"window.location.href='index.php?view=artists&subview=artist&noalbums=$noalbums&artistid=$artistid&rpage=1';\">\n";
    echo "<input type=button value='<'
			onclick=\"window.location.href='index.php?view=artists&subview=artist&noalbums=$noalbums&artistid=$artistid&rpage=$tpage';\">\n";
  }

  echo "<input name=rpage value=$rpage size=4 onchange='submit();'>";
  echo "<input type=hidden name=view value=$view>";
  echo "<input type=hidden name=subview value=$subview>";
  echo "<input type=hidden name=srch value=\"$srch\">";
  echo "<input type=hidden name=detail value=\"$detail\">";
  echo "<input type=hidden name=sort value=\"$usort\">";
  echo "<input type=hidden name=artistid value=\"$artistid\">";
  echo "<input type=hidden name=noalbums value=$noalbums>";

  if ($rpage < $lastpage) {
    $tpage = $rpage + 1;
    echo "&nbsp;<input type=button value='>'
			onclick=\"window.location.href='index.php?view=artists&subview=artist&noalbums=$noalbums&artistid=$artistid&rpage=$tpage';\">\n";
    echo " <input type=button value='>>'
			onclick=\"window.location.href='index.php?view=artists&subview=artist&noalbums=$noalbums&artistid=$artistid&rpage=$lastpage';\">\n";
  }
  echo "</form>";
  echo "</td>";
  echo "</tr>";
}
//echo "</table>\n";

// ***************** Working pane header - page selection and column heads ********************

//echo "<table cellspacing=0 width=100%> \n";

// echo "$display_sql";

$srch = urlencode($srch);
echo "<tr>
  <th class=ul>Tag</th>
  <th class=ul width=20%>Genre </th>
  <th class=ul> Collection</th>
  <th style='text-align:center;' class=ul> Play</th>
  <th class=ul>Album</th>";
echo "</tr>\n";

// Working pane contents

while ($myrow = mysqli_fetch_row($result)) {
  // Playlists in italics
  if ($myrow[8] == PLAYLIST) {
    // Albums list before playlists ('Collected Tracks'). If we just finished albums, make a blank line
    if ($it1 == "") {
      echo "<tr><td class=ul colspan=5><br>Tracks by this artist from other albums:</td></tr>";
    }
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

  // Show albums
  printf("<tr>\n");
  // Background is white for albums in current collection, light grey for others
  $bgc1 = $myrow[5] == '' ? "lightgrey" : "ivory";
  printf(" <td class=ul bgcolor=$bgc1>$it1 $b1 $myrow[6] $b2 $it2</td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$it1 $myrow[7] $it2</td>\n");

// Don't allow add/drop from all albums or all playlists
  if ($collectionid <= 2) {
    echo "<td class=ul bgcolor=$bgc1>&nbsp;</td>\n";
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
  echo "<img class=rtpad border=0 height=12px src=images/rnote.gif onclick=shellcmd('album=$myrow[0]&playmode=random&collid=$collectionid')><img border=0 height=12px src=images/snote.gif onclick=shellcmd('album=$myrow[0]&playmode=seq&collid=$collectionid')>";
  echo "</td>";

  // Album name is clickable - pops open album window.
  printf(
    " <td class=ul bgcolor=$bgc1>$it1 <a class=menu
    href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[1]</a> $it2</td>\n");

  printf("</tr>\n");
}

// Show other albums this artist appears on

// Albums this artist appears on

$query = "SELECT album.uid, album.cdtag, album.title, album.duration, genre.gname, alistitem.uid
	from artistlink, listitem, talink, genre,
	album left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid
	where album.uid=listitem.albumid
	and album.uid=artistlink.albumid
	and genre.uid=album.genre1
	and talink.tid=listitem.trackid
	and talink.aid=$artistid
	and (album.status & 128) = 0
	and artistlink.artistid != $artistid
	and artistlink.rank = 1
	group by album.uid";

$guestresult = doquery($query);

echo "<tr><td class=ul colspan=5><br>This artist also appears on these albums:</td></tr>";

while ($myrow = mysqli_fetch_row($guestresult)) {
  // Full albums bold
  if ($myrow[3] > 1800) {
    $b1 = "<b>";
    $b2 = "</b>";
  } else {
    $b1 = "";
    $b2 = "";
  }

  // Show albums
  printf("<tr>\n");
  // Background is white for albums in current collection, light grey for others
  $bgc1 = $myrow[5] == '' ? "lightgrey" : "ivory";

  printf(" <td class=ul bgcolor=$bgc1>$it1 $b1 $myrow[1] $b2 $it2</td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$it1 $myrow[4] $it2</td>\n");

  // Don't allow add/drop from all albums or all playlists
  if ($collectionid <= 2) {
    echo "<td class=ul bgcolor=$bgc1>&nbsp;</td>\n";
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
  echo "<img class=rtpad border=0 height=12px src=images/rnote.gif onclick=shellcmd('album=$myrow[0]&playmode=random&collid=$collectionid')><img border=0 height=12px src=images/snote.gif onclick=shellcmd('album=$myrow[0]&playmode=seq&collid=$collectionid')>";
  echo "</td>";

  // Album name is clickable - pops open album window.
  printf(
    " <td class=ul bgcolor=$bgc1>$it1 <a class=menu
    href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[2]</a> $it2</td>\n");

  printf("</tr>\n");
}
// End of div and td for left side.
echo "</table></div></td>";

$query = "SELECT cdtag, album.uid from album, artistlink
	where album.uid=artistlink.albumid
	and (album.status & 128) = 0
	and artistid=$artistid
	and (album.status & 16) = 16
	order by title desc limit 10";

$coverResult = doquery($query);
$offset = 0;
echo "<td align=left valign=top ><div style=\"position: relative;\">";
while ($crow = mysqli_fetch_row($coverResult)) {
  echo "<div class=cover align=left
			style=\" position: absolute; top: $offset" . "px; left: $offset" . "px;\" name=cover>";
  echo "<a href=javascript:MakeAlbumWindow($crow[1],'n')><img width=300 src=" . MakeRoot($crow[0]) . "cover.jpg></a>";
  echo "</div>";
  $offset += 40;
}
echo "</div></td>";
echo "</table>";

// Close containing div
echo "</div>";
