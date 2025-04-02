<?php
require 'init.php';
if (($_SERVER['REQUEST_METHOD'] == 'GET') && (isset($_GET['check']))) {
    getVideosWithoutSummary($pdo, __DIR__ . '/temp');
    exit();
} 
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dood DL - Poop DL - Dood Downloader - Poop Downloader</title>
    <link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
    <script src="v1.js"></script>
    <style>
        /* Background gradient animation */
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            background-size: 400% 400%;
            animation: gradientBG 10s ease infinite;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center font-sans flex-col">
    <?php
    $request_uri = $_SERVER['REQUEST_URI'];
    if (($_SERVER['REQUEST_METHOD'] == 'GET') && (isset($_GET['history']))) {
        serveHistoryPage();
    } else {
    ?>
        <div class="max-w-lg bg-white/90 backdrop-blur-md p-8 rounded-xl shadow-lg">
            <h1 class="text-3xl font-extrabold text-center text-indigo-600 mb-8">
                Dood/Poop Video Downloader
            </h1>

            <!-- Form -->
            <form action="<?php echo dirname($_SERVER['PHP_SELF']) . '/'; ?>" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="video_url" class="block text-sm font-semibold text-gray-700 mb-1">Dood/Poop URL</label>
                        <input
                            type="url"
                            name="video_url"
                            id="video_url"
                            class="bg-gray-50 block border border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2.5 rounded-lg text-gray-900 text-sm w-full"
                            placeholder="https://poops.id/d/a0b1c2d3e4f5"
                            required>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full py-3 rounded-lg bg-indigo-500 text-white font-bold shadow-lg hover:bg-indigo-600 focus:ring focus:ring-indigo-400 focus:outline-none transition duration-300">
                        Submit
                    </button>
                </div>
            </form>
            <!-- Result -->
            <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'):
                $video_url = htmlspecialchars($_POST['video_url'], ENT_QUOTES, 'UTF-8');
                if (preg_match('/https:\/\/(?:www\.)?([^\/]+)\/[de]\/([^\/]+)/', $video_url, $matches)) {
                    $domain_url = $matches[1];
                    $video_id = $matches[2];

                    if (function_exists('logToFile')) {
                        logToFile("post_access.log", "New POST request", "from " . $_SERVER['REMOTE_ADDR'] . " request POST URL video: $video_url");
                    }

                    if (empty($domain_url) || empty($video_id)) {
                        logToFile("post_access.log", "Invalid URL", "Failed to extract domain_url or video_id from URL: $video_url");
                        echo "<p>Failed to Find Video. Make sure that URL is valid and not redirect URL</p>";
                        exit();
                    } else {
                        $curlResponse = curlToDl2($domain_url, $video_id);
                    }

                    $video_download_hashed_key = $curlResponse['video_download_hashed_key'];
                    $Authorization_download_hashed = $curlResponse['Authorization_download_hashed'];
                    $video_title = $curlResponse['video_title'];

                    // check if key matches
                    if (empty($video_download_hashed_key) || empty($Authorization_download_hashed) || empty($video_title)) {
                        logToFile("post_access.log", "CURL https://$domain_url/dl2?poop_id=$video_id Error", "Failed to extract video_download_hashed_key, Authorization_download_hashed, or video_title" . PHP_EOL . "Response: " . $response);
                        // Try Fallback
                        $domain_url = getLatestDomain($pdo)['domain_url'];
                        $fallbackResponse = curlToDl2($domain_url, $video_id);
                        $video_download_hashed_key = $fallbackResponse['video_download_hashed_key'];
                        $Authorization_download_hashed = $fallbackResponse['Authorization_download_hashed'];
                        $video_title = $fallbackResponse['video_title'];

                        if (empty($video_download_hashed_key) || empty($Authorization_download_hashed) || empty($video_title)) {
                            logToFile("post_access.log", "FALLBACK CURL https://$domain_url/dl2?poop_id=$video_id Error", "Failed to extract video_download_hashed_key, Authorization_download_hashed, or video_title" . PHP_EOL . "Response: " . $response);
                            echo "<p>Failed to Find Video. Make sure that URL is valid and not redirect URL</p>";
                            exit();
                        } else {
                            logToFile("post_access.log", "FALLBACK CURL https://$domain_url/dl2?poop_id=$video_id Success", "Extracted video_download_hashed_key: " . $video_download_hashed_key . ", Authorization_download_hashed: " . $Authorization_download_hashed . ", and video_title: " . $video_title);
                        }
                    } else {
                        logToFile("post_access.log", "CURL https://$domain_url/dl2?poop_id=$video_id Success", "Extracted video_download_hashed_key: " . $video_download_hashed_key . ", Authorization_download_hashed: " . $Authorization_download_hashed . ", and video_title: " . $video_title);
                    }

                    // Step 4: Send another cURL request to get direct link
                    $ch = curl_init("https://mba.dog/download_hashed.php?key=$video_download_hashed_key");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        "Authorization: $Authorization_download_hashed",
                        "Content-Type: application/json",
                        "Origin: https://$domain_url",
                        "Referer: https://$domain_url/",
                        "Sec-CH-UA: \"Not A(Brand\";v=\"8\", \"Chromium\";v=\"132\", \"Google Chrome\";v=\"132\"",
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36"
                    ]);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $response_data = json_decode($response, true);
                    $direct_link = $response_data['direct_link'];
                    $direct_link = htmlspecialchars_decode($direct_link);

                    // check if direct link matches
                    if (empty($direct_link)) {
                        logToFile("post_access.log", "CURL https://mba.dog/download_hashed.php with video_id: $video_id Error", "Failed to extract direct link" . PHP_EOL . "Response: " . $response);
                        echo "<p>Failed to Fetch Video. Maybe server is busy. Try Again Later</p>";
                        exit();
                    } else {
                        logToFile("post_access.log", "CURL https://mba.dog/download_hashed.php with video_id: $video_id Success", "Extracted direct link: " . $direct_link);
                    }

                    if (preg_match("/cloudflare/i", $direct_link)) {
                        insertVideoData($pdo, $video_id, $domain_url, $video_download_hashed_key, $Authorization_download_hashed, $video_title, $direct_link);
                        $thumbnailPath = realpath(__DIR__ . '/temp/' . $video_id . '_summary.jpg');
                        if (!file_exists($thumbnailPath)) {
                            download_video($video_id, $direct_link);
                        } else {
                            logToFile("vid_download.log", "Download Video Ignored", "Thumbnail for video_id: $video_id already exists");
                        }
            ?>
                        <div class="mt-8">
                            <button class="w-full bg-gray-500 text-white font-medium py-2 px-4 rounded-lg hover:bg-gray-600 transition" onclick="toggleResult()">Show/Hide Video</button>
                            <div id="resultContainer" class="w-full max-w-2xl bg-white rounded-lg shadow-lg overflow-hidden mt-6 space-y-6 hidden">
                                <!-- Video Player -->
                                <div class="relative">
                                    <video id="my-video" class="video-js w-full h-auto rounded-t-lg" controls preload="auto" data-setup="{}">
                                        <source src="<?php echo $direct_link ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>

                                <!-- Video Info -->
                                <div class="p-6 text-center">
                                    <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $video_title ?></h2>
                                    <p class="text-sm text-gray-500 mb-4">Video ID: <span class="font-semibold"><?php echo $video_id ?></span></p>

                                    <!-- Download Button -->
                                    <a href=<?php echo $direct_link ?> download class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-blue-600 transition duration-300">
                                        ⬇ Download Video
                                    </a>
                                </div>
                            </div>
                        </div>
            <?php
                    } else {
                        echo "<p>Failed to Fetch Video. Maybe server is busy. Try Again Later</p>";
                    }
                } else {
                    echo "<p>Failed to Find Video. Make sure that URL is valid and not redirect URL</p>";
                }
            endif;
            ?>
        </div>
        <div class="m-8">
            <a href="?history" class="inline-flex items-center justify-center p-5 text-base font-medium text-gray-500 rounded-lg bg-gray-50 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white">
                <span class="w-full">View History</span>
                <svg class="w-4 h-4 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"></path>
                </svg>
            </a>
            <a href="bulk" class="inline-flex items-center justify-center p-5 text-base font-medium text-gray-500 rounded-lg bg-gray-50 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700 dark:hover:text-white">
                <span class="w-full">Bulk Download</span>
                <svg class="w-4 h-4 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"></path>
                </svg>
            </a>
        </div>
    <?php } ?>
</body>
<script>
    function toggleResult() {
        const container = document.getElementById('resultContainer');
        container.classList.toggle('hidden');
    }
</script>

</html>

<?php
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

    logToFile($logFile, "Download Video Started", "Started downloading video: $videoId from URL: $url");
}

function curlToDl2($domain_url, $video_id)
{
    if (function_exists('logToFile')) {
        logToFile("post_access.log", "CURL https://$domain_url/dl2?poop_id=$video_id", "Requesting video_download_hashed_key, Authorization_download_hashed, and video_title");
    }

    $curlResponse = [];
    $ch = curl_init("https://$domain_url/dl2?poop_id=$video_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Extract video_download_hashed_key, Authorization_download_hashed, and video_title
    preg_match('/fetch\("https:\/\/mba\.dog\/download_hashed\.php\?key=([^"]+)"/', $response, $key_matches);
    preg_match('/\'Authorization\': \'Bearer ([^\']+)\'/', $response, $auth_matches);
    preg_match('/<title>([^<]+)<\/title>/', $response, $title_matches);

    $curlResponse['video_download_hashed_key'] = $key_matches[1];
    $curlResponse['Authorization_download_hashed'] = "Bearer " . $auth_matches[1];
    $curlResponse['video_title'] = $title_matches[1];

    return $curlResponse;
}

function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function serveHistoryPage()
{
    global $pdo;
    if (isset($_GET['p'])) {
        $page = filter_var($_GET['p'], FILTER_VALIDATE_INT, ["options" => ["default" => 1, "min_range" => 1]]);
    } else {
        $page = 1;
    }

    if (isset($_GET['d'])) {
        $display = filter_var($_GET['d'], FILTER_VALIDATE_INT, ["options" => ["default" => 30, "min_range" => 1]]);
    } else {
        $display = 30;
    }

    $historyData = getHistoryData($pdo, $display, ($page - 1) * $display);
    $prevPage = ($page > 1) ? ($page - 1) : 1;
    $nextPage = $page + 1;
    $action = dirname($_SERVER['PHP_SELF']) . '/';
    $count = countHistoryData($pdo);

    $startShowing = min($count, (($count > 0) ? (($page - 1) * $display + 1) : 0));
    $endShowing = min($count, $page * $display);

    $html = <<<HTML
        <section class="bg-gray-200 container md:p-10 md:py-20 mx-auto my-14 p-10 px-5 rounded-2xl">
        <h1 class="font-extrabold leading-none lg:text-6xl mb-14 md:text-5xl text-4xl text-center text-indigo-800 tracking-tight">DoodDL History</h1>
        <div class="flex flex-col items-center">
            <span class="text-sm">Showing <span class="font-semibold">{$startShowing}</span> to <span class="font-semibold">{$endShowing}</span> of <span class="font-semibold">{$count}</span> Entries</span>
            <div class="inline-flex mt-2 xs:mt-0">
                <a href="?history&p={$prevPage}">
                    <button class="flex items-center justify-center px-4 h-10 text-base font-medium text-white bg-gray-800 rounded-s hover:bg-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Prev
                    </button>
                </a>
                <a href="?history&p={$nextPage}">
                    <button class="flex items-center justify-center px-4 h-10 text-base font-medium text-white bg-gray-800 border-0 border-s border-gray-700 rounded-e hover:bg-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Next    
                    </button>
                </a>
            </div>
        </div>
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-10 my-8">
HTML;

    foreach ($historyData as $data) {
        $thumbPath = realpath(__DIR__ . '/temp/' . $data['video_id'] . '_summary.jpg');
        if (!file_exists($thumbPath)) {
            $thumbnail = 'https://images.unsplash.com/photo-1476610182048-b716b8518aae?ixid=MXwxMjA3fDB8MHxzZWFyY2h8MzN8fGxhbmRzY2FwZXxlbnwwfHwwfA%3D%3D&amp;ixlib=rb-1.2.1&amp;auto=format&amp;fit=crop&amp;w=900&amp;q=100';
        } else {
            $thumbnail = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/temp/' . $data['video_id'] . '_summary.jpg';
        }
        $duration = gmdate("H:i:s", $data['video_duration']);
        $videoSize = formatBytes($data['video_size']);
        $html .= <<<HTML
            <article class="cursor-pointer duration-500 group hover:-translate-y-2 max-w-md mx-auto rounded-b-2xl shadow-2xl transform w-full pb-8">
                <section class="bg-center bg-cover content h-64 rounded-t-lg relative" style="background-image: url({$thumbnail});" onclick="openModal('{$thumbnail}')">
                    <div class="flex items-end w-full h-full bg-black bg-opacity-20 text-white text-sm font-bold p-4 rounded-t-lg">
                        <div class="w-1/2 flex items-center">
                            <span>{$videoSize}</span>
                        </div>
                        <div class="w-1/2 flex items-center flex-row-reverse">
                            <span class="place-items-end">{$duration}</span>
                        </div>
                    </div>
                </section>
                <div class="mt-4 px-4">
                    <span class="inline-block px-2 py-1 leading-none bg-orange-200 text-orange-800 rounded-full font-semibold uppercase tracking-wide text-xs">{$data['video_id']}</span>
                    <h2 class="mt-4 text-base font-medium text-gray-400">{$data['timestamp']}</h2>
                    <p class="mt-2 text-2xl text-gray-700 overflow-x-auto">
                        <a class="cursor-pointer hover:underline" onclick="showHistory('https:\/\/{$data['domain_url']}\/d\/{$data['video_id']}')">{$data['video_title']}</a>
                    </p>
                </div>
            </article>
HTML;
    }

    $html .= <<<HTML
        </section>
        <div class="flex flex-col items-center">
            <span class="text-sm">Showing <span class="font-semibold">{$startShowing}</span> to <span class="font-semibold">{$endShowing}</span> of <span class="font-semibold">{$count}</span> Entries</span>
            <div class="inline-flex mt-2 xs:mt-0">
                <a href="?history&p={$prevPage}">
                    <button class="flex items-center justify-center px-4 h-10 text-base font-medium text-white bg-gray-800 rounded-s hover:bg-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Prev
                    </button>
                </a>
                <a href="?history&p={$nextPage}">
                    <button class="flex items-center justify-center px-4 h-10 text-base font-medium text-white bg-gray-800 border-0 border-s border-gray-700 rounded-e hover:bg-gray-900 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Next    
                    </button>
                </a>
            </div>
        </div>
            <!-- Modal -->
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden">
            <div class="relative max-w-3xl">
                <img id="modalImage" class="rounded-lg shadow-lg max-h-screen">
                <button class="absolute top-2 right-2 bg-white p-2 rounded-full shadow" onclick="closeModal()">✖</button>
            </div>
        </div>
    </section>
    <script>
        function showHistory(url) {
            if (url) {              
                const form = document.createElement('form');
                form.method = "post";
                form.action = "{$action}";
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = "video_url";
                hiddenField.value = url;
                form.appendChild(hiddenField);
                document.body.appendChild(form);
                form.submit();
            }
        }
        function openModal(imageSrc) {
                document.getElementById('modalImage').src = imageSrc;
                document.getElementById('imageModal').classList.remove('hidden');
            }

            function closeModal() {
                document.getElementById('imageModal').classList.add('hidden');
        }
    </script>
HTML;

    echo $html;
}

function getVideosWithoutSummary($pdo, $tempDir) {
    try {
        // Step 1: Fetch video IDs from the database
        $stmt = $pdo->query("SELECT video_id FROM video_downloader_data");
        $videoIds = $stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as an array of video_id

        if (!$videoIds) {
            echo json_encode(["status" => "success", "message" => "No video IDs found in database", "missing_videos" => []]);
            return;
        }

        // Step 2: Check if summary images exist
        $missingVideos = [];

        foreach ($videoIds as $videoId) {
            $thumbnailPath = rtrim($tempDir, '/') . '/' . $videoId . '_summary.jpg';

            if (!file_exists($thumbnailPath)) {
                $missingVideos[] = $videoId;
            }
        }

        // Step 3: Return JSON response
        header("Content-Type: application/json");

        if (!empty($missingVideos)) {
            echo json_encode(["status" => "success", "message" => "Some videos are missing summary images", "missing_videos" => $missingVideos]);
        } else {
            echo json_encode(["status" => "success", "message" => "All videos have summary images", "missing_videos" => []]);
        }
    } catch (PDOException $e) {
        header("Content-Type: application/json");
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
}

?>