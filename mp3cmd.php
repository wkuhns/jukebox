<?php

// Invoked as AJAX server - doesn't return web page
include_once ("phpfunctions.php");

getjukeboxdb ();

if ($_GET ['deltrack']) {
	$ipath = mp3path ();
	$trackid = $_GET ['trackid'];
	deleteipodtrack ( $ipath, $trackid );
}

if ($_GET ['mp3cmd'] == "AddSelected") {
	$ipath = mp3path ();
	$device = mp3device ( $ipath );
	$pltitle = $_GET ['pltitle'];
	$outfile = fopen ( "/home/music/scripts/mp3list", "w" );
	foreach ( $_GET ['trackid'] as $ti ) {
		makemp3track ( $outfile, $device, $ipath, $ti, $pltitle );
	}
	fclose ( $outfile );
	exec ( "/home/music/scripts/mp3list > /dev/null 2>&1" );
	exit ();
}

if ($_GET ['mp3cmd'] == "AddAll") {
	$ipath = mp3path ();
	$device = mp3device ( $ipath );
	$pltitle = $_GET ['pltitle'];
	$albumid = $_GET ['albumid'];
	$query = "select trackid from listitem where listitem.albumid=$albumid order by seq";
	$result = do_query ( $query );
	$outfile = fopen ( "/home/music/scripts/mp3list", "w" );
	while ( $myrow = mysqli_fetch_row ( $result ) ) {
		makemp3track ( $outfile, $device, $ipath, $myrow [0], $pltitle );
	}
	fclose ( $outfile );
	exec ( "/home/music/scripts/mp3list > /dev/null 2>&1" );
	exit ();
}

if ($_GET ['mp3cmd'] == "check") {
	mp3check ();
}

if ($_GET ['mp3cmd'] == "mount") {
	
	system ( 'mount /mnt/seagate' );
	exec ( 'mount | grep seagate', $spath, $ret );
	if (! $ret) {
		// Seagate is mounted. Find the device
		array (
				$chunks 
		);
		$chunks = explode ( " ", $spath [0] );
		$spath = $chunks [0];
		$sdev = substr ( $spath, - 2 );
	}
	
	// might already be mounted - check
	exec ( 'mount | grep mp3', $junk, $ret );
	$mp3mounted = ! $ret;
	
	// Try each mount point
	
	if (! $mp3mounted && $sdev != 'c1') {
		system ( 'mount /mnt/mp3_c1' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'c2') {
		system ( 'mount /mnt/mp3_c2' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'd1') {
		system ( 'mount /mnt/mp3_d1' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'd2') {
		system ( 'mount /mnt/mp3_d2' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'e1') {
		system ( 'mount /mnt/mp3_e1' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'e2') {
		system ( 'mount /mnt/mp3_e2' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'f1') {
		system ( 'mount /mnt/mp3_f1' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	if (! $mp3mounted && $sdev != 'f2') {
		system ( 'mount /mnt/mp3_f2' );
		exec ( 'mount | grep mp3', $junk, $ret );
		$mp3mounted = ! $ret;
	}
	
	mp3check ();
}

if ($_GET ['mp3cmd'] == "unmount") {
	$ipath = mp3path ();
	$device = mp3device ( $ipath );
	
	if ($device == 'ipod') {
		// echo "iPod detected at $ipath<br>\n";
		// echo "<pre>";
		$cmd = "/usr/bin/mktunes -m $ipath > /dev/null 2>&1";
		system ( $cmd );
		// echo "</pre>";
	}
	system ( 'umount /mnt/mp3_d1' );
	system ( 'umount /mnt/mp3_d2' );
	system ( 'umount /mnt/mp3_e1' );
	system ( 'umount /mnt/mp3_e2' );
	system ( 'umount /mnt/mp3_f1' );
	system ( 'umount /mnt/mp3_f2' );
	// echo "It is safe to disconnect your MP3 player now <br>\n";
	echo "none:";
}