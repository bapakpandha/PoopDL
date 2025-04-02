
<?php
if ($argc > 1) {
    include 'init.php';
    $jsonData = $argv[1]; // Get JSON string from CLI arguments
    $videoDataList = json_decode($jsonData, true); // Convert JSON back to array

    // logToFile("bulk_vid_download.log", "bulk_vid_download Argument", "Receive Argument video dengan ID: $jsonData");
    logToFile("bulk_vid_download.log", "Memulai Bulk Download", "Jumlah video: " . count($videoDataList));
    foreach ($videoDataList as $videoData) {
        $videoUrl = $videoData['url'];
        $videoId = $videoData['title'];
        download_video($videoId, $videoUrl);
        sleep(10);
    }
}


function download_video($videoId, $url)
{
    $videoUrl = $url;
    $logFile = 'vid_download.log'; // Log file name
    $vidDownloadPhpPath = realpath(__DIR__ . '/vid_download.php'); // Path to vid_download.php
    $phpCliPath = realpath(__DIR__ . '/../.library/php-cli/php'); // Path to php-cli

    if (!file_exists($phpCliPath)) {
        throw new Exception("phpcli not found at path: " . $phpCliPath);
    }

    $command = sprintf(
        // '%s vid_download.php "%s" %s >> %s 2>&1 &',
        '%s %s "%s" %s 2>&1 | awk \'{ print strftime("%%Y-%%m-%%d %%H:%%M:%%S"), "vid_download.php: %s", $0 }\' >> %s &',
        escapeshellcmd($phpCliPath),
        escapeshellarg($vidDownloadPhpPath),
        ($videoUrl),
        escapeshellarg($videoId),
        escapeshellarg($videoId),
        escapeshellarg(__DIR__ . '/logs/' . $logFile)
    );

    shell_exec($command);

    logToFile($logFile, "Send to Backend", "Sending Arg to vid_download.php video: $videoId from URL: $url");
}
?>
