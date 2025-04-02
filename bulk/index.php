<?php
require '../init.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Dood DL - Bulk Poop DL - Bulk Dood Downloader - Bulk Poop Downloader</title>
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon.png">
    <script src="../v1.js"></script>
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

<body cz-shortcut-listen="true">
    <section class="bg-gray-200 container md:p-10 md:py-20 mx-auto my-14 p-10 px-5 rounded-2xl">
        <h1 class="font-extrabold leading-none lg:text-6xl mb-14 md:text-5xl text-4xl text-center text-indigo-800 tracking-tight">Bulk DoodDL</h1>
        <form action="<?php echo dirname($_SERVER['PHP_SELF']) . '/'; ?>" method="POST" class="mb-14 space-y-6">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="bulk_video_url" class="block text-sm font-semibold text-gray-700 mb-1">Bulk Dood/Poop URL</label>
                    <input type="url" name="bulk_video_url" id="bulk_video_url" class="bg-gray-50 block border border-gray-300 focus:border-blue-500 focus:ring-blue-500 p-2.5 rounded-lg text-gray-900 text-sm w-full" placeholder="https://poops.id/d/a0b1c2d3e4f5" required="">
                </div>
            </div>

            <div>
                <button type="submit" class="w-full py-3 rounded-lg bg-indigo-500 text-white font-bold shadow-lg hover:bg-indigo-600 focus:ring focus:ring-indigo-400 focus:outline-none transition duration-300">
                    Submit
                </button>
            </div>
        </form>
        <?php
        if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['bulk_video_url']))) {
            $url = filter_var($_POST['bulk_video_url'], FILTER_SANITIZE_URL);
            $htmlContent = getCurlContent($url);
            $ids = extractIds($htmlContent);
            $latestDomain = getLatestDomain($pdo);
            $latestDomainUrl = $latestDomain['domain_url'];
            $bulkCardsElement = "";
            $videoDataList = [];
            $videoUrls = array_map(function ($id) use ($latestDomainUrl) {
                return "https://" . $latestDomainUrl . '/d/' . $id;
            }, $ids);
            if (count($videoUrls) > 0) {
                $bulk_data = insertBulkData($pdo, $url);
                $bulk_url_id = $bulk_data['id'] ?? null;
            }
            foreach ($videoUrls as $videoUrl) {
                $videoData = getVideoData($videoUrl);
                if ($videoData === null) {
                    continue;
                }
                $video_id = $videoData['video_id'];
                $domain_url = $videoData['domain_url'];
                $video_download_hashed_key = $videoData['hashed_key'];
                $Authorization_download_hashed = $videoData['auth_bearer'];
                $video_title = $videoData['video_title'];
                $direct_link = $videoData['decoded_src'];
                $is_bulk = true;
                if (preg_match("/cloudflare/i", $direct_link)) {
                    insertVideoData($pdo, $video_id, $domain_url, $video_download_hashed_key, $Authorization_download_hashed, $video_title, $direct_link, $is_bulk, $bulk_url_id);
                    $thumbnailPath = realpath(__DIR__ . '/../temp/' . $video_id . '_summary.jpg');
                    if (!file_exists($thumbnailPath)) {
                        // download_video($video_id, $direct_link); // OLD FUNCTION
                        $videoDataList[] = [
                            'title' => $video_id,
                            'url' => $direct_link
                        ];
                    } else {
                        logToFile("vid_download.log", "Download Video Ignored", "Thumbnail for video_id: $video_id already exists");
                    }
                }
                $bulkCardsElement .= bulkVideoCardsElementBuilder($videoData);
            }
        ?>
            <button class="w-full bg-gray-500 text-white font-medium py-2 px-4 rounded-lg hover:bg-gray-600 transition" onclick="toggleResult()">Show/Hide Video</button>
            <section id="resultContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-10 hidden">
                <?php
                echo $bulkCardsElement;
                $action = dirname($_SERVER['PHP_SELF']) . '/../';
                ?>
            </section>
            <script>
                function toggleResult() {
                    const container = document.getElementById('resultContainer');
                    container.classList.toggle('hidden');
                }

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
            </script>
        <?php
            if (count($videoDataList) > 0) {
                // download_videos($videoDataList);
                passingToBackendDownloader($videoDataList);
            }
        } ?>

    </section>


</body>

</html>

<?php

function getCurlContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $output = curl_exec($ch);
    curl_close($ch);

    logToFile('bulk_download.log', 'Bulk Download Get First Curl', "URL: $url");

    return $output;
}

function extractIds($html)
{
    preg_match_all('/\/d\/([a-zA-Z0-9]+)/', $html, $matches);
    preg_match_all('/%2Fd%2F([a-zA-Z0-9]+)/', $html, $encoded_matches);

    $decoded_matches = array_map(function ($match) {
        return preg_replace('/.*%2Fd%2F([a-zA-Z0-9]+)/', '$1', urldecode($match));
    }, $encoded_matches[0]);

    $sanitized_matches = array_map(function ($item) {
        return preg_replace('/^\/d\//', '', $item);
    }, $decoded_matches);

    $all_matches = array_merge($matches[1], $sanitized_matches);

    if (count($all_matches) == 0) {
        logToFile('bulk_download.log', 'Bulk Download Extract IDs', "No matches found in HTML content");
    } else {
        logToFile('bulk_download.log', 'Bulk Download Extract IDs', "Matches found: " . json_encode($all_matches));
    }

    return array_unique($all_matches);
}

function getVideoData($video_url)
{
    if (preg_match('/https:\/\/(?:www\.)?([^\/]+)\/[de]\/([^\/]+)/', $video_url, $matches)) {
        $domain_url = $matches[1];
        $video_id = $matches[2];

        // Step 3: Send cURL request to get HTML response
        $ch = curl_init("https://$domain_url/dl2?poop_id=$video_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Extract video_download_hashed_key, Authorization_download_hashed, and video_title
        preg_match('/fetch\("https:\/\/mba\.dog\/download_hashed\.php\?key=([^"]+)"/', $response, $key_matches);
        preg_match('/\'Authorization\': \'Bearer ([^\']+)\'/', $response, $auth_matches);
        preg_match('/<title>([^<]+)<\/title>/', $response, $title_matches);

        $video_download_hashed_key = $key_matches[1];
        $Authorization_download_hashed = "Bearer " . $auth_matches[1];
        $video_title = $title_matches[1];

        if (empty($video_download_hashed_key) || empty($Authorization_download_hashed) || empty($video_title)) {
            logToFile("bulk_download.log", "CURL https://$domain_url/dl2?poop_id=$video_id Error", "Failed to extract video_download_hashed_key, Authorization_download_hashed, or video_title" . PHP_EOL . "Response: " . $response);
            return null;
        } else {
            logToFile("bulk_download.log", "CURL https://$domain_url/dl2?poop_id=$video_id Success", "Extracted video_download_hashed_key: " . $video_download_hashed_key . ", Authorization_download_hashed: " . $Authorization_download_hashed . ", and video_title: " . $video_title);
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
            logToFile("bulk_download.log", "CURL https://mba.dog/download_hashed.php with video_id: $video_id Error", "Failed to extract direct link" . PHP_EOL . "Response: " . $response);
            echo "<p>Failed to Fetch Video. Maybe server is busy. Try Again Later</p>";
            return null;
        } else {
            logToFile("bulk_download.log", "CURL https://mba.dog/download_hashed.php with video_id: $video_id Success", "Extracted direct link: " . $direct_link);
        }

        $data = [
            'video_id' => $video_id,
            'domain_url' => $domain_url,
            'hashed_key' => $video_download_hashed_key,
            'auth_bearer' => $Authorization_download_hashed,
            'video_title' => $video_title,
            'decoded_src' => $direct_link
        ];
    } else {
        $data = []; // Return empty array if URL is not valid
    }
    return $data;
};

function bulkVideoCardsElementBuilder($videoData)
{
    $video_id = $videoData['video_id'];
    $video_title = $videoData['video_title'];
    $direct_link = $videoData['decoded_src'];

    $element = <<<HTML
                            <div class="w-full max-w-2xl bg-white rounded-lg shadow-lg overflow-hidden mt-6 space-y-6">
                                <!-- Video Player -->
                                <div class="relative">
                                    <video class="video-js w-full h-auto rounded-t-lg" controls="" preload="metadata" data-setup="{}">
                                        <source src="{$direct_link}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>

                                <!-- Video Info -->
                                <div class="p-6 text-center">
                                    <p class="mt-2 text-2xl text-gray-700 overflow-x-auto"><a class="cursor-pointer hover:underline" onclick="showHistory('https:\/\/{$videoData['domain_url']}\/d\/{$videoData['video_id']}')">{$video_title}</a> </p>
                                    <p class="text-sm text-gray-500 mb-4">Video ID: <span class="font-semibold">{$video_id}</span></p>

                                    <!-- Download Button -->
                                    <a href="{$direct_link}" download="" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg shadow-lg hover:bg-blue-600 transition duration-300">
                                        ⬇ Download Video
                                    </a>
                                </div>
                            </div>
HTML;
    return $element;
}

function passingToBackendDownloader($videoDataList)
{
    $vidDownloadPhpPath = realpath(__DIR__ . '/../bulk_vid_download_V2.php');
    $logFile = __DIR__ . '/../logs/bulk_vid_download.log';
    $phpCliPath = realpath(__DIR__ . '/../../.library/php-cli/php');

    if (!file_exists($logFile)) {
        file_put_contents($logFile, '====================' . PHP_EOL . 'Log file created at ' . date('Y-m-d H:i:s') . PHP_EOL . '====================' . PHP_EOL . PHP_EOL);
        chmod($logFile, 0777);
    }
    logToFile("bulk_download.log", 'Passing argument to backend', "EXECUTING...");
    $command = sprintf(
        '%s %s %s 2>&1 | awk \'{ print strftime("%%Y-%%m-%%d %%H:%%M:%%S"), $0 }\' >> %s &',
        escapeshellcmd($phpCliPath),
        escapeshellarg($vidDownloadPhpPath),
        escapeshellarg(json_encode($videoDataList)),
        escapeshellarg($logFile)
    );

    logToFile("bulk_download.log", 'Passing argument to backend', "START Command: $command");
    shell_exec($command);
}
