
<?php
if ($argc > 1) {
    include 'init.php';
    $jsonData = $argv[1]; // Get JSON string from CLI arguments
    $videoDataList = json_decode($jsonData, true); // Convert JSON back to array
    $tempDir = __DIR__ . '/temp/';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
        chmod($tempDir, 0777);
    }

    logToFile("bulk_vid_download.log", "bulk_vid_download Argument", "Receive Argument video dengan ID: $jsonData");
    logToFile("bulk_vid_download.log", "Memulai Bulk Download", "Jumlah video: " . count($videoDataList));
    foreach ($videoDataList as $videoData) {
        $videoUrl = $videoData['url'];
        $videoPath = $tempDir . $videoData['title'] . ".mp4";        
        downloadVideo($videoUrl, $videoPath);
    }

    // setelah semua video selesai di download, generate thumbnail
    logToFile("bulk_vid_download.log", "bulk_vid_download Argument", "Generate thumbnail untuk semua video yang sudah di download");
    foreach ($videoDataList as $videoData) {
        $videoPath = $tempDir . $videoData['title'] . ".mp4";
        $vidFfmpegPhpPath = realpath(__DIR__ . '/bulk_vid_ffmpeg.php');
        $logFile = __DIR__ . '/logs/bulk_vid_ffmpeg.log';
        $phpCliPath = realpath(__DIR__ . '/../.library/php-cli/php');


	if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0777);
            file_put_contents($logFile, '====================' . PHP_EOL . 'Log file created at ' . date('Y-m-d H:i:s') . PHP_EOL . '====================' . PHP_EOL . PHP_EOL);
        }

        $command = sprintf(
            '%s %s %s 2>&1 | awk \'{ print strftime("%%Y-%%m-%%d %%H:%%M:%%S"), "bulk_vid_ffmpeg.php: %s", $0 }\' >> %s &',
            escapeshellcmd($phpCliPath),
            escapeshellarg($vidFfmpegPhpPath),
            escapeshellarg($videoPath),
            escapeshellarg($videoPath),
            escapeshellarg($logFile)
        );
        logToFile("bulk_vid_download.log", "Bulk Download", "Mengeksekusi command: $command");
        shell_exec($command);
    }
}


function downloadVideo($videoUrl, $videoPath)
{
    $ch = curl_init($videoUrl);
    $fp = fopen($videoPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Tidak ada timeout (biarkan hingga selesai)
    curl_exec($ch);
    logToFile("bulk_vid_download.log", "function downloadVideo", "$videoPath sedang didownload");

    if (curl_errno($ch)) {
        logToFile("bulk_vid_download.log", "ERROR CURL function downloadVideo for $videoPath", "Download Error: " . curl_error($ch));
    }

    curl_close($ch);
    fclose($fp);
    // Log bahwa download selesai (atau panggil fungsi lain)
    logToFile("bulk_vid_download.log", "Success function downloadVideo", "$videoPath selesai didownload");
}
?>
