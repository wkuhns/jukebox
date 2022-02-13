<?php

// Top page. Display top and left nav and status sections, then include main page based on left nav

// Session header sets up key variables, includes 'phpfunctions
include_once "sessionheader.php";

include_once "jsfunctions.js";

// Start HTML......
?>
<html>
<head>
<title>New Music Database</title>
<link rel=stylesheet type=text/css href=jukebox.css>
</head>
<?php

// wininit() checks for MP3 player, starts nowplaying()
echo "<body onload=wininit()>\n";

// Top nav bar
echo "<div name=topleft style='float:left; position: relative; width:400px;'>";
echo "<table width=100%>";
// Show / select user
echo "<tr>";
echo "<td><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . " method=post>";
echo "<b>User: </b><input type=hidden name=view value=$view>\n";
echo "<select name=userid onchange=submit()>\n";

foreach ($lusers as $uid => $uname) {
  $sel = $uid == $userid ? 'selected' : '';
  echo "<option value=$uid $sel>$uname</option>\n";
}

echo "</select>\n";
echo "</form></td>\n";

// Show / select collection
echo "<td><form action=" . htmlentities($_SERVER['PHP_SELF'], ENT_QUOTES) . "  method=post>";
echo "<b>&nbsp;&nbsp;Collection: </b>\n";
echo "<select name=collectionid onchange=submit()>\n";
$query = "select uid,title from alist where ownerid=$userid or ownerid=0";
$result = doquery($query);

while ($myrow = mysqli_fetch_row($result)) {
  if ($myrow[0] == $collectionid) {
    $sel = 'selected';
    $collection_name = $myrow[1];
  } else {
    $sel = '';
  }
  echo "<option value=$myrow[0] $sel>$myrow[1]</option>\n";
}
echo "</select>\n";

echo "</form></td>\n";
echo "</tr>";

// Show current playlist
echo "<tr>";
echo "
    <td colspan=2 valign=center>Editing Playlist: <a class=lsblue href=javascript:MakeAlbumWindow($plistid,'n')>$plistname</a>\n";
echo "</td></tr></table>";

// Close left header container div
echo "</div>";

// Center Header div: Div to hold 'Now Playing' text
echo "<div id=nowplaying style='float:left; top:0; position: relative;'></div>\n";

// Right header container div
echo "<div style='position: relative; float:right; top:0; width:300px;'>";

/*
// MP3 player divs
echo "<div
id=mp3div
style='background-color: gray;
float:left;
position: relative;
font-size: small;
font-weight: bold;
border-width: 1px;
border-style: solid;
padding: 2px;
border-color: black;'
onclick=mp3manager('mount')> Find MP3 </div>";
echo "<div id=mp3actiondiv
style='background-color: gray;
float:left;
position: relative;
font-size: small;
font-weight: bold;
border-width: 1px;
border-style: solid;
padding: 2px;
margin-left: 5px;
border-color: black;'
onclick=mp3manager('unmount')></div>";
 */

// div for skip/end buttons
echo "<div style='float:right; top:0; position: relative;'>

  <input type=button value=Skip onclick=shellcmd('Skip=x')>
  <input type=button value=End onclick=shellcmd('Eject=x')>";

echo "</center>";

echo "  </div>\n";

echo "</div>\n";

// Div for balance of page
echo "<div style='clear:both; position:relative; width:100%;'>";
// *************************** Left Menu *******************************************

// Div for left nav pane
echo "<div style=' clear:both; float:left; position: relative; width:150px;'>\n";

// Home (Albums)
echo "<div width=20% class=" . ($view == 'home' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'home' ? 'menu2sel' : 'menu2') . " href=index.php?view=home$genre_url>Home</b></a></div>\n";

// Artists
echo "<div width=20% class=" . ($view == 'artists' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'artists' ? 'menu2sel' : 'menu2') . " href=index.php?view=artists>Artists</b></a></div>\n";

// Playlists
echo "<div width=20% class=" . ($view == 'playlist' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'playlist' ? 'menu2sel' : 'menu2') . " href=index.php?view=playlist>Playlists</b></a></div>\n";

// Collections
echo "<div width=20% class=" . ($view == 'collection' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'collection' ? 'menu2sel' : 'menu2') . " href=index.php?view=collection>Collections</b></a></div>\n";

/*// MP3 Player
echo "<div width=20% class=" . ($view=='mp3' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view=='mp3' ? 'menu2sel' : 'menu2') . " href=index.php?view=mp3>MP3 Player</b></a></div>\n";
 */
// utils
echo "<div width=20% class=" . ($view == 'utils' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'utils' ? 'menu2sel' : 'menu2') . " href=index.php?view=utils>Maintenance</b></a></div>\n";

// about
echo "<div width=20% class=" . ($view == 'about' ? 'inset' : 'outset') . "  align=center>\n";
echo "<b><a class=" . ($view == 'about' ? 'menu2sel' : 'menu2') . " href=index.php?view=about>About</b></a></div>\n";

// End of nav pane
echo "</div>\n";

// ******************************* Content *********************************************

echo "<div style='position:absolute; color: black;  border-style: inset; border-width: 3px 3px 3px 0px;
  float:left; background-color: ivory; left: 150; right:0; height: 1200px'>\n";

// ************************ Display selected view *****************************************

if ($view == 'home') {
  include "home-inc.php";
}

if ($view == 'artists') {
  include "artist-inc.php";
}

if ($view == 'playlist') {
  include "playlist-inc.php";
}

if ($view == 'collection') {
  include "collection-inc.php";
}

if ($view == 'mp3') {
  include "mp3-inc.php";
}

if ($view == 'about') {
  include "about-inc.php";
}

//if (!(gethostbyname("copper.cld.home")=="copper.cld.home")){
if ($view == 'utils') {
  include "maintenance-inc.php";
}
//}

//echo "</div>\n";
echo "</div>\n";

?>
</body>

</html>
