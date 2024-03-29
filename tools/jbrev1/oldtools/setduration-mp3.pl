#!/usr/bin/perl
#use strict;
# Make track and listitem records for cd where CDDB is not available. 
# cdtag value must be supplied on command line.
# /music/<cdtag> must exist, and audio_xx.wav files must be in directory.

use DBI;

$db = "music"; 
$host = "granite";
$user = "wkuhns";
$pwd = "";

# connect to the database.

$dbh = DBI->connect( "DBI:mysql:$db:$host", $user, $pwd)
or die "Connecting : $DBI::errstr\n ";

# Try to find artist

$sql = "SELECT uid, url from track where url like '%.mp3' and duration=0";
$sth = $dbh->prepare($sql) or die "preparing: ",$dbh->errstr;
$sth->execute or die "executing: ", $dbh->errstr;

$n=0;
while ($row = $sth->fetchrow_hashref)
  {
    print "$row->{'uid'} ";
    $secs = `mp3info -p\%S $row->{'url'}`;
    if ($secs != ''){
    	$mins = int($secs/60);
    	$fmins = $secs - $mins*60;
    	print "$mins:$fmins\n";
    	$sql2 = "UPDATE track SET duration=$secs where uid=$row->{'uid'}";
    	print "$sql2\n";
    	$sth2 = $dbh->prepare($sql2) or die "preparing: ",$dbh->errstr;
    	$sth2->execute or die "executing: ", $dbh->errstr;
    }
  }

