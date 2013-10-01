<?php

###############################################################################
# config
###############################################################################

$accessToken = ''; 
$clientIdentifier = 'your-app-identifier';
$userLocal = 'en_US';

$document_root = './';
$output_path = './';

$cursor             = null;
$use_cursor         = '0';
$cursor_file        = $document_root. 'cursor_file.txt';

$preg_match_filter  = '/^\/sync/';

$report_to_screen   = '1';
$report_to_email    = '0';
$email_from         = 'me@me.com';
$email_reply_to     = $email_from;
$email_to           = $email_from; 
$email_title        = "REPORT: dropbox-sync: ";

###############################################################################
# modules
###############################################################################

require_once __DIR__.'/../lib/Dropbox/autoload.php';
use \Dropbox as dbx;

$client = new dbx\Client($accessToken, $clientIdentifier, $userLocale);

###############################################################################
# program
###############################################################################

// collect accountinfo to be used for human quota calculations

$account_info = $client->getAccountInfo();
$quota_shared = bytes_to_human($account_info['quota_info']['shared']);
$quota = bytes_to_human($account_info['quota_info']['quota']);
$quota_used = bytes_to_human($account_info['quota_info']['normal']);
$quota_left = $account_info['quota_info']['quota'] - $account_info['quota_info']['normal'];
$quota_left = bytes_to_human($quota_left);

$delta_page = array();

// get_previous_cursor

if ($use_cursor == 1) {
    $cursor = get_previous_cursor($cursor_file);
    $delta_page['cursor'] = $cursor;
} else {
    //$cursor = $cursor;
    $delta_page['cursor'] = $cursor;
}
    
// getDelta collets data per page
// as long as we have more (pages)
// fetch the delta (for the current page) and put entries in all_entries 

$dropbox_path_file_to_meta = array(); 
$all_entries = array();
$has_more = '1';
$delta_page['has_more'] = null; 

while ($has_more == 1) {
    
    $delta_page     = $client->getDelta($cursor);    
    $cursor         = $delta_page['cursor'];
    $has_more       = $delta_page['has_more'];
    $all_entries[]  = $delta_page['entries'];
    
    foreach ($delta_page['entries'] as $key => $value) {
        if ($value[1]['is_dir'] == '1' ) {
        } else {   
            $dropbox_path_file_to_meta[$value[0]] = $value[1];
        }    
    }
    
    if ($has_more != '1' ) {
        break;
    }

}

// set_last_cursor

if ($use_cursor == 1) {
    set_last_cursor($cursor_file, $delta_page['cursor']);  
} 

// loop over all_entries, set statistics, when it is a new file -> getFile

$count_new_adds     = 0;
$count_old_adds     = 0;
$count_files        = 0;
$count_removes      = 0;
$count_dirs         = 0;
$files_taken        = '';
$errors             = '';

foreach ($all_entries as $key => $value) {
    
    foreach ($all_entries[$key] as $entry) {
    
        list($dropbox_path, $metadata) = $entry;
        
        if (preg_match($preg_match_filter, $dropbox_path)) {
        
            if ($metadata === null) {
                // (-)
                $count_removes++;
            } else {
                // (+)
                if ($metadata['is_dir'] == 1) {
                    $count_dirs++;
                } else {
                
                    // add_file
                    $local_path = $output_path. $metadata['path'];
                    $path_error = dbx\Path::findErrorNonRoot($dropbox_path);
                    
                    if ($path_error !== null) {
                        $error = "invalid <dropbox_path>: $dropbox_path ($path_error) \n";
                        $errors .= $error;
                    }
                    
                     $original_bytes = $dropbox_path_file_to_meta[$dropbox_path]['bytes'];
                    
                    if (file_exists($local_path) && $original_bytes == filesize($local_path) ) {
                        $count_old_adds++;
                    } else {
                        
                        $count_new_adds++;
                        
                        // if the dir does not exist create it    
                        $dirname = dirname($local_path);
                        if (!is_dir($dirname)) {
                            mkdir($dirname, 0755, true);
                        }
                        
                        if ($report_to_screen == 1) {
                            print "getting: $dropbox_path\n";
                        }
                        
                        $getfile_metadata = $client->getFile($dropbox_path, fopen($local_path, "w"));
                        
                        if ($getfile_metadata === null) {
                            $error = "file not found on dropbox <dropbox_path>: $dropbox_path \n";
                            $errors .= $error;
                        }
                        
                        $msg = "file written to <local_path>: $local_path \n";
                        $files_taken .= $local_path . "\n";
                        
                    }
                    
                    // old and new
                    $count_files++;                
                                   
                } // add_file
                        
            }
        
        } // entry
    
    } // preg_match

} // all_entries

// output statistics

$stats_has_more = $delta_page['has_more'];
$stats_cursor = $delta_page['cursor'];

$report = <<<REPORT
===
quota
===
quota: $quota
quota_used: $quota_used
quota_left: $quota_left
===
stats
===
count_new_adds: $count_new_adds
count_old_adds: $count_old_adds
count_files: $count_files
count_removes: $count_removes
count_dirs: $count_dirs
has_more: $stats_has_more
last_cursor: $stats_cursor
===
files_taken
===
$files_taken
===
errors
===
$errors
REPORT;

if ($report_to_screen == 1) {
    print "$report\n";
}

if ($report_to_email == 1) {

    $email_title = $email_title . " - ". date('Y-m-d H:i:s'); 
    
    $headers = 'From: ' . "$email_from\r\n" .
        'Reply-To: ' . "$email_reply_to\r\n" .
        'X-Mailer: PHP/' . phpversion();
        
    $parameters = '-f'. $email_from;
    
    //$report = wordwrap($report, 70, "\r\n");
    
    mail($email_to, $email_title, $report, $headers);
    
    //mail($email_to, $email_title, $report, $headers, $parameters);

}

###############################################################################
# subroutines
###############################################################################

function bytes_to_human($bytes) {

    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    
    return $bytes;

}

function get_previous_cursor($file) {

    $fh = fopen($file, 'r');
    
    while (!feof($fh)) {
        $line = fgets($fh);
        break;
    }
    
    fclose($fh);
    
    $line = trim($line);
    if (empty($line)) {
        $line = null;
    }
    
    return $line;
    
}

function set_last_cursor($file, $cursor) {
    file_put_contents($file, $cursor, LOCK_EX);
}

#
# EOF
#
?>
