<script language="JavaScript">


function MakeAlbumWindow(uid,pl) {
  this.url = "album.php?uid="+uid+"&pl="+pl;
  abw = window.open(this.url,"","toolbar=no,directories=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=800");
  abw.focus();
}

function MakeArtistWindow(uid) {
  this.url = "artist.php?artistid="+uid;
  abw = window.open(this.url,"","toolbar=no,directories=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=600");
  abw.focus();
}

function nowplaying() {
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      document.getElementById("nowplaying").innerHTML=xmlhttp.responseText;
    }
  }
  xmlhttp.open("GET","nowplaying.php",true);
  xmlhttp.send();
}

function mp3manager(mycmd) {
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    mp3http=new XMLHttpRequest();
  }
  mp3http.onreadystatechange=function() {
    if (mp3http.readyState==4 && mp3http.status==200) {
      var mp3status = mp3http.responseText;
      var mp3chunks = mp3status.split(":");
      if(mp3chunks[0] == "none"){
	document.getElementById("mp3div").innerHTML="Find MP3";
	document.getElementById("mp3div").style.backgroundColor="lightgrey";
	document.getElementById("mp3actiondiv").innerHTML = "";
	document.getElementById("mp3actiondiv").visibility = "hidden";
      }
      if(mp3chunks[0] == "MP3"){
	document.getElementById("mp3div").innerHTML="MP3";
	document.getElementById("mp3div").style.backgroundColor="lightgreen";
	document.getElementById("mp3actiondiv").style.backgroundColor="lightyellow";
	document.getElementById("mp3actiondiv").innerHTML = "Eject";
	document.getElementById("mp3actiondiv").visibility = "visible";
      }
      if(mp3chunks[0] == "iPod"){
	document.getElementById("mp3div").innerHTML=" iPod ";
	document.getElementById("mp3div").style.backgroundColor="lightgreen";
	document.getElementById("mp3actiondiv").style.backgroundColor="lightyellow";
	document.getElementById("mp3actiondiv").innerHTML = "Eject";
	document.getElementById("mp3actiondiv").visibility = "visible";
      }
    }
  }
  if (document.getElementById("mp3div").innerHTML != "Find MP3" && mycmd == "mount"){
    mp3http.open("GET","mp3cmd.php?mp3cmd=check",true);
  }else{
    mp3http.open("GET","mp3cmd.php?mp3cmd=" + mycmd,true);
  }
  mp3http.send();
}

function shellcmd(cmd) {
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
    }
  }
  
  xmlhttp.open("GET","shellcmd.php?" + cmd,true);
  xmlhttp.send();
}

function silentcmd(cmd,rfrsh) {
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp=new XMLHttpRequest();
  }
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) {
      if(rfrsh='y'){
	window.location.reload();
      }
    }
  }
  
  xmlhttp.open("GET",cmd,true);
  xmlhttp.send();
}

function wininit() {
  nowplaying();
  //mp3manager("check");
  setInterval('nowplaying()',2000);
}

</script>
