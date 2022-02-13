<?php


// Set status for this artist to 1 - means this one is the correct and preferred version, or is unique
if ($_GET['action'] == 'approve'){
  $query = "update artist set status=1 where uid=$_GET[uid]";
  $result = doquery($query);
}

// Replace 'from' with 'to'.
// Update all tracks pointing to 'from'
// update all artistlinks pointing to 'from'
// delete artist 'from'
// Approve 'to'
if ($_GET['action'] == 'replace'){
  $from = $_GET['from'];
  $to = $_GET['to'];
  // make sure that 'to' exists
  $query = "select uid from artist where uid=$to";
  $result = doquery($query);
  if(mysql_num_rows($result) == 0){
    echo "Invalid artist in replace: $to<br>\n";
    exit;
  }
  $query = "update track set artistid=$to where artistid=$from";
  $result = doquery($query);
  $query = "update artistlink set artistid=$to where artistid=$from";
  $result = doquery($query);
  $query = "delete from artist where uid=$from";
  $result = doquery($query);
  $query = "update artist set status=1 where uid=$to";
  $result = doquery($query);
}

if ($_GET['detail'] == 'fixalbumdups'){

  $query = "select a1.uid,a1.title,a2.uid,a2.title, dispname
    from album as a1, album as a2, artistlink as al1, artistlink as al2, artist
    where a1.uid=al1.albumid 
    and a2.uid=al2.albumid 
    and al1.artistid=al2.artistid 
    and a1.uid != a2.uid 
    and artist.uid=al2.artistid
    and substring(a1.title,1,25) = substring(a2.title,1,25)
    order by dispname,a1.title limit 10";
    $result = doquery($query);
  echo "<table>\n";
  while($a1 = mysql_fetch_row($result)){
    echo "<tr>
      <td>$a1[4]</td>
      <td>$a1[1]</td>
      <td>$a1[3]</td>
      </tr>\n";
  }
}




if ($_GET['detail'] == 'fixartist'){

  $query = "select uid, firstname, lastname, dispname from artist where status = 0 limit 1";
  $result = doquery($query);

  echo "<table>\n";
  while($a1 = mysql_fetch_row($result)){
    $query = "select 
      uid, firstname, lastname, dispname, status 
      from artist 
      where uid != $a1[0]
      and (match dispname against (\"$a1[2]\")
      or match dispname against (\"$a1[3]\")
      or lastname like \"%$a1[2]%\"
      or substring(\"$a1[2]\",1,25) = substring(lastname%1,25)) order by dispname";
    $r2 = doquery($query);
    // if no rows, original artist is unique
    //  echo "<tr><td colspan=8>$query</td></tr>\n";
    if(mysql_num_rows($r2) == 0){
      $query = "update artist set status=1 where uid=$a1[0]";
      doquery($query);
    }else{
      // possible dups
      echo "<tr>
	<td><b><a href=index.php?view=utils&detail=fixartist&action=approve&uid=$a1[0]>Approve</a></b></td>
	<td><b>$a1[0]</b></td>
	<td><b> First:</b></td><td><b> $a1[1]</b></td>
	<td><b> Second:</b></td><td><b> $a1[2]</b></td>
	<td><b> Displayname: </b></td><td><b>$a1[3]</b></td>
	</tr>\n";
      $bgc="white";
      while($a2 = mysql_fetch_row($r2)){
	$b1 = $a2[4] == 1 ? '<b>' : '';
	$b2 = $a2[4] == 1 ? '</b>' : '';
	//$bgc = $bgc == 'lightblue' ? 'white' : 'lightblue';
	echo "<tr>
	  <td bgcolor=$bgc><b><a href=index.php?view=utils&detail=fixartist&action=replace&to=$a2[0]&from=$a1[0]>Approve</a></b></td>
	  <td bgcolor=$bgc>$b1$a2[0]$b2</td>
	  <td bgcolor=$bgc>&nbsp;</td>
	  <td bgcolor=$bgc>$b1$a2[1]$b2</td>
	  <td bgcolor=$bgc>&nbsp;</td>
	  <td bgcolor=$bgc>$b1$a2[2]$b2</td>
	  <td bgcolor=$bgc>&nbsp;</td>
	  <td bgcolor=$bgc>$b1$a2[3]$b2</td>
	  </tr>\n";
      }
      echo "<tr><td colspan=8><hr></td></tr>\n";
    }
  }
  echo "</table>\n";
}