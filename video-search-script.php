<?php
  //GET timestamp FOR LOGS
  $TIMESTAMP = time();

  //PROVIDE FILE PATH FOR WHERE YOU WANT TO SEARCH
  $SEARCH_DIRECTORY = "/Users/toasty/path/to/stuff/";

  //PROVIDE FILE PATH FOR WHERE YOU WANT TO LOG
  $SEARCH_DIRECTORY = "/Users/toasty/Desktop/logs/";

  //PROBABLY DON'T MESS WITH ANYTHING BELOW HERE UNLESS YOU'RE COOL WITH IT
  //_______________________________________________________________________
  //CLEAN UP PATHS

  $SEARCH_DIRECTORY = str_replace(" ","\ ",$SEARCH_DIRECTORY);
  $LOG_DIRECTORY = str_replace(" ","\ ",$LOG_DIRECTORY);

  //BUILD FIND COMMAND
  $FIND_CMD = "find $SEARCH_DIRECTORY -type d -path '*/\.*' -prune -o -not -name '.*' -type f -print";
  //echo ($FIND_CMD);

  //EXECUTE FIND COMMAND
  $FIND_RESULTS = shell_exec($FIND_CMD);

  //BUILD FILENAMES
  $FILE_LIST = $LOG_DIRECTORY."/".$TIMESTAMP."_file_list.txt";
  $CSV_LIST = $LOG_DIRECTORY."/".$TIMESTAMP."_format_list.csv";
  //WRITE RESULTS TO A FILE FOR SAFE KEEPING AND MEMORY MANAGEMENT

  $fp = fopen($FILE_LIST, 'w+');
  fwrite($fp, $FIND_RESULTS);
  fclose($fp);
  unset($fp);

  $fp = fopen($CSV_LIST, 'w+');
  fwrite($fp, "FILENAME, FILE PATH, FILE TYPE, FILE FORMAT, CODEC, VIDEO DETAILS, AUDIO FORMAT \n");
  $handle = fopen($FILE_LIST, "r");

  if ($handle) {
      while (($line = fgets($handle)) !== false) {

          //RUN MEDIAINFO AGAINST THE FILE
          $FULL_FILE_PATH = trim($line);
          $MEDIAINFO_CMD = "/usr/local/bin/mediainfo --Output=XML ".$FULL_FILE_PATH;
          $MEDIAINFO_RESULTS = shell_exec($MEDIAINFO_CMD);
          //UNCOMMENT THIS IF YOU WANT TO SEE THE MEDIAINFO OUTPUT IN XML FORMAT
          //echo $MEDIAINFO_RESULTS."\n";

          //PARSE MEDIA INFO RESULT
          $FILE_XML = new SimpleXMLElement($MEDIAINFO_RESULTS);

          if (!$FILE_XML->media->track) {
            continue;
          }

          foreach ($FILE_XML->media->track as $track) {
              switch((string) $track['type']) { // Get attributes as element indices
              case 'General':
                //if ($track->VideoCount < 1) {
                //  continue;
                //}

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
          echo $line;
          // /usr/local/bin/mediainfo
          $FILE_NAME = trim(basename($line));
          $LINE_TO_WRITE = $FILE_NAME.",".$FULL_FILE_PATH.",".$FILE_TYPE.",".$FILE_FORMAT.",".$FILE_CODEC.",".$FILE_DETAILS.",".$FILE_AUDIO."\n";
          fwrite($fp, $LINE_TO_WRITE);

      }
      fclose($handle);






  } else {
      // error opening the file.
      echo "COULD NOT OPEN FILE_LIST AT ".$FILE_LIST;
      echo "\n";
      exit(1);

  }
  fclose($fp);
  unset($fp);
  unset($handle);
  //CLEAN UP YOU FILTHY MONKEY
  exit(0);

?>
