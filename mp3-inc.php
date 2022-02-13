<script language="JavaScript">

  function mp3cmd(cmd) {
    if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp=new XMLHttpRequest();
    }
    xmlhttp.onreadystatechange=function() {
      if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	window.location.reload();
      }
    }
    mycmd = "mp3cmd.php?" + cmd;
    xmlhttp.open("GET",mycmd,true);
    xmlhttp.send();
  }

</script>


<?php

if (mp3path() == ''){
  echo "No MP3 player or iPod detected - click 'Find MP3' at top of page";
  exit;
}

// Included from index.php when view=mp3. Show contents of mp3 player
// Allow edits: delete tracks or delete all.

    

// Set defaults and get form variables
$detail2 = $_GET[detail2];

if (!$detail = $_REQUEST[detail]){
  $detail = 'A';
}

if (!$sort =$_REQUEST[sort]){
  $sort = 'artist,album,song';
}

$filter = "";

$allalbums = $_REQUEST[allalbums];

//$filter = " where true ";

/*
if ($allalbums == "on"){
  $filter = " where true ";
}else{
  $filter = " where alistitem.uid is not null ";
}
*/

$maxlines = 50;				// Maximum lines to display
if (!$rpage =$_REQUEST[rpage]){
  $rpage = 0;				// Results page to display
}

$ipodserial = ipodserial(mp3path());

//echo "mp3path = " . mp3path();
//echo "<br> ipodserial = $ipodserial";

// ************************************ Top navigation bar in working pane ************************
//echo "<div id=cnav>\n";

echo "<table width=100% ><tr>\n";

echo "<td>\n";
if ($srch=$_REQUEST[srch]){
  echo "Showing search results for '$srch' sorted by $sort<br>\n";

}else{
  echo "Artist starting with: ";
  $query = "select distinct ucase(substr(artist,1,1)) as li from mp3player where playerid='$ipodserial' order by li";
  $result = doQuery($query);
  while($myrow = mysqli_fetch_row($result)){
    if($myrow[0] != $detail){
      echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$myrow[0]&sort=$sort><b class=blue>$myrow[0]</b></a>\n";
    }else{
      echo "<b class=white>$myrow[0]</b> \n";
    }
  }
  echo "<a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=all&sort=$sort>[All]</a> \n";
}
echo "</td>\n";

$ipath = mp3path();
$device = mp3device($ipath);
$nt = mp3space($ipath);

echo "<td><b>$device with space for $nt more songs</td>\n";

echo "<td valign=bottom><form action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";
echo "<input type=hidden name=view value=mp3>\n";
echo "<input type=hidden name=detail value=\"$detail\">\n";
echo "<input type=hidden name=sort value=\"$sort\">\n";
echo "<input type=hidden name=srch value=\"$srch\">\n";
echo "</form></td>\n";

echo "<td align=center>\n";

echo "</td>\n";

echo "<td align=right>\n";
echo "<form action='" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "' method='get'>\n";
echo "  <input type=text name=srch size=10 maxlength=80>\n";
echo "  <input type=hidden name=view value=$view>\n";
echo "  <input type=submit value=Search>\n";
echo "</form></td>\n";

echo "</tr></table>\n";
//echo "</div>\n";

// Process search

$sql = "select mp3player.playertrack, song, artist, album, url from mp3player where playerid='$ipodserial'";
if ($srch=$_REQUEST[srch]){
  $filter .= " and  artist like '%$srch%' ";
}else{
  if($detail != 'all'){
    $filter .= " and artist like '$detail%'";
  }
}


$sql .= $filter;

$sql .= " order by $sort";

echo $sql;

// Working pane header - page selection and column heads

echo "<table width=100% cellspacing=1> \n";
$usort = urlencode($sort);
//echo $usort;

// Check results. If more than maxlines, reformulate query and display page selection links
$result = doQuery($sql);
$rlines = mysqli_num_rows($result);
if($rlines > $maxlines){
  $sql .= " limit " . ($rpage * $maxlines) . ", " . $maxlines;
  $result = doQuery($sql);
  echo "<tr><td  colspan=5 align=center>Select Page: \n";
  for($i = 0; $i<=intval($rlines/$maxlines);$i++){
    if ($i == $rpage){
      echo " <b>$i</b>";
    }else{
      echo " <a  class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&srch=$srch&rpage=$i&sort=$usort>$i</a>\n";
    }
  }
  echo "</td></tr>\n";
}

echo "<tr>
  <th align=left><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&onlyme=$onlyme&srch=$srch&sort=song>Track</a></th>
  <th align=left><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&onlyme=$onlyme&srch=$srch&sort=artist>Artist</a></th>
  <th align=left><a class=menu href=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "?view=$view&detail=$detail&onlyme=$onlyme&srch=$srch&sort=album>Album</a></th>
  </tr>\n";
  
// Working pane contents


while ($myrow = mysqli_fetch_row($result)) {
  $ext = substr($myrow[4],-3);
  // Show artists and albums
  printf("<tr>\n");
  // Background is white for albums in current collection, light grey for others
  $bgc1 = "white";
  printf(" <td class=ul bgcolor=$bgc1><img src=\"images/b_drop.png\" onclick=mp3cmd('deltrack=1&trackid=$myrow[0]')>\n");
  printf(" <img border=0 src=images/note.gif onclick=shellcmd('playurl=$ext&url=$myrow[4]')></a></td>\n");
  //printf(" <td class=ul bgcolor=$bgc1 width=20><img border=0 src=images/note.gif onclick=shellcmd('play=$ext&uid=$myrow[4]')></a></td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$myrow[1]</td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$myrow[2]</td>\n");
  printf(" <td class=ul bgcolor=$bgc1>$myrow[3]</td>\n");
  printf("</tr>\n");
}

