#!/usr/bin/perl


$uid=46;

$cdtag = chr($uid % 26+65) . chr(int($uid/676)+66) . chr(int(($uid % 676)/26)+65);

print "$cdtag\n";

$genre='folk';
$genre=ucfirst($genre);

print "$genre\n";

$artistid=333;
$i=4;
$cdtag = lc($cdtag);
$n = 1;
$f = sprintf("%02d",$n);

print "Arg = $ARGV[0]\n";

if ($ARGV[0]){
  print "Arg = $ARGV[0]\n";
}else{
  print "No Argument\n";
}

#$test = "\"test\"";
#print "$test\n";
#$t = $test s/\"/\'/;
#print "$t\n";

opendir $dh,"/music/xca";
#print readdir $dh;
$n=0;
foreach $i (readdir $dh) {
  ($a,$b) = split(/\./, $i);
  if ($b eq "wav") {
     print "$n: $a; $b= $i\n";
     $n++;
   }
}
