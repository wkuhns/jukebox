import mysql.connector
import musicbrainzngs
import json
import requests
import certifi
import ssl
ssl._create_default_https_context = ssl._create_unverified_context

# Extract this to another file - it's duplicated
def dbquery(myStatus,sql):

  try:
    # Execute the SQL command
    myStatus["dbc"].execute(sql)
    # Commit your changes in the database
    # dbh.commit()
    return()
  except mysql.connector.Error as err:
    # Rollback in case there is any error
    # dbh.rollback()
    print("MySQL error: {0} {1} ".format(err,sql))
    exit()

######## Get MBZ release and releaseId, title, and artist
def getMBZdiscRelease(myStatus):
  global musicbrainzngs

  # Use ID to get data including genre
  if len(myStatus["mbzResult"]['disc']['release-list']) > 1:
    print("\nThis CD could be one of several releases:")
    choice = 1
    for rlist in myStatus["mbzResult"]['disc']['release-list']:
      print("  " + str(choice) + ": Artist: " + rlist['artist-credit-phrase'] +
        " Title: " + rlist['title'] + " Status: " + rlist['status'], end=" ")
      if 'country' in rlist:
        print (" Country: " + rlist['country'], end=" ")
      if 'date' in rlist:
        print(" Date: " + rlist['date'], end=" ")
      #print(json.dumps(rlist, indent=2, sort_keys=True))
      print()
      choice = choice + 1
    print("Select release:", end=' ')
    choice = int(input())
    choice = choice - 1
  else:
    choice = 0

  myStatus["mbzReleaseId"] = myStatus["mbzResult"]['disc']['release-list'][choice]['id']
  #print(myStatus["mbzReleaseId"])

  myStatus["mbzResult"] = musicbrainzngs.get_release_by_id(myStatus["mbzReleaseId"], includes=["artists", "recordings", "tags", "artist-credits"])
  #print(json.dumps(myStatus["mbzResult"], indent=2, sort_keys=True))

  myStatus["mbzRelease"] = myStatus["mbzResult"]['release']

  myStatus["artist"] = myStatus["mbzRelease"]['artist-credit'][0]['artist']['name']
  myStatus["title"] = myStatus["mbzRelease"]['title']
  if 'date' in myStatus["mbzRelease"]:
    myStatus["year"] = format(myStatus["mbzRelease"]['date'].split('-')[0])

  # Set track names


  for track in myStatus["mbzRelease"]['medium-list'][1]['track-list']:
    trackno = int(track['position'])-1
    # Sometimes mbz has wrong number of track titles (other disc in multidisc set)
    if trackno < myStatus["trackCount"]:
      myStatus["tracknames"][trackno] = track['recording']['title']
      # Display track to user
      print("Track {0}: {1}".format(trackno+1,track['recording']['title']))

  # get highest rated genres from tags
  print(json.dumps(myStatus["mbzRelease"]['medium-list'], indent=2, sort_keys=True))

  # might have genre associated with artist
  if 'tag-list' in myStatus["mbzRelease"]['artist-credit'][0]['artist']:
    myStatus["mbzRelease"]['artist-credit'][0]['artist']['tag-list'].sort(reverse=True,key=lambda x:sorted(x.keys()))
    hits = 0
    for gentry in myStatus["mbzRelease"]['artist-credit'][0]['artist']['tag-list']:
      # get genre ID
      try:
        gname = myStatus["dbh"].escape_string(gentry['name'])
        sql = "SELECT uid from genre where gname=\'" + gname + "\'"
        dbquery(myStatus,sql)
        rows = myStatus["dbc"].fetchall()
        if len(rows) == 1:
          # Got a hit. Is it the first? If so, set genre1
          if hits == 0:
            hits = 1
            myStatus["genre1"] = gentry['name']
            myStatus["genre1id"] = rows[0][0]
          elif hits == 1:
            myStatus["genre2"] = gentry['name']
            myStatus["genre2id"] = rows[0][0]
            break
      except:
        break

def getMBZartwork(myStatus):
  if myStatus["mbzRelease"]['cover-art-archive']['artwork'] == 'true':
    url = 'http://coverartarchive.org/release/' + myStatus["mbzRelease"]['id']
    getstuff = requests.get(url, allow_redirects=True, verify=False).content
    art = json.loads(getstuff.decode('utf-8'))
    #print(json.dumps(art, indent=2, sort_keys=True))

    # If only one, just get it
    hits = 0
    if len(art['images']) == 1:
      hits = 1
      cover = requests.get(art['images'][0]['image'], allow_redirects=True, verify=False)
    else:
      # look for 'front' image
      for image in art['images']:
         if image['front'] == True:
            hits = 1
            cover = requests.get(image['image'], allow_redirects=True, verify=False)
            break
    if hits == 1:
      cdt1 = myStatus["cdtag"][0:1].lower()
      cdt2 = myStatus["cdtag"][1:2].lower()
      cdt3 = myStatus["cdtag"][2:3].lower()
      fname = "/music/{0}/{1}/{2}/cover.jpg".format(cdt1,cdt2,cdt3)

      # print('COVER="{}"'.format(fname))
      f = open(fname, 'wb')
      f.write(cover.content)
      f.close()
      sql = "UPDATE album set status = status | 16 where uid={0}".format(myStatus["albumId"])
      dbquery(myStatus,sql)

def getJBartist(myStatus,artist):
  ######## Find or create Artist in database

  # Look for perfect match and just do it
  myArtist = myStatus["dbh"].converter.escape(artist)
  sql = "SELECT uid, dispname FROM artist where dispname = '{0}'".format(myArtist)
  dbquery(myStatus,sql)
  rows = myStatus["dbc"].fetchall()
  if len(rows) == 1:
    return(rows[0][0])

  print ("\nArtist name from CD is {0}. Could be one of the following:".format(artist))
  sql = "SELECT uid, dispname FROM artist where match (dispname) against ('{0}') or dispname like '%{0}%'".format(myArtist)
  dbquery(myStatus,sql)
  #myStatus["dbc"].execute(sql)
  rows = myStatus["dbc"].fetchall()
  choice = 0
  print("  x: Don't create artist link for track")
  print("  0: No matching artist")
  for row in rows:
    choice = choice + 1
    print("  {0}: {1}".format(choice,row[1]))
  print("select artist:", end=' ')
  choice = input()
  if choice == "x":
    return(0)

  choice = int(choice)

  if choice == 0:
    # Need to create a new artist
    sql = "INSERT INTO artist (dispname) values (\"" + myArtist + "\")"
    dbquery(myStatus,sql)
    return (myStatus["dbc"].lastrowid)
  else:
    choice = choice - 1
    return(rows[choice][0])

# See if album is on jukebox, optionally add if not. ArtistID is assumed to be valid.
# Return cdtag or 0. Three possible searches:
# cdtag: exit if no match. If match, verify with user and allow user to exit
# mbz: Show list of possible matches. User can select any or 'no match'
# user: match against user supplied title
def getJBalbum(myStatus,srch,add=False):
  sql = "SELECT album.uid, album.title, artist.uid, artist.dispname, album.cdtag \
                from album, artistlink, artist \
                where album.uid=artistlink.albumid \
                and artist.uid = artistlink.artistid "
  if srch == "cdtag":
    sql = sql +  "and cdtag = '{0}' order by album.title".format(myStatus["cdtag"])
    dbquery(myStatus,sql)
    rows = myStatus["dbc"].fetchall()
    if len(rows) == 1:
      # Does mbz data match jukebox?
      if myStatus["haveMBZdisc"] == True:
        if myStatus["albumId"] != str(rows[0][0]) or myStatus["artistId"] != str(rows[0][2]):
          print ("Mismatch: CD is {0} by {1}, Jukebox has {2} by {3}".format(myStatus["title"],
                                                                             myStatus["artist"],
                                                                             str(rows[0][1]),
                                                                             str(rows[0][3])))
          print("Proceed? (y/n):", end=' ')
          choice = input()
          if choice != 'y':
            exit()
        else:
          # We had one match and it wasn't a mismatch
          myStatus["haveJBAlbum"] = True
          return(str(rows[0][4]))
      else:
        # No mbz data. Use jukebox data
        myStatus["albumId"] = str(rows[0][0])
        myStatus["title"] = str(rows[0][1])
        myStatus["artistId"] = str(rows[0][2])
        myStatus["artist"] = str(rows[0][3])
        myStatus["haveJBAlbum"] = True
        return(str(rows[0][4]))
    else:
      # no matching cdtag in jukebox
      print("No matching cdtag")
      exit()

  # srch wasn't cdtag. 'user' means we had no cdtag of mbz data so user gave us title and artist
  if srch == "user" or srch == "mbz":
    title = myStatus["dbh"].converter.escape(myStatus["title"])
    sql = sql + "and (album.title like '%{0}%' or match (title) against ('{0}')) \
                 and artist.uid = {1} order by album.title".format(title,myStatus["artistId"])
    dbquery(myStatus,sql)
    rows = myStatus["dbc"].fetchall()
    choice = 0
    if len(rows) > 0:
      print("\nCD could match one of the following albums on the jukebox:")
    else:
      print("\nCD does not appear to match any albums on the jukebox.")
    print("  0: No matching album")
    for row in rows:
      choice = choice + 1
      print("  {0}: {1} Artist: {2} Title: {3}".format(choice,row[4],row[3],row[1]))
    print("select album:", end=' ')
    choice = int(input())
    if choice > 0:
      myStatus["albumId"] = str(rows[choice-1][0])
      print("Album ID: {0}".format(myStatus["albumId"]))
      myStatus["haveJBAlbum"] = True
      return(str(rows[choice-1][4]))
    else:
      return(0)

