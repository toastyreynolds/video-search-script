<?php

  //LOAD_USER_CONFIG
  require_once("video-config.php");

  //MAKE A PRETTY TIMESTAMP FOR OUR FILES
  $TIMESTAMP = date('Ymd_His');
  $TODAY = date('Ymd');


  //_______________________________________________________________________
  // PROBABLY DON'T MESS WITH ANYTHING BELOW HERE UNLESS YOU'RE COOL WITH WHAT
  // MIGHT HAPPEN. --Toasty
  //_______________________________________________________________________
  //LET'S CHECK FOR (AND REMOVE) TRAILING SLASHES IN OUR USER CONFIG PATH VARIABLES
  // This is a very sloppy way to account for trailing slashes in the paths above.
  if (substr($VIDEO_SEARCH_DIRECTORY, -1) == "/") { $VIDEO_SEARCH_DIRECTORY = substr($VIDEO_SEARCH_DIRECTORY, 0, -1); }

  if (substr($VIDEO_CONFIG_LOG_DIRECTORY, -1) == "/") { $VIDEO_CONFIG_LOG_DIRECTORY = substr($VIDEO_CONFIG_LOG_DIRECTORY, 0, -1); }

  //LET'S DO A LITTLE BIT OF ERROR CHECKING
  //LET'S CHECK SOME FILE NAMES

  if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $VIDEO_SEARCH_LOG_FILE)) {
    die("There seems to be a problem with your \$VIDEO_SEARCH_LOG_FILE: ".$VIDEO_SEARCH_LOG_FILE."\n");
  }
  if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $VIDEO_SEARCH_FORMAT_FILE)) {
    die("There seems to be a problem with your \$VIDEO_SEARCH_FORMAT_FILE: ".$VIDEO_SEARCH_FORMAT_FILE."\n");
  }
  if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $VIDEO_SEARCH_SKIPPED_FILE)) {
    die("There seems to be a problem with your \$VIDEO_SEARCH_SKIPPED_FILE: ".$VIDEO_SEARCH_SKIPPED_FILE."\n");
  }


  //Check to see if the path set is a valid directory to search within
  if (!is_dir($VIDEO_SEARCH_DIRECTORY)) {
    die("We don't seem to recognize ".$VIDEO_SEARCH_DIRECTORY." as a proper directory to search in.  We're so sorry. \n");
  }
  //Let's do some checking on our log directory.
  if (is_dir($VIDEO_CONFIG_LOG_DIRECTORY)) {
    if (!is_writable($VIDEO_CONFIG_LOG_DIRECTORY)) {
       die("We've found your log directory: ".$VIDEO_CONFIG_LOG_DIRECTORY.", but I'm afraid we can't write to it.\n");
    }
  } else {
      die("We don't seem to recognize ".$VIDEO_CONFIG_LOG_DIRECTORY." as a proper directory to write logs to.  We're so sorry. \n");
  }

  //CHECK FOR LOGS FROM TODAY
  if (!is_dir($VIDEO_CONFIG_LOG_DIRECTORY."/".$TODAY)) {
    if (!mkdir($VIDEO_CONFIG_LOG_DIRECTORY."/".$TODAY,0777, true)) {
    }
  }


  //This is a very sloppy way to support spaces in file names
  if (strstr($VIDEO_SEARCH_DIRECTORY," ")) {
    $ESC_VIDEO_SEARCH_DIRECTORY = str_replace(" ","\ ",$VIDEO_SEARCH_DIRECTORY);
  } else {
    $ESC_VIDEO_SEARCH_DIRECTORY = $VIDEO_SEARCH_DIRECTORY;
  }

  if (strstr($VIDEO_CONFIG_LOG_DIRECTORY," ")) {
    $ESC_VIDEO_CONFIG_LOG_DIRECTORY = str_replace(" ","\ ",$VIDEO_CONFIG_LOG_DIRECTORY);
  } else {
    $ESC_VIDEO_CONFIG_LOG_DIRECTORY = $VIDEO_CONFIG_LOG_DIRECTORY;
  }

  //BUILD FIND COMMAND
  $FIND_CMD = "find ".$ESC_VIDEO_SEARCH_DIRECTORY." ".$VIDEO_SEARCH_FIND_PARAMETERS;
  //echo ($FIND_CMD);

  //EXECUTE FIND COMMAND
  $FIND_RESULTS = shell_exec($FIND_CMD);

  //BUILD FILENAMES




  $FILE_LIST = $VIDEO_CONFIG_LOG_DIRECTORY."/".$TODAY."/".$TIMESTAMP."_".$VIDEO_SEARCH_LOG_FILE;
  $SKIPPED_LIST = $VIDEO_CONFIG_LOG_DIRECTORY."/".$TODAY."/".$TIMESTAMP."_".$VIDEO_SEARCH_SKIPPED_FILE;
  $CSV_LIST = $VIDEO_CONFIG_LOG_DIRECTORY."/".$TODAY."/".$TIMESTAMP."_".$VIDEO_SEARCH_FORMAT_FILE;

  //WRITE RESULTS TO A FILE FOR SAFE KEEPING AND MEMORY MANAGEMENT
  $fp = fopen($FILE_LIST, 'w+');
  fwrite($fp, $FIND_RESULTS);
  fclose($fp);
  unset($fp);

  //LET'S PREP OUR FORMAT FILE
  if (!$FILE_POINTER = fopen($CSV_LIST, 'w+')) {
    die("Unable to open FILE_POINTER ON: ".$CSV_LIST);
  } else {
  fwrite($FILE_POINTER, "FILENAME, FILE PATH, FILE TYPE, FILE FORMAT, CODEC, VIDEO DETAILS, AUDIO FORMAT \n");
  }

  $FILE_HANDLE = fopen($FILE_LIST, "r");

  if ($FILE_HANDLE) {
      while (($LINE = fgets($FILE_HANDLE)) !== false) {
        //LETS CHECK TO SEE IF WE'RE WASTING OUR TIME
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $LINE)) {
            // one or more of the 'special characters' found in $string
            $SKIPPED_LIST_POINTER = fopen($SKIPPED_LIST, 'a+');
            fwrite($SKIPPED_LIST_POINTER, $LINE);
            fclose($SKIPPED_LIST_POINTER);
            unset($SKIPPED_LIST_POINTER);
            continue;
        }
        //ARE YOU A DIRECTORY?
        if (is_dir($LINE)) {
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
                  $FILE_AUDIO = $track->Format;
                  break;
              }
          }
        echo $LINE;
        // /usr/local/bin/mediainfo
        $FILE_NAME = trim(basename($LINE));
        $LINE_TO_WRITE = $FILE_NAME.",".trim($FULL_FILE_PATH).",".$FILE_TYPE.",".$FILE_FORMAT.",".$FILE_CODEC.",".$FILE_DETAILS.",".$FILE_AUDIO."\n";
        fwrite($FILE_POINTER, $LINE_TO_WRITE);


        //LET'S CLEAN UP OUR VARIABLES YOU DOG
        unset($FILE_XML);
        unset($FILE_TYPE);
        unset($FILE_FORMAT);
        unset($FILE_DETAILS);
        unset($FILE_AUDIO);
        unset($FILE_NAME);
        unset($FILE_CODEC);

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
