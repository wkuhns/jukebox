<?php
include("sessionheader.php");

/*
div.header { display:block; position: relative; background-color: green; height:auto;}
div.headerleft { background-color: red; position: absolute; top:0; left:0; width:30%; }
div.nowplaying { background-color: yellow; position: absolute; top:0; left:30%; width:40%;}
div.headerright { background-color: gray;  float:right; width:30%;}

div.leftmenu { display:inline; clear:both; float:left; position: relative; width:150px;}

div.content {  display:inline; position: relative; float:left; background-color: lightgreen;}
*/

//echo "<div class=header>";
 echo "<div style='background-color: red; float:left; position: relative; width:30%;'>Header Left Content</div>"; 
 echo "<div style='background-color: yellow; float:left; top:0; left:30%; width:40%'>Now Playing<br>Content</div>"; 
 echo "<div style='background-color: gray; position: relative; float:right; width:30%;'><br>Header<br> right <br>Content</div>";
//echo "</div>";


//echo "<div class=header>";
 echo "<div style='display:inline; clear:both; float:left; position: relative; width:150px;'>Left Menu</div>"; 
 echo "<div style='display:inline; position: relative; float:left; background-color: lightgreen;'>Content</div>"; 
//echo "</div>";

