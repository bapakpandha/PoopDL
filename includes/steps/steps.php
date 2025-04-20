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
        echo json_encode($result);
        break;

    case 3:
        require_once __DIR__ . '/steps/GetVidSrcPlayerUrl.php';
        $getVidSrcPlayerUrl = new GetVidSrcPlayerUrl();
        $result = $getVidSrcPlayerUrl->process($input['metrolagu_post_id']);
        echo json_encode($result);
        break;

    case 4:
        require_once __DIR__ . '/steps/GetVideoSrc.php';
        $getVideoSrc = new GetVideoSrc();
        $fullURL = $input['fullURL'] ?? '';
        $baseURL = $input['baseURL'] ?? '';
        $result = $getVideoSrc->process($fullURL, $baseURL);
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
        $result = $scrapingDoodstream->process($doodstreamURL);
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
