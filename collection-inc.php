<?php

// Included from index.php when view=home. Show common controls

// Set defaults and get form variables

$detail2 = $_GET[detail2];
if (!$viewcollectionid=$_REQUEST['viewcollectionid']){
  $viewcollectionid=$collectionid;
}

if (!$detail = $_REQUEST[detail]){
  $detail = 'all';
}

if (!$sort =$_REQUEST[sort]){
  $sort = 'artist.lastname';
  $sort2 = 'artist.lastname';
}

// Add additional sort columns

if ($sort == 'artist.lastname'){
  $sort2 = 'artist.lastname,album.title';
}

if ($sort == 'album.title'){
  $sort2 = 'album.title';
}

if ($sort == 'album.cdtag'){
  $sort2 = 'album.cdtag';
}

if ($sort == 'genre.gname'){
  $sort2 = 'genre.gname,artist.lastname,album.title';
}


$filter = "";

$maxlines = 50;				// Maximum lines to display
if (!$rpage =$_REQUEST[rpage]){
  $rpage = 0;				// Results page to display
}


// Create new collection if that's how we were called
if ($_POST[newcollection]){
  $query = "insert into alist (title,type,ownerid) values (\"$_POST[title]\",'Collection',$_POST[author])";
  $result=doQuery($query);
  $collectionid=mysqli_insert_id($db);
} // End create new collection


// ************************************ Top navigation bar in working pane ************************
//echo "<div id=cnav>\n";

echo "<table width=100%><tr>\n";

// Show / select collection user

if ($_REQUEST[collectionuser] == ''){
  $collectionuser=$userid;
}else{
  $collectionuser =$_REQUEST[collectionuser];
}
echo "<td class=black align=left>Collections for user:  <form action=index.php method=get>\n";
echo "<input type=hidden name=view value=collection>\n";
echo "<select name=collectionuser onchange=submit()>\n";
$query = "select uid,dispname from artist where user='y' order by lastname";
$result = doquery($query);

// Entry for 'System':
$sel = 0 == $collectionuser ? 'selected' : '';
echo "<option value=0 $sel>System</option>\n";

while ($myrow = mysqli_fetch_row($result)){
  $sel = $myrow[0] == $collectionuser ? 'selected' : '';
  echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
}
echo "</select></form></td>\n";

echo "<td class=black>"; 
echo "<form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=get>";
echo "<input type=hidden name=view value=$view>\n";
echo "<input type=hidden name=collectionuser value=$collectionuser>\n";
echo "<select name=viewcollectionid onchange=submit()>\n";
$query = "select uid,title from alist where ownerid=$collectionuser";
$result = doquery($query);

while ($myrow = mysqli_fetch_row($result)){
  $sel = $myrow[0] == $viewcollectionid ? 'selected' : '';
  echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
}
echo "</select>\n";

echo "  </form>\n";
echo "</td>";


  // New Collection form
  echo "<td class=black><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=get>\n";
  echo "<input type=hidden name=view value=collection>\n";
  echo "<table border=0 cellpadding=0 cellspacing=0>\n";
  echo "<tr><td class=black colspan=2 align=center><b><u>Create New Collection</u></b></td></tr>\n"; 
  echo "<tr><td class=black>Title:</td><td>\n";
  echo "<input type=text size=30 name=title ><input type=hidden name=display value=collections></td></tr>\n";
  $result = doquery("select uid,dispname from artist where user='Y'");
  echo "<tr><td class=black>Owner:</td><td><select name=author>\n";
  while ($myrow = mysqli_fetch_row($result)){
    $sel = $myrow[0] == $userid ? 'selected' : '';
    echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
  }
  echo "</select>\n";
  echo "<input type=submit name=newcollection value='Submit'></td></tr>\n";
  echo "</table></form></td>";

echo "</tr><tr>\n";

echo "<td class=black colspan=5>\n";

echo "Artist starting with: ";
$query = "select distinct ucase(substr(lastname,1,1)) as li from artist";
$result = doquery($query);
while($myrow = mysqli_fetch_row($result)){
  if($myrow[0] != $detail){
    echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . 
    "?view=$view&detail=$myrow[0]&sort=$sort&viewcollectionid=$viewcollectionid&collectionuser=$collectionuser>
    $myrow[0]</a> \n";
  }else{
    echo "<b>$myrow[0]</b> \n";
  }
}
echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . 
"?view=$view&detail=all&sort=$sort&viewcollectionid=$viewcollectionid&collectionuser=$collectionuser>
[All]</a> \n";
  
echo "</td>\n";

echo "</tr></table>\n";

// Process search
  $sql = "select album.uid, album.title, artist.uid, artist.lastname, artist.firstname, a2.uid, album.cdtag, gname 
    from artist,artistlink,genre,alistitem,album
    left join alistitem as a2 on album.uid=a2.albumid and a2.alistid=$collectionid
    where artistlink.artistid=artist.uid
    and artistlink.albumid=album.uid
    and alistitem.albumid=album.uid
    and alistitem.alistid=$viewcollectionid
    and album.genre1=genre.uid
    and album.status & " . PLAYLIST . " = 0
    and album.status & " . JUKEBOX . " = 1
    ";

  if ($srch=$_REQUEST[srch]){
    $filter .= " and ( match dispname against ('$srch') or match album.title against ('$srch') or album.cdtag='$srch' )";
  }else{
    if($detail != 'all'){
      $filter .= " and lastname like '$detail%'";
    }
  }

  $sql .= $filter;

  $sql .= " order by $sort2";
//echo $sql;

// Working pane header - page selection and column heads

echo "<table cellspacing=1 bgcolor=grey> \n";

//echo "<td  bgcolor=lightslategray colspan=5>\n";

$usort = urlencode($sort);

echo "<tr>\n";
// Check results. If more than maxlines, reformulate query and display page selection links
$result = doquery($sql);
$rlines = mysqli_num_rows($result);
if($rlines > $maxlines){
  $sql .= " limit " . ($rpage * $maxlines) . ", " . $maxlines;
  $result = doquery($sql);
  echo "<tr><td class=black bgcolor=lightslategray colspan=5 align=center>Select Page: \n";
  for($i = 0; $i<=intval($rlines/$maxlines);$i++){
    if ($i == $rpage){
      echo " <b>$i</b>";
    }else{
      echo " <a  class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . 
      "?view=$view&viewcollectionid=$viewcollectionid&collectionuser=$collectionuser&songs=$songs&detail=$detail&allalbums=$allalbums&srch=$srch&rpage=$i&sort=$usort>$i</a>\n";
    }
  }
  echo "</td></tr>\n";
}

echo "<tr>
  <th class=ul align=left bgcolor=lightslategray >Collection</th>
  <th class=ul align=left bgcolor=lightslategray ><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&collectionuser=$collectionuser&viewcollectionid=$viewcollectionid&detail=$detail&allalbums=$allalbums&srch=$srch&sort=artist.lastname>Artist</a></th>
  <th class=ul align=left bgcolor=lightslategray ><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&collectionuser=$collectionuser&viewcollectionid=$viewcollectionid&detail=$detail&allalbums=$allalbums&srch=$srch&sort=album.title>Album</a></th>
  <th class=ul align=left bgcolor=lightslategray ><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&collectionuser=$collectionuser&viewcollectionid=$viewcollectionid&detail=$detail&allalbums=$allalbums&srch=$srch&sort=album.cdtag>Tag</a></th>
  <th class=ul align=left bgcolor=lightslategray ><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&collectionuser=$collectionuser&viewcollectionid=$viewcollectionid&detail=$detail&allalbums=$allalbums&srch=$srch&sort=genre.gname>Genre</a></th>";
  if ($songs == 'on'){
    echo "<th class=ul align=left bgcolor=lightslategray ><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&collectionuser=$collectionuser&viewcollectionid=$viewcollectionid&detail=$detail&allalbums=$allalbums&srch=$srch&sort=album.cdtag>Track</a></th>";
  }
  echo "</tr>\n";
  
// Working pane contents



while ($myrow = mysqli_fetch_row($result)) {
  // Show artists and albums
  printf("<tr>\n");
  // Background is white for albums in current collection, light grey for others
  $bgc1 = $myrow[5] == '' ? "lightgrey" : "white";
  if($myrow[5] != ''){
    printf(" <td class=blue bgcolor=$bgc1 
    onclick=silentcmd('utilcmd.php?sort=$sort&detail=$detail&viewcollectionid=$viewcollectionid&collectionid=$collectionid&action=collectiondrop&albumid=$myrow[0]','y')>
    <b>Drop</b></td>\n");
  }else{
    printf(" <td class=blue bgcolor=$bgc1 
    onclick=silentcmd('utilcmd.php?sort=$sort&detail=$detail&viewcollectionid=$viewcollectionid&collectionid=$collectionid&action=collectionadd&albumid=$myrow[0]','y')>
    <b>Add</b></td>\n");
  }
  printf(" <td class=ul bgcolor=$bgc1>$myrow[4] $myrow[3]</td>\n");
  // Album name is clickable - pops open album window.
  printf(" <td class=ul bgcolor=$bgc1><a class=menu 
    href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[1]</a></td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$myrow[6]</td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$myrow[7]</td>\n");
  if ($songs == 'on'){
    printf(" <td class=ul style='color: blue;' bgcolor=$bgc1 onclick=shellcmd('play=any&uid=$myrow[9]')>$myrow[8]</td>\n");
  }
  printf("</tr>\n");
}
