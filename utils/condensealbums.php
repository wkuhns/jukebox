<?php
include '../sessionheader.php';
include '../constants.php';

// Find empty slots and move high-numbered albums down

for($acount = 0; $acount < 100; $acount ++) {
	// $query = "select uid,cdtag from album where (status & 128) = 0 order by uid desc limit 100";
	$query = "select uid,cdtag from album order by uid desc limit 1";
	$result = doquery ( $query );
	while ( $myrow = mysql_fetch_row ( $result ) ) {
		$srcid = $myrow [0];
		$srctag = $myrow [1];
		// if (substr ( $srctag, 1, 1 ) == '[') {
		$destid = getnewalbumid ();
		if ($destid > $srcid) {
			echo ("no more empty slots");
			exit ();
		}
		echo "$destid ";
		$destcdtag = chr ( $destid % 26 + 65 ) . chr ( ($destid / 676) + 66 ) . chr ( (($destid % 676) / 26) + 65 );
		$srccdtag = strtolower ( $srctag );
		$srccdt1 = substr ( $srccdtag, 0, 1 );
		$srccdt2 = substr ( $srccdtag, 1, 1 );
		$srccdt3 = substr ( $srccdtag, 2, 1 );
		$srcdir = "/music/$srccdt1/$srccdt2/$srccdt3";
		$destcdtag = strtolower ( $destcdtag );
		$destcdt1 = substr ( $destcdtag, 0, 1 );
		$destcdt2 = substr ( $destcdtag, 1, 1 );
		$destcdt3 = substr ( $destcdtag, 2, 1 );
		$destdir = "/music/$destcdt1/$destcdt2/$destcdt3";
		echo "Candidate $srcid $srccdtag at $srcdir to $destid $destcdtag $destdir\n";
		// exit ();
		// If directory exists, try deleting it to make sure it's empty
		if (file_exists ( $destdir )) {
			if (! rmdir ( $destdir )) {
				// Can't delete it - problem!
				echo "Error: $destdir not empty or can't be deleted\n";
				mkdir ( "/music/import/$destcdt1/$destcdt2", 0777, true );
				rename ( $destdir, "/music/import/$destcdt1/$destcdt2/$destcdt3" );
			}
		}
		// Destination directory should have been deleted if it existed. Re-create.
		if (mkdir ( $destdir, 0777, TRUE )) {
			echo "Created $destdir\n";
		} else {
			echo "Error creating $destdir\n";
			exit ();
		}
		$query = "update alistitem set albumid=$destid where albumid=$srcid";
		doquery ( $query );
		echo "$query\n";
		$query = "update artistlink set albumid=$destid where albumid=$srcid";
		doquery ( $query );
		echo "$query\n";
		$query = "update listitem set albumid=$destid where albumid=$srcid";
		doquery ( $query );
		echo "$query\n";
		$query = "update mp3file set albumid=$destid where albumid=$srcid";
		doquery ( $query );
		echo "$query\n";
		$query = "update album set uid=$destid, cdtag = ucase('$destcdtag') where uid=$srcid";
		doquery ( $query );
		echo "$query\n";
		$query = "update track set url=concat('$destdir/',substring(url,14,20)) where url like '$srcdir/%'";
		doquery ( $query );
		echo "$query\n";
		$query = "update mp3file set file=concat('$destdir/',substring(file,14,20)) where file like '$srcdir/%'";
		doquery ( $query );
		echo "$query\n";
		$cmd = "mv $srcdir/* $destdir";
		echo "$cmd\n";
		system ( $cmd );
		if (! rmdir ( $srcdir )) {
			// Can't delete it - problem!
			echo "Error: Source directory $srcdir not empty or can't be deleted\n";
			
			exit ();
		}
	}
}
