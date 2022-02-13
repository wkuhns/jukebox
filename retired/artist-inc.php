<?php

// Included from index.php when view=artist. Show artists and albums
// Detail is reserved for the first letter of artist name

    

// Set defaults and get form variables
$detail2 = $_GET[detail2];

if (!$detail = $_REQUEST[detail]){
  $detail = 'A';
}

if (!$sort =$_REQUEST[sort]){
  $sort = 'lastname';
}

$filter = "";

$allalbums = $_REQUEST[allalbums];

if ($allalbums == "on"){
  $filter = " where true ";
}else{
  $filter = " where alistitem.uid is not null ";
}

$maxlines = 50;				// Maximum lines to display
if (!$rpage =$_REQUEST[rpage]){
  $rpage = 0;				// Results page to display
}


// ************************************ Top navigation bar in working pane ************************
//echo "<div id=cnav>\n";

echo "<table width=100% ><tr>\n";

echo "<td>\n";
if ($srch=$_REQUEST[srch]){
  echo "Showing search results for '$srch' sorted by $sort<br>\n";

}else{
  echo "Artist starting with: ";
  $query = "select distinct ucase(substr(lastname,1,1)) as li from artist";
  $result = mysql_query($query,$db);
  while($myrow = mysql_fetch_row($result)){
    if($myrow[0] != $detail){
      echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$myrow[0]&sort=$sort&allalbums=$allalbums>$myrow[0]</a> \n";
    }else{
      echo "<b>$myrow[0]</b> \n";
    }
  }
  echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=all&sort=$sort>[All]</a> \n";
}
echo "</td>\n";

echo "<td valign=bottom align=right><form action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";
echo "Search for artists:<br>";
echo "<input type=hidden name=view value=artist>\n";
echo "<input type=hidden name=detail value=\"$detail\">\n";
echo "<input type=hidden name=sort value=\"$sort\">\n";
echo " All Albums: <input type=checkbox name=allalbums onchange=submit() " . ($allalbums == "on" ? "checked" : "") . ">\n";
echo "  <input type=text name=srch size=10 maxlength=80 value='$srch'>\n";
echo "  <input type=hidden name=view value=$view>\n";
echo "  <input type=submit value=Search>\n";
echo "</form></td>\n";

echo "</tr></table>\n";
//echo "</div>\n";

// Process search

$sql = "select album.uid, album.title, artist.uid, artist.lastname, artist.firstname, alistitem.uid
  from artist left join artistlink on artistlink.artistid=artist.uid
  left join album on artistlink.albumid=album.uid
  left join alistitem on album.uid=alistitem.albumid and alistitem.alistid=$collectionid ";

if ($srch=$_REQUEST[srch]){
  $filter .= " and  lastname like '%$srch%' ";
}else{
  if($detail != 'all'){
    $filter .= " and lastname like '$detail%'";
  }
}

$sql .= $filter;

$sql .= "and album.status & " . PLAYLIST . " = 0 and album.status & " . JUKEBOX . " = 1";

$sql .= " order by $sort";

//echo $sql;

// Working pane header - page selection and column heads

echo "<table width=100% cellspacing=1> \n";
$usort = urlencode($sort);
//echo $usort;

// Check results. If more than maxlines, reformulate query and display page selection links
$result = mysql_query($sql,$db);
$rlines = mysql_num_rows($result);
if($rlines > $maxlines){
  $sql .= " limit " . ($rpage * $maxlines) . ", " . $maxlines;
  $result = mysql_query($sql,$db);
  echo "<tr><td  colspan=5 align=center>Select Page: \n";
  for($i = 0; $i<=intval($rlines/$maxlines);$i++){
    if ($i == $rpage){
      echo " <b>$i</b>";
    }else{
      echo " <a  class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&srch=$srch&rpage=$i&allalbums=$allalbums&sort=$usort>$i</a>\n";
    }
  }
  echo "</td></tr>\n";
}

echo "<tr>
  <th align=left>Collection</th>
  <th align=left><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&allalbums=$allalbums&srch=$srch&sort=artist.lastname>Artist</a></th>
  <th align=left><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&allalbums=$allalbums&srch=$srch&sort=album.title>Album</a></th>
  </tr>\n";
  
// Working pane contents



while ($myrow = mysql_fetch_row($result)) {
  // Background is white for albums in current collection, light grey for others
  $bgc1 = $myrow[5] == '' ? "lightgrey" : "white";
  // Show artists and albums
  printf("<tr>\n");
  // Show 'Add' link if not in collection, 'Drop' link otherwise
  if($myrow[5] != ''){
    printf(" <td class=menu bgcolor=$bgc1 onclick=silentcmd('utilcmd.php?collectionid=$collectionid&action=collectiondrop&albumid=$myrow[0]','y')>Drop</td>\n");
  }else{
    printf(" <td class=menu bgcolor=$bgc1 onclick=silentcmd('utilcmd.php?collectionid=$collectionid&action=collectionadd&albumid=$myrow[0]','y')>Add</td>\n");
  }
  printf(" <td class=ul bgcolor=$bgc1>$myrow[4] $myrow[3]</td>\n");
  // Album name is clickable - pops open album window.
  printf(" <td class=ul bgcolor=$bgc1><a class=menu 
    href=javascript:MakeAlbumWindow($myrow[0],'n')>$myrow[1]</a></td>\n");
  printf("</tr>\n");
}

