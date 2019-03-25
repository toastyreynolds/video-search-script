<?php
  //GET timestamp FOR LOGS
  $TIMESTAMP = time();

  //PROVIDE FILE PATH FOR WHERE YOU WANT TO SEARCH
  //$SEARCH_DIRECTORY = "/Volumes/ALPHA/_PHOTOG/EMMA VIDEO/FLIPVIDEO/DCIM/100VIDEO";
  $SEARCH_DIRECTORY = "/Volumes/ALPHA/_PHOTOG/EMMA VIDEO";

  //PROVIDE FILE PATH FOR WHERE YOU WANT TO LOG
  $LOG_DIRECTORY = "/Users/boom/Desktop/logs";

  //PROBABLY DON'T MESS WITH ANYTHING BELOW HERE UNLESS YOU'RE COOL WITH IT
  //_______________________________________________________________________
  //LET'S CHECK FROM TRAILING SLASHES IN OUR PATHS

  // This is a very sloppy way to account for trailing slashes in the paths above.
  if (substr($SEARCH_DIRECTORY, -1) == "/") { $SEARCH_DIRECTORY = substr($SEARCH_DIRECTORY, 0, -1); }

  if (substr($LOG_DIRECTORY, -1) == "/") { $SEARCH_DIRECTORY = substr($SEARCH_DIRECTORY, 0, -1); }

  //LET'S DO A LITTLE BIT OF ERROR CHECKING
  //Check to see if the path set is a valid directory to search within
  if (!is_dir($SEARCH_DIRECTORY)) { die("We don't seem to recognize ".$SEARCH_DIRECTORY." as a proper directory to search in.  We're so sorry. \n"); }
  //Let's do some checking on our log directory.
  if (is_dir($LOG_DIRECTORY) {
    if (!is_writable($LOG_DIRECTORY)) {
       die("We've found your log directory: ".$LOG_DIRECTORY.", but I'm afraid we can't write to it.\n");
    }
  } else {
      die("We don't seem to recognize ".$LOG_DIRECTORY." as a proper directory to write logs to.  We're so sorry. \n");
  }

  //This is a very sloppy way to support spaces in file names
  if (strstr($SEARCH_DIRECTORY," ")) {
    $ESC_SEARCH_DIRECTORY = str_replace(" ","\ ",$SEARCH_DIRECTORY);
  } else {
    $ESC_SEARCH_DIRECTORY = $SEARCH_DIRECTORY;
  }

  if (strstr($LOG_DIRECTORY," ")) {
    $ESC_LOG_DIRECTORY = str_replace(" ","\ ",$LOG_DIRECTORY);
  } else {
    $ESC_SEARCH_DIRECTORY = $SEARCH_DIRECTORY;
  }

  //BUILD FIND COMMAND
  $FIND_CMD = "find $ESC_SEARCH_DIRECTORY -type d -path '*/\.*' -prune -o -not -name '.*' -type f -print";
  //echo ($FIND_CMD);

  //EXECUTE FIND COMMAND
  $FIND_RESULTS = shell_exec($FIND_CMD);

  //BUILD FILENAMES
  $FILE_LIST = $ESC_LOG_DIRECTORY."/".$TIMESTAMP."_file_list.txt";
  $SKIPPED_LIST = $ESC_LOG_DIRECTORY."/".$TIMESTAMP."_skipped_list.txt";
  $CSV_LIST = $ESC_LOG_DIRECTORY."/".$TIMESTAMP."_format_list.csv";
  //WRITE RESULTS TO A FILE FOR SAFE KEEPING AND MEMORY MANAGEMENT
  $fp = fopen($FILE_LIST, 'w+');
  fwrite($fp, $FIND_RESULTS);
  fclose($fp);
  unset($fp);

  $FILE_POINTER = fopen($CSV_LIST, 'w+');
  fwrite($FILE_POINTER, "FILENAME, FILE PATH, FILE TYPE, FILE FORMAT, CODEC, VIDEO DETAILS, AUDIO FORMAT \n");
  $FILE_HANDLE = fopen($FILE_LIST, "r");

  if ($FILE_HANDLE) {
      while (($LINE = fgets($FILE_HANDLE)) !== false) {

        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $LINE))
          {
            // one or more of the 'special characters' found in $string
            $SKIPPED_LIST_POINTER = fopen($SKIPPED_LIST, 'a+');
            fwrite($SKIPPED_LIST_POINTER, $LINE);
            fclose($SKIPPED_LIST_POINTER);
            unset($SKIPPED_LIST_POINTER);
            continue;
          }

          //RUN MEDIAINFO AGAINST THE FILE
          if (strstr($LINE," ")) {
            $FULL_FILE_PATH = str_replace(" ","\ ",$LINE);
          } else {
            $FULL_FILE_PATH = $LINE;
          }

          $MEDIAINFO_CMD = "/usr/local/bin/mediainfo --Output=XML ".$FULL_FILE_PATH;
          $MEDIAINFO_RESULTS = shell_exec($MEDIAINFO_CMD);
          //UNCOMMENT THIS IF YOU WANT TO SEE THE MEDIAINFO OUTPUT IN XML FORMAT
          //echo $MEDIAINFO_RESULTS."\n";

          //PARSE MEDIA INFO RESULT
          $FILE_XML = new SimpleXMLElement($MEDIAINFO_RESULTS);
          //var_dump($FILE_XML);
          foreach ($FILE_XML->media->track as $track) {
              switch((string) $track['type']) { // Get attributes as element indices
              case 'General':
                $FILE_TYPE = $track->FileExtension;
                $FILE_FORMAT = $track->Format;
              break;
              case 'Video':
                $FILE_DETAILS = $track->Format;
                $FILE_DETAILS .= " - ". $track->Format_Profile;
                $FILE_DETAILS .= " - ". $track->Format_Settings_Matrix;
                $FILE_DETAILS .= " - ". $FILE_CODEC = $track->CodecID;
                  break;
              case 'Audio':
                  echo $FILE_AUDIO = $track->Format;
                  break;
              }
          }
          echo $LINE;
          // /usr/local/bin/mediainfo
          $FILE_NAME = trim(basename($LINE));
          $LINE_TO_WRITE = $FILE_NAME.",".trim($FULL_FILE_PATH).",".$FILE_TYPE.",".$FILE_FORMAT.",".$FILE_CODEC.",".$FILE_DETAILS.",".$FILE_AUDIO."\n";
          fwrite($FILE_POINTER, $LINE_TO_WRITE);
      }
      fclose($FILE_HANDLE);
  } else {
      // error opening the file.
      echo "COULD NOT OPEN FILE_LIST AT ".$FILE_LIST;
      echo "\n";
      exit(1);
  }
  fclose($FILE_POINTER);
  unset($FILE_POINTER);
  unset($FILE_HANDLE);
  //CLEAN UP YOU FILTHY MONKEY
  exit(0);
?>
