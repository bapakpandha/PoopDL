<?php
header('Content-Type: application/json');
// Baca input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Cek apakah input valid
if (!is_array($input) || !isset($input['step'])) {
    $step = 1;
} else {
    $step = (int) $input['step'];
}

if (!is_int($step) || $step < 1) {
    $step = 1;
}

$url = isset($input['url']) ? $input['url'] : null;

// db dimasukkan ke step 2
$config = !empty($config) ? $config : include __DIR__ . '/../config.php';
$isDbEnabled = $config['enable_history'] ?? false;
if ($isDbEnabled) {
    require_once __DIR__ . '/../db/DbHandle.php';
    $db = new DbHandle();
}

// Routing berdasarkan step
switch ($step) {
    case 1:
        require_once __DIR__ . '/steps/ValidateUrl.php';
        $validateUrl = new ValidateUrl();
        $result = $validateUrl->process($url);
        echo json_encode($result);
        break;

    case 2:
        require_once __DIR__ . '/steps/GetMetrolaguPostIdAndDetail.php';
        $getMetrolaguPostIdAndDetail = new GetMetrolaguPostIdAndDetail();
        $result = $getMetrolaguPostIdAndDetail->process($url);
        if ($isDbEnabled && $result['status'] == 'success') {
            $db->insertHistoryV2([
                'video_id'      => $result['data']['video_id'],
                'domain'        => $result['data']['domain'],
                'title'         => $result['data']['title'],
                'length'        => $getMetrolaguPostIdAndDetail->convertDurationToSeconds($result['data']['length']),
                'size'          => $getMetrolaguPostIdAndDetail->convertSizeToBytes($result['data']['size']),
                'thumbnail_url' => $result['data']['thumbnail'],
                'upload_at'     => $getMetrolaguPostIdAndDetail->convertTanggalToDate($result['data']['uploadate']),
                'user_ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        }
        echo json_encode($result);
        break;

    case 3:
        require_once __DIR__ . '/steps/GetVidSrcPlayerUrl.php';
        $getVidSrcPlayerUrl = new GetVidSrcPlayerUrl();
        $result = $getVidSrcPlayerUrl->process($input['metrolagu_post_id'], $input['url']);
        if ($isDbEnabled && $result['status'] == 'success') {
            $db->insertHistoryV2([
                'video_id'      => $result['data']['video_id'],
                'player_url'   => $result['data']['fullURL'],
            ]);
        }
        echo json_encode($result);
        break;

    case 4:
        require_once __DIR__ . '/steps/GetVideoSrc.php';
        $getVideoSrc = new GetVideoSrc();
        $fullURL = $input['fullURL'] ?? '';
        $baseURL = $input['baseURL'] ?? '';
        $refererURL = $input['metrolagu_url'] ?? '';
        $result = $getVideoSrc->process($fullURL, $baseURL, $refererURL);
        if ($isDbEnabled && $result['status'] == 'success') {
            $db->insertHistoryV2([
                'video_id'      => $input['video_id'],
                'video_src'   => $result['data']['video_src'],
            ]);
        }
        echo json_encode($result);
        break;

    case 5:
        require_once __DIR__ . '/steps/TryDetectDoodstream.php';
        $tryDetectDoodstream = new TryDetectDoodstream();
        $fullURL = $input['fullURL'] ?? '';
        $result = $tryDetectDoodstream->process($fullURL);
        echo json_encode($result);
        break;

    case 6:
        require_once __DIR__ . '/steps/ScrapingDoodStream.php';
        $scrapingDoodstream = new ScrapingDoodStream();
        $doodstreamURL = $input['iframe_src'] ?? '';
        $url = $input['url'] ?? null;
        $result = $scrapingDoodstream->process($doodstreamURL);
        if ($isDbEnabled && $result['status'] == 'success')  {
            require_once __DIR__ . '/steps/GetMetrolaguPostIdAndDetail.php';
            $getMetrolaguPostIdAndDetail = new GetMetrolaguPostIdAndDetail();
            $urlAndDomain = $getMetrolaguPostIdAndDetail->getDomainAndVideoId($url);
            if ($urlAndDomain['video_id'] !== null) {
                $db->insertHistoryV2([
                    'video_id'      => $urlAndDomain['video_id'],
                    'video_src'   => $result['data']['video_src'],
                ]);
            }
        }
        echo json_encode($result);
        break;
    
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Step tidak dikenali.',
            'step' => $step
        ]);
        break;
}
