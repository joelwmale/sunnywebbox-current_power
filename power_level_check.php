<?php
require 'database.php';

$zip = new ZipArchive;
$databaseObj = new Database();

/**
 * Adjust these variables as per your settings.
 */
$SITE_NAME = 'Greenswamp';
$FILE_PATH = '/tmp/sunny'; // DO NOT HAVE A TRAILING / AT THE END OF THE STRING.

// Levels at which the script should consider the power to be in a warning state, or a critical state.
$WARNING = 65;
$CRITICAL = 50;

// Slack endpoint for notifications
$SLACK_ENDPOINT = '';

/**
 * DO NOT CHANGE
 */
$alreadyReported = false;
$SEVERITY_WARNING = 1;
$SEVERITY_CRITICAL = 2;

$files = scandir($FILE_PATH);
foreach ($files as $file) {
    if (strpos($file, 'wb') !== false) {
        // $file = File name of the current for loop
        if ($zip->open("{$FILE_PATH}/$file")) {
            $zip->extractTo($FILE_PATH . '/current');
            $zip->close();
            if (file_exists($FILE_PATH . '/current')) {
                // File unzipped
                $subFiles = scandir($FILE_PATH . '/current');
                foreach ($subFiles as $subFile) {
                    if (strpos($subFile, 'Mean') !== false) {
                        // Mean file
                        if ($zip->open("{$FILE_PATH}/current/$subFile")) {
                            $zip->extractTo($FILE_PATH . '/current/mean');
                            $zip->close();
                            $meanFiles = scandir($FILE_PATH . '/current/mean');
                            $found = false;
                            foreach ($meanFiles as $meanFile) {
                                if (file_exists("{$FILE_PATH}/current/mean/$meanFile") && strpos($meanFile, 'Mean') !== false) {
                                    $xml = simplexml_load_file("{$FILE_PATH}/current/mean/$meanFile");
                                    foreach ($xml->MeanPublic as $object) {
                                        //var_dump($object);
                                        if (strpos($object->Key, 'BatSoc') !== false && strpos($object->Key, 'BatSocErr') == false) {
                                            if ($object->Mean <= $WARNING && !$alreadyReported) {
                                                if ($databaseObj->shouldIReport($object->Mean)) {
                                                    $result = slack($SLACK_ENDPOINT, "{$SITE_NAME} WARNING: Power is below Warning Threshold ($WARNING). Power is currently *{$object->Mean}%*.");
                                                    $databaseObj->addReport($SEVERITY_WARNING, time(), $object->Mean);
                                                    $alreadyReported = true;
                                                }
                                            } elseif ($object->Mean <= $CRITICAL && !$alreadyReported) {
                                                if ($databaseObj->shouldIReport($object->Mean)) {
                                                    $result = slack($SLACK_ENDPOINT, "{$SITE_NAME} CRITICAL: Power is below CRITICAL Threshold ($CRITICAL). Power is currently *{$object->Mean}%*.");
                                                    $databaseObj->addReport($SEVERITY_CRITICAL, time(), $object->Mean);
                                                    $alreadyReported = true;
                                                }
                                            } elseif (!$alreadyReported) {
                                                $databaseObj->addReport('3', time(), $object->Mean);
                                                $alreadyReported = true;
                                            }
                                        }
                                    }
                                }
                                shell_exec($FILE_PATH . '/current/mean/*');
                            }
                        } else {
                            print "Could not open $subFile for extracting..\n\n";
                        }
                    } else {
                        // Log file, maybe delete?
                    }
                }
                shell_exec('rm -R '. $FILE_PATH . '/current/*');
            }
        }
    }
}

shell_exec('rm -R ' . $FILE_PATH . '/*');

function slack($SLACK_ENDPOINT, $message, $room = "service") {
    $room = ($room) ? $room : "service";
    $data = "payload=" . json_encode(array(
            "channel" => "#{$room}", "text" => $message
        ));

    $ch = curl_init($SLACK_ENDPOINT);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}