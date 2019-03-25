video-search-script
=====

This is a simple PHP script to help you find files that are affected by (https://9to5mac.com/2019/03/22/apple-legacy-media-final-cut-pro-macos/)

## Preamble
This is a _very_ simple PHP script that you probably shouldn't use unless you know me personally. That's not to say you can't use it if you don't, it's just that this script doesn't have as much error handling as it should.  I'll try and build this out into something more servicable, but I'm sure better developers will have better scripts out there shortly.

## Install
Should you want to use this, you'll need PHP.  I wrote this against 7.1.2, but as it's really simple it should work with most versions.

This script also uses the `media-info` brew package.  Check out (https://brew.sh/) to get brew, and then run `brew install media-info`

Inside video-search-script.php you'll edit two variables. It's the path for where the script searches, and where it writes the resulting log files.

It will run a find command against the path, skipping hidden directories. It will log those results to a file.

It will then check each file in that list, line by line to get mediainfo details.

If your file contains a special character that isn't a underscore or dash, it will be placed in a "skipped list". _Animals_
