<?php

// Included from index.php when view=artists. Show nav options (artist starting with, search, genre)

// 'detail' is 'artist stating with', default is 'all'
if (! $detail = $_REQUEST [detail]) {
	$detail = 'all';
}

$noalbums = $_REQUEST[noalbums];
$maxlines = 50; // Maximum lines to display
if (! $rpage = $_REQUEST [rpage]) {
	$rpage = 1; // Results page to display
}

if (! $_REQUEST [sort]) {
	$sort = 'artist.lastname';
}else{
	$sort = $_REQUEST [sort];
}
$usort = urlencode ( $sort );

if($subview = $_REQUEST[subview]){
	include "artistdetail.php";
	exit;
}

$srch = $_REQUEST [srch];
$sphrase = "Showing ";
$filter = "";
if ($srch) {
	$sphrase .= " artists matching '$srch' ";
	$filter .= " and dispname like '%$srch%' ";
} else {
	if ($detail != 'all') {
		$sphrase .= " artists where last name starts with '$detail' ";
		$filter .= " and lastname like '$detail%' ";
	}else{
		$sphrase .= "all artists";
	}
}

// If user has clicked genre box(es) we'll have either single value or array
if (! is_array ( $_GET ['genre'] )) {
	$genre = array ();
} else {
	$genre = $_GET ['genre'];
	$genre_names = array ();
}

// Default is show level 1 genres (most used)
if (! $genre_level = $_REQUEST ['genre_level']) {
	$genre_level = 1;
}

// Build genre url
$genre_url = '';
foreach ( $genre as $g ) {
	$genre_url .= "&genre[]=$g";
}

if($noalbums != 'on'){
	$sphrase .= " with albums";
}

// ************************************ Top navigation bar in working pane ************************

// Navigation form
echo "<form name=navform action='" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . "' method='get'>\n";
echo "<table><tr>\n";

// Artist 'starting with' selection ends up in 'detail'

// Show a clickable link for every character that starts an artist name
echo "<td class=black>Artist starting with: </td>";
$query = "select distinct ucase(substr(lastname,1,1)) as li from artist";
$result = doquery ( $query );

echo "<td>";
while ( $myrow = mysqli_fetch_row ( $result ) ) {
	if ($myrow [0] != $detail) {
		echo "<a class=menu href=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) .
				 "?view=$view&detail=$myrow[0]&noalbums=$noalbums>$myrow[0]</a> \n";
	} else {
		echo "<b class=white>$myrow[0]</b> \n";
	}
}
echo "<a class=menu href=" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) .
		 "?view=$view&detail=all&noalbums=$noalbums>[All]</a> \n";
echo "</td>\n";

// Search box with 'songs' and 'all albums' checkboxes
echo "<td class=black valign=bottom align=right>";
echo "<input type=hidden name=detail value=\"$detail\">\n";
echo "Include artists with no albums? 
	<input type=checkbox onchange=submit() name=noalbums " . ($noalbums == "on" ? "checked" : "") . ">\n";
echo "<input type=text name=srch size=10 maxlength=80 value = '$srch'>\n";
echo "<input type=hidden name=view value=$view>\n";
echo "<input type=hidden name=sort value=$sort>\n";
echo "<input type=submit value=Search>\n";
echo "</td>\n";
echo "</tr>\n";

// ***************** show genre checkboxes **************************

echo "<tr>\n";

echo "<td colspan=3><table bgcolor=#d6e4f8 width=100%><tr>\n";

echo "<td rowspan=2 class=black>Limit by Genre<br>";
$chk = $genre_level == 1 ? 'checked' : '';
echo "1<input type=radio name=genre_level value=1 $chk onclick=submit()> ";
$chk = $genre_level == 2 ? 'checked' : '';
echo "2<input type=radio name=genre_level value=2 $chk onclick=submit()> ";
$chk = $genre_level == 3 ? 'checked' : '';
echo "3<input type=radio name=genre_level value=3 $chk onclick=submit()> ";
echo "</td>";

$query = "select uid,gname from genre where rank <= $genre_level order by gname";
$result = doquery ( $query );

$cols = 0;

while ( $myrow = mysqli_fetch_row ( $result ) ) {
	if (in_array ( $myrow [0], $genre )) {
		$sel = 'checked';
		$genre_names [] = $myrow [1];
		$bold = "<b>";
		$unbold = "</b>";
	} else {
		$bold = "";
		$unbold = "";
		$sel = '';
	}
	echo "<td align=right class=black>$bold$myrow[1]$unbold<input type=checkbox name=genre[] value=$myrow[0] onchange=submit() $sel></td>";
	if ($cols ++ == 7) {
		$cols = 0;
		echo "</tr><tr>\n";
	}
}
//echo "</table></td></form>\n";

echo "</tr>";
echo "</table>";
echo "</form>";


// *********************** Build SQL statements ************************************

echo "<table>";
echo "<tr>\n";

// If one or more genres specified, add genre to filter criteria and display phrase
if (is_array ( $genre_names )) {
	$glength = count ( $genre_names );
	// If single genre don't need parentheses
	$sphrase .= " with genre = ";
	if ($glength == 1) {
		$gfilter = " and genre.uid = $genre[0]";
		$sphrase .= "$genre_names[0] ";
	} else {
		// list genres in parenthesis with 'or'
		$gfilter = " and (";
		$sphrase .= "( ";
		for($i = 0; $i < $glength; $i ++) {
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
}

// Determine how many results there will be
//select count(*) from (select artist.uid, count(distinct artistlink.uid) as acount from artist left join genre on genre.uid=artist.agenre left join artistlink on artistlink.artistid=artist.uid where lastname like 'P%' group by artist.uid having acount > 0) a;

$query = "SELECT artist.uid, count(distinct artistlink.uid) as acount
	from artist left join genre on genre.uid=artist.agenre
	left join artistlink on artistlink.artistid=artist.uid
	where artist.user='N' ";
$query .= $filter . $gfilter;
$query .=" group by artist.uid ";
if(($noalbums != "on") && !$srch){
	$query .= "having acount > 0";
}
$query .= ") a";

$query = "SELECT count(*) from(" . $query;
//echo "$query <br>";

$result = doquery ( $query );
$myrow = mysqli_fetch_row ( $result );

$rlines = $myrow [0];
$lastpage = intval ( $rlines / $maxlines) +1;
$query = "SELECT artist.uid, dispname, firstname, lastname, status, 
	gname, count(distinct artistlink.uid) as acount
	from artist left join genre on genre.uid=artist.agenre 
	left join artistlink on artistlink.artistid=artist.uid 
	where artist.user='N' ";
//	left join talink on artist.uid=talink.aid";
	$query .= $filter . $gfilter;
$query .=" group by artist.uid ";
if(($noalbums != "on") && !$srch){
	$query .= " having acount > 0";
}
//$query .=" group by artist.uid having acount > 0";

$query .= " order by " . $sort;

if ($rlines > $maxlines) {
	$query .= " limit " . (($rpage-1) * $maxlines) . ", " . $maxlines;
}

//echo "$query <br>";
$result = doquery ( $query );

// *********** show display phrase  **************************

echo "<td class=black>";
echo "<form name=navform action='" . htmlentities ( $_SERVER ['PHP_SELF'], ENT_QUOTES ) . "' method='get'>\n";
echo "<i>$sphrase.</i>";

// Navigate lotsa pages
if($lastpage > 1){
	echo " <b>There are $lastpage pages of results.</b> Select Page: \n";

	if($rpage > 1){
		$tpage = $rpage - 1;
		echo "<input type=button value='<<' 
			onclick=\"window.location.href='index.php?view=$view&detail=$detail&noalbums=$noalbums&srch=$srch&rpage=1&sort=$usort$gstring';\">\n";
		echo "<input type=button value='<' onclick=\"window.location.href='index.php?view=$view&detail=$detail&noalbums=$noalbums&srch=$srch&rpage=$tpage&sort=$usort$gstring';\">\n";
	}

	echo "<input name=rpage value=$rpage size=4 onchange='submit();'>";
	echo "<input type=hidden name=view value=$view>";
	echo "<input type=hidden name=subview value=$subview>";
	echo "<input type=hidden name=srch value=\"$srch\">";
	echo "<input type=hidden name=detail value=\"$detail\">";
	echo "<input type=hidden name=sort value=\"$usort\">";
	echo "<input type=hidden name=noalbums value=$noalbums>";

	if($rpage < $lastpage){
		$tpage = $rpage + 1;
		echo "&nbsp;<input type=button value='>' onclick=\"window.location.href='index.php?view=$view&detail=$detail&allalbums=$allalbums&srch=$srch&rpage=$tpage&sort=$usort$gstring&noalbums=$noalbums';\">\n";
		echo " <input type=button value='>>' onclick=\"window.location.href='index.php?view=$view&detail=$detail&allalbums=$allalbums&srch=$srch&rpage=$lastpage&sort=$usort$gstring&noalbums=$noalbums';\">\n";
	}
	echo "</form>";
	echo "</td>";

	echo "</tr>";
}
echo "</table>\n";

// ***************** Working pane header - page selection and column heads ********************

echo "<table cellspacing=0 bgcolor=grey> \n";

// echo "$display_sql";

$srch = urlencode ( $srch );
echo "<tr>
  <th align=left class=ul bgcolor=lightgrey>Genre</th>
  <th align=left class=ul bgcolor=lightgrey>Albums</th>
  <th align=left class=ul bgcolor=lightgrey><a class=menu href=index.php?view=artists&noalbums=$noalbums&sort=artist.dispname&detail=$detail&allalbums=$allalbums&srch=$srch&rpage=$rpage>Display Name</a></th>
  <th align=left class=ul bgcolor=lightgrey>First Name</th>
  <th align=left class=ul bgcolor=lightgrey><a class=menu href=index.php?view=artists&noalbums=$noalbums&sort=artist.lastname&detail=$detail&allalbums=$allalbums&srch=$srch&rpage=$rpage>Last Name</a></th>";

echo "</tr>\n";

// Working pane contents

$bgc1="white";
while ( $myrow = mysqli_fetch_row ( $result ) ) {
	// Show artists
	printf ( "<tr>\n" );
	printf ( " <td class=ul bgcolor=$bgc1>$myrow[5]</td>\n" );
	printf ( " <td class=ul bgcolor=$bgc1>$myrow[6]</td>\n" );
	printf ( " <td class=ul bgcolor=$bgc1><a class=menu href=index.php?view=artists&subview=artist&artistid=$myrow[0]>$myrow[1]</a></td>\n" );
	printf ( " <td class=ul bgcolor=$bgc1>$myrow[2]</td>\n" );
	printf ( " <td class=ul bgcolor=$bgc1>$myrow[3]</td>\n" );
	printf ( "</tr>\n" );
}
