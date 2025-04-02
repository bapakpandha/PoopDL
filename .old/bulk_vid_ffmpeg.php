
<?php
if (isset($argv[1])) {
    include 'init.php';
    $videoPath = $argv[1];
    logToFile("bulk_vid_ffmpeg.log", "vid_ffmpeg Argument", "Receive Argument video dengan Path: $videoPath");
    generateThumbnailAsync($pdo, $videoPath);
}

function generateThumbnailAsync($pdo, $videoPath)
{
    $outputThumbnail = str_replace('.mp4', '_summary.jpg', $videoPath);

    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffmpeg'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR generateThumbnailAsync", "ffmpeg tidak ditemukan di path: " . $ffprobePath);
        throw new Exception("fmpeg tidak ditemukan di path: " . $ffprobePath);
    }

    try {
        $result = calculateIntervalAndScale($videoPath);
    } catch (Exception $e) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR calculateIntervalAndScale", "Error: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }

    $scaleWidth = $result['scale_width'];
    $scaleHeight = $result['scale_height'];
    $interval = $result['interval'];
    $logFile = __DIR__ . '/logs/ffmpeg.log';

    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }

    $command = sprintf(
        "%s -i %s -vf \"select='not(mod(n\\,%d))',scale=%d:%d,tile=6x6\" -vsync vfr -frames:v 1 %s >> %s 2>&1  &",
        escapeshellcmd($ffprobePath),
        escapeshellcmd($videoPath),
        $interval,
        $scaleWidth,
        $scaleHeight,
        escapeshellarg($outputThumbnail),
        $logFile
    );

    shell_exec($command);
    logToFile("bulk_vid_ffmpeg.log", "function generateThumbnailAsync", "Mengeksekusi command: $command");

    // Set ukuran dan durasi video
    setVideoSizeAndDurationFromDownloaded($pdo, $videoPath, str_replace('.mp4', '', basename($videoPath)));

    // Hapus file video setelah 4 jam
    scheduleFileDeletion($videoPath);
}

function scheduleFileDeletion($filePath)
{
    $delay = 0.25 * 3600; // 15 menit
    $command = sprintf(
        "sleep %d && rm -f %s > /dev/null 2>&1 &",
        $delay,
        escapeshellarg($filePath)
    );
    shell_exec($command);
}

function getTotalFrames($videoPath)
{
    // Periksa apakah file video ada
    if (!file_exists($videoPath)) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getTotalFrames", "File video tidak ditemukan: $videoPath");
        throw new Exception("File video tidak ditemukan: $videoPath");
    }

    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffprobe'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getTotalFrames", "ffprobe tidak ditemukan di path: " . $ffprobePath);
        throw new Exception("ffprobe tidak ditemukan di path: " . $ffprobePath);
    }

    $logFile = __DIR__ . '/logs/ffmpeg.log';

    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }

    $command = sprintf(
        '%s -v quiet -print_format json -show_format -show_streams "%s" 2>&1',
        escapeshellcmd($ffprobePath),
        escapeshellcmd($videoPath)
    );

    // Eksekusi command
    $returnVar = 0;
    logToFile("bulk_vid_ffmpeg.log", "function setVideoSizeAndDurationFromDownloaded for: $videoPath", "Mengeksekusi command: $command");
    exec($command, $outputs, $returnVar);

    if ($returnVar !== 0) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR setVideoSizeAndDurationFromDownloaded for: $videoPath", "Gagal mendapatkan durasi video. Output: " . implode("\n", $outputs));
    }
    $jsonOutput = implode("\n", $outputs);

    $metadata = json_decode($jsonOutput, true);
    $output = $metadata['format']['duration'] ?? null;

    // Periksa apakah output valid
    if ($output === null || trim($output) === '') {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getTotalFrames for: $videoPath", "Gagal mendapatkan jumlah frame dari video: $videoPath");
        // Command untuk mendapatkan total frames
        $command = sprintf(
            '%s -v error -count_packets -select_streams v:0 -show_entries stream=nb_read_packets -of default=nokey=1:noprint_wrappers=1 "%s" 2>&1 ',
            escapeshellcmd($ffprobePath),
            escapeshellcmd($videoPath)
        );

        // Eksekusi command
        $outputs = [];
        $returnVar = 0;
        logToFile("bulk_vid_ffmpeg.log", "function getTotalFrames for: $videoPath", "Mengeksekusi command: $command");
        exec($command, $outputs, $returnVar);

        if ($returnVar !== 0) {
            logToFile("bulk_vid_ffmpeg.log", "ERROR getTotalFrames for: $videoPath", "Gagal mendapatkan total frames. Output: " . implode("\n", $outputs));
        }

        $output = intval($outputs[0]);
    }
    return (int)trim($output);
}

function getVideoResolution($videoPath)
{
    // Periksa apakah file video ada
    if (!file_exists($videoPath)) {
        throw new Exception("File video tidak ditemukan: $videoPath");
    }

    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffprobe'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        throw new Exception("ffprobe tidak ditemukan di path: " . $ffprobePath);
    }

    $logFile = __DIR__ . '/logs/ffmpeg.log';

    // Command untuk mendapatkan resolusi
    $command = sprintf(
        '%s -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 "%s" 2>&1',
        escapeshellcmd($ffprobePath),
        escapeshellcmd($videoPath)
    );

    // Eksekusi command
    $output = [];
    $returnVar = 0;
    logToFile("bulk_vid_ffmpeg.log", "function getVideoResolution for: $videoPath", "Mengeksekusi command: $command");
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getVideoResolution for: $videoPath", "Gagal mendapatkan resolusi video. Output: " . implode("\n", $output));
        // throw new Exception("Gagal mendapatkan resolusi video. Output: " . implode("\n", $output));
    }

    // Pecah output menjadi array [width, height]
    $resolution = explode('x', trim($output[0]));
    if (count($resolution) !== 2) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getVideoResolution for: $videoPath", "Output resolusi tidak valid: $output");
        // fallback
        $metadata = getVideoMetadata($videoPath);
        $resolution = [$metadata['streams'][0]['width'], $metadata['streams'][0]['height']];
        
        // cek apakah resolusi valid
        if (reset($resolution) == 0 || end($resolution) == 0) {
            throw new Exception("Resolusi video tidak valid: " . implode("x", $resolution));
        }
    }

    // Kembalikan resolusi sebagai array
    return [
        'width' => (int)$resolution[0],
        'height' => (int)$resolution[1]
    ];
}

function calculateIntervalAndScale($videoPath)
{
    // Dapatkan total frame video

    try {
        $totalFrames = getTotalFrames($videoPath);
        logToFile("bulk_vid_ffmpeg.log", "function calculateIntervalAndScale for: $videoPath", "Total Frame: $totalFrames");
    } catch (Exception $e) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR calculateIntervalAndScale for: $videoPath", "Error: " . $e->getMessage());
        // echo "Error: " . $e->getMessage();
    }

    try {
        $resolution = getVideoResolution($videoPath);
        logToFile("bulk_vid_ffmpeg.log", "function calculateIntervalAndScale for: $videoPath", "Resolution: {$resolution['width']}x{$resolution['height']}");
        // echo "Resolution: {$resolution['width']}x{$resolution['height']}";
    } catch (Exception $e) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR calculateIntervalAndScale for: $videoPath", "Error: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }

    if ($totalFrames <= 0) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR calculateIntervalAndScale for: $videoPath", "Total frame video tidak valid.");
        throw new Exception("Total frame video tidak valid.");
    }

    $interval = ceil($totalFrames / 36);

    $width = $resolution['width'];
    $height = $resolution['height'];

    if ($width > $height) {
        $scale_width = 160;
        $scale_height = intval(160 * $height / $width);
    } else {
        $scale_height = 160;
        $scale_width = intval(160 * $width / $height);
    }

    // Kembalikan hasil sebagai array
    return [
        'interval' => $interval,
        'scale_width' => $scale_width,
        'scale_height' => $scale_height
    ];
}

function setVideoSizeAndDuration($videoUrl, $videoId)
{
    include 'init.php';

    $tempFile = $videoId . "_temp.mp4";

    $fp = fopen($tempFile, "wb");

    $ch = curl_init($videoUrl);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_RANGE, "0-500000"); // Unduh 500kb pertama saja
    $response = curl_exec($ch);

    $contentLength = 0;

    if (curl_errno($ch) == 0) {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);

        if (preg_match('/Content-Length: (\d+)/', $headers, $matches)) {
            $contentLength = (int)$matches[1];
        }
    }

    curl_close($ch);
    fclose($fp);

    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffprobe'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        throw new Exception("ffprobe tidak ditemukan di path: " . $ffprobePath);
    }

    $command = sprintf(
        '%s -v quiet -print_format json -show_format -show_streams "%s"',
        escapeshellcmd($ffprobePath),
        escapeshellcmd($tempFile)
    );

    // Eksekusi command
    $output = [];
    $returnVar = 0;
    error_log(time() . "mengeksekusi video_duration: $command");
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        throw new Exception("Gagal mendapatkan durasi video. Output: " . implode("\n", $output));
    }

    // Gabungkan array output menjadi string JSON
    $jsonOutput = implode("\n", $output);

    unlink($tempFile); // Hapus file sementara

    $metadata = json_decode($jsonOutput, true);
    $duration = $metadata['format']['duration'] ?? "Unknown";


    echo "Durasi Video: " . gmdate("H:i:s", $duration) . "\n" . "Ukuran Video: " . $contentLength . " bytes\n";

    $stmt = $pdo->prepare("UPDATE video_downloader_data SET video_size = :video_size, video_duration = :video_duration WHERE video_id = :video_id");
    $stmt->bindParam(':video_size', $contentLength, PDO::PARAM_INT);
    $stmt->bindParam(':video_duration', $duration, PDO::PARAM_INT);
    $stmt->bindParam(':video_id', $videoId, PDO::PARAM_STR);
    $stmt->execute();

    return $contentLength;
}

function curl_get_file_size($url)
{
    // Assume failure.
    $result = -1;

    $curl = curl_init($url);

    // Issue a HEAD request and follow any redirects.
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );

    $data = curl_exec($curl);
    curl_close($curl);

    error_log(time() . "data: $data");

    if ($data) {
        $content_length = "unknown";
        $status = "unknown";

        if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
            $status = (int)$matches[1];
        }

        if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
            $content_length = (int)$matches[1];
        }

        // http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        if ($status == 200 || ($status > 300 && $status <= 308)) {
            $result = $content_length;
        }
    }

    return $result;
}

function setVideoSizeAndDurationFromDownloaded($pdo, $videoPath, $videoId)
{
    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffprobe'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        throw new Exception("ffprobe tidak ditemukan di path: " . $ffprobePath);
    }

    $logFile = __DIR__ . '/logs/ffmpeg.log';
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }
    $command = sprintf(
        '%s -v quiet -print_format json -show_format -show_streams "%s" 2>&1',
        escapeshellcmd($ffprobePath),
        escapeshellcmd($videoPath)
    );

    // Eksekusi command
    $output = [];
    $returnVar = 0;
    logToFile("bulk_vid_ffmpeg.log", "function setVideoSizeAndDurationFromDownloaded for: $videoPath", "Mengeksekusi command: $command");
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR setVideoSizeAndDurationFromDownloaded for: $videoPath", "Gagal mendapatkan durasi video. Output: " . implode("\n", $output));
        throw new Exception("Gagal mendapatkan durasi video. Output: " . implode("\n", $output));
    }

    // Gabungkan array output menjadi string JSON
    $jsonOutput = implode("\n", $output);

    $metadata = json_decode($jsonOutput, true);
    $duration = $metadata['format']['duration'] ?? "Unknown";
    $contentLength = $metadata['format']['size'] ?? "Unknown";

    logToFile("bulk_vid_ffmpeg.log", "function setVideoSizeAndDurationFromDownloaded for: $videoPath", "Durasi Video: " . gmdate("H:i:s", $duration) . "\n" . "Ukuran Video: " . $contentLength . " bytes");

    $stmt = $pdo->prepare("UPDATE video_downloader_data SET video_size = :video_size, video_duration = :video_duration WHERE video_id = :video_id");
    $stmt->bindParam(':video_size', $contentLength, PDO::PARAM_INT);
    $stmt->bindParam(':video_duration', $duration, PDO::PARAM_INT);
    $stmt->bindParam(':video_id', $videoId, PDO::PARAM_STR);
    $stmt->execute();

    return $metadata;
}

function getVideoMetadata($videoPath)
{
    $ffprobePath = realpath(__DIR__ . '/../.library/ffmpeg/ffprobe'); // Path ke ffprobe

    if (!file_exists($ffprobePath)) {
        throw new Exception("ffprobe tidak ditemukan di path: " . $ffprobePath);
    }

    $logFile = __DIR__ . '/logs/ffmpeg.log';
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0777);
    }
    $command = sprintf(
        '%s -v quiet -print_format json -show_format -show_streams "%s" 2>&1',
        escapeshellcmd($ffprobePath),
        escapeshellcmd($videoPath)
    );

    // Eksekusi command
    $output = [];
    $returnVar = 0;
    logToFile("bulk_vid_ffmpeg.log", "function getVideoMetadata for: $videoPath", "Mengeksekusi command: $command");
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        logToFile("bulk_vid_ffmpeg.log", "ERROR getVideoMetadata for: $videoPath", "Gagal mendapatkan metadata video. Output: " . implode("\n", $output));
        throw new Exception("Gagal mendapatkan metadata video. Output: " . implode("\n", $output));
    }

    // Gabungkan array output menjadi string JSON
    $jsonOutput = implode("\n", $output);

    $metadata = json_decode($jsonOutput, true);

    return $metadata;
}