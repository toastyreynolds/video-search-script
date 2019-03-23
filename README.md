# video-search-script

video-search-script
=====

This is a simple PHP script to help you find files that are affected by (https://9to5mac.com/2019/03/22/apple-legacy-media-final-cut-pro-macos/)

[![Build Status](https://travis-ci.org/emkay/nesly.png?branch=master)](https://travis-ci.org/emkay/nesly)

## Install
You'll want to install mediainfo from brew (brew install media-info)
Inside the script you set two path variables.

It will run a find command against the path, skipping hidden directories.
It will log those results to a file.
It will then check each file in that list, line by line to get mediainfo details.
If your file contains a special character that isn't a underscore or dash, it will be placed in a "skipped list" for now.
