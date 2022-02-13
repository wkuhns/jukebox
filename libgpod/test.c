

// Exercise libgpod

// cc -I /usr/include/gpod-1.0/gpod/ -I /usr/include/glib-2.0/ -I /usr/lib/glib-2.0/include/ -I /usr/include/gtk-2.0/ -L /usr/lib -lgpod test.c

#ifdef HAVE_CONFIG_H
#  include <config.h>
#endif

#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <libintl.h>

#include "itdb.h"
#include "glib/glist.h"


int
main (int argc, char *argv[])
{
  GError *error=NULL;
  Itdb_iTunesDB *itdb;
  gchar *infile = NULL;
  gchar *outfile = NULL;
  GList *trackitem;
  Itdb_Track *track;
  int t;
  
  if (argc >= 2)
      infile = argv[1];
  if (argc >= 3)
      outfile = argv[2];

  if (infile == 0)
  {
      printf ("Usage: %s <infile> [<outfile>]\n",  g_basename(argv[0]));
      exit (0);
  }

  itdb = itdb_parse_file (infile, &error);
  printf ("%p\n", itdb);

  if (error)
  {
      if (error->message)
	  puts (error->message);
      g_error_free (error);
      error = NULL;
  }

  if (itdb)
  {
      printf ("tracks: %d\n", g_list_length (itdb->tracks));
      printf ("playlists: %d\n", g_list_length (itdb->playlists));
      //tracks = itdb->tracks;
      trackitem = g_list_first(itdb->tracks);

      for (t=0; t < g_list_length (itdb->tracks);t++){
	trackitem = g_list_nth(itdb->tracks,t);
	track = trackitem->data;
	printf("%s %s %s\n",track->title,track->artist,track->album);
      }
      if (outfile)
	  itdb_write_file (itdb, outfile, &error);
      if (error)
      {
	  if (error->message)
	      puts (error->message);
	  g_error_free (error);
	  error = NULL;
      }
  }

  itdb_free (itdb);

  return 0;
}
