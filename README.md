Documentation
====================

About:
---------------------
 This script can download files from a Dropbox account to a (web)server folder
 Downloading can take some time so run this script as crontab, shell-script

PHP Dropbox SDK
---------------------
 The Dropbox client, getAccountInfo, getDelta, getFile methods 
 are taken from the PHP Dropbox SDK. You can download the SDK on
 https:www.dropbox.com/developers/core

 PHP Dropbox SDK documentation can be found on:
 http:dropbox.github.io/dropbox-sdk-php/api-docs/v1.1.x/class-Dropbox.Client.html

 Please note: The PHP Dropbox SDK uses 64-bit integers, so 
              this script does not run on 32-bit machines

 Besides the Dropbox SDK this script needs: php5-curl and php5-gd
 When you like to run software tests: php5-memcache, php5-xdebug and 
 php-invoker must be installed, to be sure all requirements are met ...
 install from the composer.json file which can be found in the SDK

 This script only download files which are not yet stored on (web)server,
 The decision to download or not is based on 
 the $output_path and filesize in bytes

 On a file download no futher processing takes place.
 For publication purposes you can always hookup your own functionality eg.
 image_compression ... CMS software.

config options 
--------------------- 

 please adjust all 'config' options matching to your environment and liking


$accessToken
---------------------

 * Register your application via www.dropbox.com/developers/
 *
 * After run "authorize.php" from the Dropbox SDK
 * When logged in the following url might give your $accessToken directly:  
 * https:www.dropbox.com/1/oauth2/authorize?locale=en&client_id=[YOUR_CLIENTID]&response_type=code 

$clientIdentifier
---------------------

 a funny name for your application

$userLocale
---------------------

 a locale in your native languate

$cursor / $use_cursor / $cursor_file
---------------------

 a cursor variable can take off the Dropbox delta from an earlier session:

 1* you might run this script once with $use_cursor = 0 / $cursor = null
    after set the last fetched cursor as staring point to $cursor = '..'
 2* its also possible to set $use_cursor = 1 
    this option will store and read the last fetched $cursor into $cursor_file
    be aware this option has no fallback when a error occurs!
    On big files and slow internet connections Curl might timeout. 
    When facing unfinished sessions/errors 
    ... always set $use_cursor = 0 / $cursor = '...' || $cursor = null  

$document_root / $output_path / Dropbox SDK
---------------------

 Where is this script stored and where to store the output
 Relative paths might work when running as crontab set full paths
 set the full path to lib/Dropbox/autoload.php when 
 having trouble loading the Dropbox SDK

$preg_match_filter
---------------------

 Via the PHP preg_match function its possible to filter on 
 diretories or filenames for example: 
 $preg_match_filter = '';   no filter
 $preg_match_filter = '/^sync/';  files from the sync directory        
 $preg_match_filter = '/^sync|^docs/';  files from sync or docs
 For more information see the PHP documentation

$report_to_screen / $report_to_email
---------------------

 This script creates a report on quota, counts, files_taken, errors ...
 You can send the output of this $report_to_screen and or $report_to_email
 When $report_to_email is set to 1 also set the $email* variables 

 Dropbox getDelta collects data per page. 
 This script keeps on fetching until there are no pages left. 
 Please save bandwith and requests by using the $cursor and preg_match_filter
 On large Dropbox directory trees this script might not be the best solution!!
