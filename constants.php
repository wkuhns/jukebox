<?php

// Bitmask constants for filetype (ftype) field in track records
define ( 'WAV', 1 );
define ( 'MP3', 2 );
define ( 'M4A', 4 );
define ( 'WMA', 8 );

// Bitmask constants for album status field.
define ( 'JUKEBOX', 1 ); // In jukebox
define ( 'CD', 2 ); // Have (or recorded from) CD
define ( 'ALBUM', 4 ); // Have (or recorded from) vinyl
define ( 'TAPE', 8 ); // Have tape
define ( 'COVER', 16 ); // Have cover art
define ( 'ALLTRACKS', 32 ); // Complete album
define ( 'FULLFIDELITY', 64 ); // Highest quality audio
define ( 'PLAYLIST', 128 ); // This is a playlist, not an album
define ( 'NEWDIR', 256 ); // Converted to new directory structure
define ( 'NONDUP', 512 ); // No known duplicates
define ( 'MASTER', 1024 ); // Definitive copy

?>