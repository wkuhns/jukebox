
import musicbrainzngs
import discid
import requests
import certifi
import json
import os; import locale;  os.environ["PYTHONIOENCODING"] = "utf-8";
import sys
import mbzfuncs
import subprocess
from subprocess import PIPE, run

import ssl
ssl._create_default_https_context = ssl._create_unverified_context


import mysql.connector

# Flags
myStatus = {
  "haveMBZdisc" : False,
  "haveMBZstub": False,
  "haveTitle": False,
  "title": "Unknown Album",
  "year": 1900,
  "haveArtist": False,
  "artist": "Unknown Artist",
  "artistId": "",
  "albumId": "",
  "cdtag": "",
  "haveGenre": False,
  "genre1": "Rock",
  "genre1id": 17,
  "genre2": "Rock",
  "genre2id": 17,
  "haveArt": False,
  "haveJBAlbum": False,
  "trackCount": 0,
  "mbzResult": "",
  "mbzRelease": "",
  "mbzReleaseId": "",
  "tracknames": [],
  "dbc": "",
  "dbh": "",
  "dir": ""
}

try:
  myStatus["dbh"] = mysql.connector.connect(host='192.168.1.10',
                                user='pyuser',
                                password='pypwd',
                                database='jukebox')
except mysql.connector.Error as err:
  print("Failed opening database: {}".format(err))
  exit(1)

try:
  myStatus["dbc"] = myStatus["dbh"].cursor(buffered=True)
except mysql.connector.Error as err:
  print("Failed creating cursor: {}".format(err))
  exit(1)

def sanitize(s):
  #s = s.replace("(","[")
  #s = s.replace(")","]")
  #s = s.replace("\"","'")
  return(s)

def dbquery(myStatus,sql):
  try:
    # Execute the SQL command
    myStatus["dbc"].execute(sql)
    return()
  except mysql.connector.Error as err:
    print("MySQL error: {0} {1} ".format(err,sql))
    exit()

def setCdtag(id):
  cdt1 = chr(id % 26+97);
  cdt2 = chr(int(id/676)+98);
  cdt3 = chr(int((id % 676)/26) + 97);
  cdtag = cdt1 + cdt2 + cdt3;
  myStatus["cdtag"] = cdtag.upper()
  myStatus["dir"] = "/music/{0}/{1}/{2}/".format(cdt1,cdt2,cdt3)

def createAlbum(myStatus):
  title = myStatus["dbh"].converter.escape(myStatus["title"])
  sql = "INSERT INTO album (title,cddbid,cdtag,genre1,genre2,status) values (\'{0}\',\'{1}\',\'{2}\',\'{3}\',\'{4}\',\'{5}\')".format(
                            title,myStatus["mbzReleaseId"],'xxx',myStatus["genre1id"],myStatus["genre2id"],99) 
  #print(sql)
  dbquery(myStatus,sql)
  albumid = myStatus["dbc"].lastrowid
  setCdtag(albumid)

  # Fix cdtag
  sql = "UPDATE album SET cdtag='{0}' WHERE uid={1}".format(myStatus["cdtag"],albumid);
  dbquery(myStatus,sql)

  # Link album to artist
  sql = "insert into artistlink values (NULL,{0},{1},1)".format(myStatus["artistId"],albumid);
  #print(sql)
  dbquery(myStatus,sql)

  # Link album to 'all albums' collection 
  sql = "insert into alistitem values (NULL,1,{0})".format(albumid);
  #print(sql)
  dbquery(myStatus,sql)
  myStatus["albumId"] = str(albumid)

  # Create directory
  cmd = ["mkdir", "-p", "{0}".format(myStatus["dir"])]
  result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)

def replaceAlbum(MyStatus):
  # We have the album - delete tracks and track-related data
  sql = "select track.uid from listitem,track \
      where listitem.trackid=track.uid \
      and listitem.albumid = {0}".format(myStatus["albumId"])
  #print (sql)
  dbquery(myStatus,sql)
  rows = myStatus["dbc"].fetchall()
  for row in rows:
    trackid = row[0]
    sql = "delete from mp3file where trackid={0}".format(trackid)
    #print(sql)
    dbquery(myStatus,sql)

    sql = "delete from listitem where trackid={0}".format(trackid)
    #print(sql)
    dbquery(myStatus,sql)

    sql = "delete from ratings where trackid={0}".format(trackid)
    #print(sql)
    dbquery(myStatus,sql)

    sql = "delete from talink where tid={0}".format(trackid)
    #print(sql)
    dbquery(myStatus,sql)

    sql = "delete from track where uid={0}".format(trackid)
    #print(sql)
    dbquery(myStatus,sql)

  cmd = ["rm", "{0}*".format(myStatus["dir"])]
  #print(cmd)
  result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)

# We have album id and artistid. Ready to deal with tracks
def readTracks(myStatus):

  for track in range(myStatus["trackCount"]):
    filename = myStatus["dir"] + "audio_{:0>2d}.wav".format(track+1)
    title = myStatus["dbh"].converter.escape(myStatus["tracknames"][track])
    artistid = myStatus["artistId"]
    if myStatus["haveMBZdisc"] == True or myStatus["haveMBZstub"] == True:
      try:
        if 'artist-credit' in myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]:
          artist = myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]['artist-credit'][0]
          #print(json.dumps(artist, indent=2, sort_keys=True))
          try:
            #print ("\nChoose track artist {0}".format(artist['artist']['name']))
            artistid = mbzfuncs.getJBartist(myStatus,artist['artist']['name'])
          except:
            print(json.dumps(myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]['artist-credit'][0], indent=2, sort_keys=True))
      except:
        artistid=0
    # If no valid track artist, use album artist
    if artistid == 0:
      artistid = myStatus["artistId"]
    sql = "INSERT INTO track (artistid,title,url,genre1,seq,rating,ftype,pscore,volume) \
            values ({0},'{1}','{2}',{3},{4},60,1,110,0.95)".format(
            artistid,
            title,
            filename,
            int(myStatus["genre1id"]),
            (track+1))
    #print(sql)
    dbquery(myStatus,sql)
    trackid = myStatus["dbc"].lastrowid
    sql = "INSERT INTO listitem (trackid,albumid,seq) \
            values ({0},{1},{2})".format(trackid,myStatus["albumId"],track+1)
    #print(sql)
    dbquery(myStatus,sql)

    #print(json.dumps(, indent=2, sort_keys=True))
    # If there's a second artist credit, try to match it and create talink record
    if myStatus["haveMBZdisc"] == True or myStatus["haveMBZstub"] == True:
      try:
        acount = len(myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]['artist-credit'])
      except:
        acount=0
      if acount > 1:
        for aindex in range(1,acount):
          artist = myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]['artist-credit'][aindex]
          try:
            artistid = mbzfuncs.getJBartist(myStatus,artist['artist']['name'])
          except:
            print(json.dumps(myStatus["mbzRelease"]['medium-list'][0]['track-list'][track]['artist-credit'], indent=2, sort_keys=True))
            artistid = 0
          if artistid > 0:
            sql = "INSERT into talink (tid,aid) values ({0},{1})".format(trackid,artistid)
            #print(sql)
            dbquery(myStatus,sql)

  for track in range(myStatus["trackCount"]):
    filename = myStatus["dir"] + "audio_{:0>2d}.wav".format(track+1)
    cmd = ["/usr/bin/cdparanoia", "-wz=5", "{0}".format(track+1), "{0}".format(filename)]
    print("Reading track {0}...".format(track+1))
    result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)
    if(result.returncode) != 0:
      print("\nProblem encountered. Return code {0}".format(result.returncode))
      print(result.stderr)
    else:
      print("Success!")
    myStatus["tracknames"][track+1] = "Track {0}".format(track+1)
  cmd = ["/bin/chown", "-R", "www-data:webusers", "{0}".format(dir)]
  result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)
  cmd = ["/bin/chmod", "-R", "0775", "{0}".format(dir)]
  result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)

########## Set up sources ##############
device = "/dev/cdrom"
cd = discid.read(device)
cdid = cd.id
#cdid = "lwHl8fGzJyLXQR33ug60E8jhf4k-"

# Get track count and set default names
cmd = ['/music/tools/getTrackCount','xxx']
result = run(cmd, stdout=PIPE, stderr=PIPE, universal_newlines=True)
myStatus["trackCount"] = int(result.stdout)
print("Track Count: {0}".format(myStatus["trackCount"]))

for t in range(myStatus["trackCount"]):
  myStatus["tracknames"].append("Track {:0>2d}".format(int(t+1)))

# Try for mbz data
musicbrainzngs.set_useragent("Jukebox", "3.1", contact=None)

try:
  #Get ID
  myStatus["mbzResult"] = musicbrainzngs.get_releases_by_discid(cdid, includes=["artists"])
  if myStatus["mbzResult"].get("disc"):
    myStatus["haveMBZdisc"] = True
    #print("Have MBZ disc")
  else:
    myStatus["haveMBZstub"] = True
    print("Have MBZ stub")

except musicbrainzngs.ResponseError:
  print("Musicbrainz data not available for disc ID {0}".format(cdid))


#print(json.dumps(result, indent=2, sort_keys=True))


# Get title, artist name, genre, year, and track names
if myStatus["haveMBZdisc"] == True:
  mbzfuncs.getMBZdiscRelease(myStatus)
  print ("\nCD is Artist: {0}, Title: {1}".format(myStatus["artist"],myStatus["title"]))
######## big decision tree. We may have MBZ data. The user may have specified a cdtag. The album may be on the jukebox already

# If user specified cdtag, read data from jukebox
if len(sys.argv) > 1:
  myStatus["cdtag"] = sys.argv[1].upper()
  updating = True
  myStatus["cdtag"] = mbzfuncs.getJBalbum(myStatus,"cdtag")
  print ("Have cdtag from command line: {0}".format(myStatus["cdtag"]))
else:
  # No cdtag. No MBZ data? Get from user
  if myStatus["haveMBZdisc"] == False and myStatus["haveMBZstub"] == False:
    print("Enter album title:", end=' ')
    myStatus["title"] = input()
    print("Enter artist name:", end=' ')
    myStatus["artist"] = input()
    #Is this in the jukebox already? Get (or create) artist
    myStatus["artistId"] = mbzfuncs.getJBartist(myStatus,myStatus["artist"])
    myStatus["cdtag"] = mbzfuncs.getJBalbum(myStatus,"user")
    print("No cdtag, no mbz. cdtag = {0}".format(myStatus["cdtag"]))
  else:
    # We have mbzdata, no cdtag. Is album on jukebox?
    myStatus["artistId"] = mbzfuncs.getJBartist(myStatus,myStatus["artist"])
    myStatus["cdtag"] = mbzfuncs.getJBalbum(myStatus,"user")
    print("No cdtag, have mbz. cdtag = {0}".format(myStatus["cdtag"]))

if str(myStatus["cdtag"]) == "0":
  createAlbum(myStatus)
  setCdtag(int(myStatus["albumId"]))
else:
  setCdtag(int(myStatus["albumId"]))
  replaceAlbum(myStatus)

if myStatus["haveMBZdisc"] == True or myStatus["haveMBZstub"] == True:
  mbzfuncs.getMBZartwork(myStatus)

print(myStatus["title"])
print(myStatus["albumId"])
print(myStatus["haveJBAlbum"])
print(myStatus["artist"])
print(myStatus["artistId"])
print(myStatus["haveMBZdisc"])

# Finally, read the tracks
readTracks(myStatus)

print("\nCompleted album {0}. Artist: {1} Title: {2}".format(myStatus["cdtag"],myStatus["artist"],myStatus["title"]))

