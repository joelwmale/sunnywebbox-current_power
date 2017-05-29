<?php
/**
 * Created by PhpStorm.
 * User: Joel.Male
 * Date: 29/05/2017
 * Time: 2:51 PM
 */

$zip = new ZipArchive;

$SITE_NAME = 'Greenswamp';
$WARNING = 65;
$CRITICAL = 50;
$alreadyReported = false;

$files = scandir('/tmp/sunny');
foreach ($files as $file) {
    if (strpos($file, 'wb') !== false) {
        // $file = File name of the current for loop
        if ($zip->open("/tmp/sunny/$file")) {
            $zip->extractTo('/tmp/sunny/current');
            $zip->close();
            if (file_exists('/tmp/sunny/current')) {
                // File unzipped
                $subFiles = scandir('/tmp/sunny/current');
                foreach ($subFiles as $subFile) {
                    if (strpos($subFile, 'Mean') !== false) {
                        // Mean file
                        if ($zip->open("/tmp/sunny/current/$subFile")) {
                            $zip->extractTo('/tmp/sunny/current/mean');
                            $zip->close();
                            $meanFiles = scandir('/tmp/sunny/current/mean');
                            $found = false;
                            foreach ($meanFiles as $meanFile) {
                                if (file_exists("/tmp/sunny/current/mean/$meanFile") && strpos($meanFile, 'Mean') !== false) {
                                    $xml = simplexml_load_file("/tmp/sunny/current/mean/$meanFile");
                                    foreach ($xml->MeanPublic as $object) {
                                        //var_dump($object);
                                        if (strpos($object->Key, 'BatSoc') !== false && strpos($object->Key, 'BatSocErr') == false) {
                                            print $object->Mean . "\n\n";
                                            if ($object->Mean <= $WARNING && !$alreadyReported) {
                                                $result = slack("WARNING: {$SITE_NAME} below {$WARNING}%.");
                                                $alreadyReported = true;
                                            } elseif ($object->Mean <= $CRITICAL && !$alreadyReported) {
                                                $result = slack("CRITICAL: {$SITE_NAME} below {$CRITICAL}%.");
                                                $alreadyReported = true;
                                            }
                                        }
                                    }
                                }
                                shell_exec('/tmp/sunny/current/mean/*');
                            }
                        } else {
                            print "Could not open $subFile for extracting..\n\n";
                        }
                    } else {
                        // Log file, maybe delete?
                    }
                }
                shell_exec('rm -R /tmp/sunny/current/*');
            }
        }
    }
}

shell_exec('rm -R /tmp/sunny/*');

function slack($message, $room = "service") {
    $room = ($room) ? $room : "service";
    $data = "payload=" . json_encode(array(
            "channel" => "#{$room}", "text" => $message
        ));

    $ch = curl_init("https://hooks.slack.com/services/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");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}