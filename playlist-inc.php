<?php

// Included from index.php when view=playlist

// Set defaults and get form variables

$plistuser =$_REQUEST['plistuser'];

if (!$sort =$_REQUEST['sort']){
  $sort = 'lastname';
}

$filter = "";

$maxlines = 50;				// Maximum lines to display
if (!$rpage =$_REQUEST['rpage']){
  $rpage = 0;				// Results page to display
}


// Create new playlist if that's how we were called
if ($_POST['newplaylist']){
  $title = addslashes($_POST[title]);
  echo $title;
  $query = "insert into album (title,status) values ('$title',128 | 1)";
  $result=doQuery($query);
  $albumid=mysqli_insert_id($db);
  $query = "insert into artistlink (albumid,artistid) values ($albumid,$_POST[author])";
  $result=doQuery($query);
  $cdtag = chr($albumid % 26+65) . chr(($albumid/676)+66) . chr((($albumid % 676)/26)+65);
  $query = "update album set cdtag='$cdtag' where uid=$albumid";
  $result=doQuery($query);
  $plistid = $albumid;
} // End create new playlist


// ************************************ Top navigation bar in working pane ************************
echo "<div id=cnav>\n";

echo "<table><tr>\n";

echo "<td>\n";

// Show / select playlist user

if ($plistuser == ''){
  $plistuser=$userid;
}
echo "<td class=black align=left>Playlists for user:  <form action=index.php method=post>\n";
echo "<input type=hidden name=view value=playlist>\n";
echo "<select name=plistuser onchange=submit()>\n";
$query = "select uid,dispname from artist where user='y' order by lastname";
$result = doQuery($query);

while ($myrow = mysqli_fetch_row($result)){
  $sel = $myrow[0] == $plistuser ? 'selected' : '';
  echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
}
echo "</select></form></td>\n";
  // New playlist form
  echo "<td class=black><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>\n";
  echo "<input type=hidden name=view value=playlist>\n";
  echo "<table border=0 cellpadding=0 cellspacing=0>\n";
  echo "<tr><td class=black colspan=2 align=center><b><u>Create New Playlist</u></b></td></tr>\n"; 
  echo "<tr><td class=black>Title:</td><td>\n";
  echo "<input type=text size=30 name=title ><input type=hidden name=display value=playlists></td></tr>\n";
  $result = doQuery("select uid,dispname from artist where user='Y'");
  echo "<tr><td class=black>Owner:</td><td><select name=author>\n";
  while ($myrow = mysqli_fetch_row($result)){
    $sel = $myrow[0] == $userid ? 'selected' : '';
    echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
  }
  echo "</select>\n";
  echo "<input type=submit name=newplaylist value='Submit'></td></tr>\n";
  echo "</table></form></td>";

echo "</tr></table></div>\n";

// Process search

$sql = "select album.uid, album.title, artist.uid, artist.lastname, artist.firstname
  from artist left join artistlink on artistlink.artistid=artist.uid
  left join album on artistlink.albumid=album.uid
  where artist.uid=$plistuser order by album.title";

// Working pane header - page selection and column heads

echo "<table> \n";
$usort = urlencode($sort);

// Check results. If more than maxlines, reformulate query and display page selection links
$result = doQuery($sql);
$rlines = mysqli_num_rows($result);
if($rlines > $maxlines){
  $sql .= " limit " . ($rpage * $maxlines) . ", " . $maxlines;
  $result = doQuery($sql);
  echo "<tr><td  colspan=5 align=center bgcolor=slategrey>Select Page: \n";
  for($i = 0; $i<=intval($rlines/$maxlines);$i++){
    if ($i == $rpage){
      echo " <b>$i</b>";
    }else{
      echo " <a  class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&srch=$srch&rpage=$i&sort=$usort>$i</a>\n";
    }
  }
  echo "</td></tr>\n";
}

echo "<tr>
  <th class=ul align=left bgcolor=lightgrey><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&onlyme=$onlyme&srch=$srch&sort=artist.lastname>Select</a></th>
  <th class=ul align=left bgcolor=lightgrey><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&onlyme=$onlyme&srch=$srch&sort=album.title>Album</a></th>
  </tr>\n";
  
// Working pane contents



while ($myrow = mysqli_fetch_row($result)) {
  // Show artists and albums
  printf("<tr>\n");
  printf(" <td class=ul bgcolor=white><a href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&plistid=$myrow[0]>Select</a></td>\n");
  // Album name is clickable - pops open album window.
  printf(" <td class=ul bgcolor=white><a class=menu 
    href=javascript:MakeAlbumWindow($myrow[0],'y')>$myrow[1]</a></td>\n");
  printf("</tr>\n");
}
