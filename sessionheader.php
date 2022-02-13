<?php
// Sessionheader file. Included at top of any script that creates contents in a new window
// Gets values for key variables, sets cookies. No HTML output.

include_once "phpfunctions.php";

//$db = getjukeboxdb($db);
$db = NULL;
$ldb = NULL;
getjukeboxdb();

// Cookie management. Cookies are set to last for about a month.
// General approach:
//   If cookie variable is passed to us, set cookie.
//   If cookie exists, refresh it. If not, set it to safe value
$cookie_life = time() + 3600 * 24 * 31;

$userid = handleFormVar("userid", "631");
$username = $_COOKIE["username"];
$plistid = $_COOKIE["plistid"];
$plistname = $_COOKIE["plistname"];

$view = handleFormVar("view", "search");
$detail = handleFormVar("detail", "all");
$sort = handleFormVar("sort", "artist.lastname");
$genre_level = handleFormVar("genre_level", "1");
$genre = handleFormVar("genre", "");
$rpage = handleFormVar("rpage", "1");
$collectionid = handleFormVar("collectionid", 1);

// Special handling for checkboxes. If we have their form submitted, set or clear based on form. Else process normally.

if ($_REQUEST[sform]) {
  $notthis = handleFormVar("notthis", "", true);
  $allalbums = handleFormVar("allalbums", "", true);
  $songs = handleFormVar("songs", "", true);
} else {
  $notthis = handleFormVar("notthis", "");
  $allalbums = handleFormVar("allalbums", "");
  $songs = handleFormVar("songs", "");
}

// Process genre (if any)
if ($_REQUEST[genre]) {
  if (!is_array($_GET['genre'])) {
    $genre = array();
  } else {
    $genre = $_GET['genre'];
    $genre_names = array();
  }

  // Build genre url
  $genre_url = '';
  foreach ($genre as $g) {
    $genre_url .= "&genre[]=$g";
  }
  setcookie("gurl", $genre_url, $cookie_life);
} else {
  // gform without genre - user has unclicked last genre box. Clear cookie.
  if ($_REQUEST[gform]) {
    setcookie("genre", "", $cookie_life);
    $genre = "";
    setcookie("gurl", "", $cookie_life);
    $genre_url = "";
  } else {
    // just use cookie
    $genre_url = $_COOKIE["gurl"];
  }
}

// Should we only use local db for queries? Starts out as 'Y' but set to 'N' when update is made.
// Cookie is good for 12 hours
$localdb = handleFormVar("localdb", "Y");

// Have we been passed a new value? Set cookie. Also delete collectionid cookie
if ($_REQUEST["userid"]) {
  setcookie("userid", $_POST["userid"], $cookie_life);
  setcookie("collectionid", '', time() - 3600);
  $userid = $_POST["userid"];
  unset($collectionid);
  $query = "select uid,title from alist where ownerid=$userid and uid > 2 limit 1";
  $result = mysqli_query($dbi, $query);
  if ($myrow = mysqli_fetch_row($result)) {
    $collectionid = $myrow[0];
  } else {
    $collectionid = 1;
  }
  setcookie("collectionid", $collectionid, $cookie_life);
} else {
  // No new value. Did cookie exist?
  if (!$userid) {
    foreach ($lusers as $userid => $username) {
      setcookie("userid", $userid, $cookie_life);
      setcookie("username", "$username", $cookie_life);
      continue (1);
    }
  }
}

// Collection id
if ($_REQUEST["collectionid"]) {
  // Randomize pscore for probability of selection during random play
  $query = "update ratings set pscore = (cscore +rand()*80) where collid=$collectionid";
  // always use local db
  mysqli_query($dbi, $query);
} else {
  if (!$collectionid) {
    setcookie("collectionid", 1, $cookie_life);
    $collectionid = 1;
  }
}

// has user changed database location?
if ($_GET[dbloc]) {
  if ($_GET[dbloc] == 'local') {
    setcookie("localdb", 'Y', time() + 3600 * 12);
    $localdb = 'Y';
  } else {
    setcookie("localdb", 'N', time() + 3600 * 12);
    $localdb = 'N';
  }
}

// Userid / username
// Get local users - file if present, else all users from db
if (!isset($localusers)) {
  $lusers = array();
  if (file_exists("../users.txt")) {
    $localusers = file("../users.txt");
    foreach ($localusers as $i => $firstname) {
      $firstname = rtrim($firstname);
      //echo "Firstname=$firstname<br>";
      $query = "select uid,dispname from artist where dispname='$firstname' and user='y' limit 1";
      $result = doquery($query);
      if ($myrow = mysqli_fetch_row($result)) {
        $lusers[$myrow[0]] = $myrow[1];
      }
    }
  } else {
    $query = "select uid,dispname from artist where user='y'";
    $result = doquery($query);
    while ($myrow = mysqli_fetch_row($result)) {
      $lusers[$myrow[0]] = $myrow[1];
    }
  }
}

// Playlist id - must have been changed
if ($_GET["plistid"]) {
  $query = "select title from album where uid=$_GET[plistid]";
  $result = doquery($query);
  $myrow = mysqli_fetch_row($result);
  setcookie("plistid", $_GET["plistid"], $cookie_life);
  $plistid = $_GET["plistid"];
  setcookie("plistname", "$myrow[0]", $cookie_life);
  $plistname = $myrow[0];
}

?>
