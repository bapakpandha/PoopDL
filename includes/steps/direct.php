<?php

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$url = isset($input['url']) ? $input['url'] : null;

require_once __DIR__ . '/steps/ValidateUrl.php';
require_once __DIR__ . '/steps/GetMetrolaguPostIdAndDetail.php';
require_once __DIR__ . '/steps/GetVidSrcPlayerUrl.php';
require_once __DIR__ . '/steps/GetVideoSrc.php';

$validateUrl = new ValidateUrl();
$validateUrlResult = $validateUrl->process($url);
$validatedUrl = $validateUrlResult['data']['url'] ?? null;

if (!$validatedUrl) {
    return [
        'status' => 'error',
        'message' => 'URL tidak valid',
        'step' => 1
    ];
    exit;
}

$getMetrolaguPostIdAndDetail = new GetMetrolaguPostIdAndDetail();
$getMetrolaguPostIdAndDetailResult = $getMetrolaguPostIdAndDetail->process($validatedUrl);
$metrolaguPostId = $getMetrolaguPostIdAndDetailResult['data']['metrolagu_post_id'] ?? null;

if (!$metrolaguPostId) {
    return [
        'status' => 'error',
        'message' => 'Gagal mendapatkan ID post Metrolagu',
        'step' => 2
    ];
    exit;
}

$getVidSrcPlayerUrl = new GetVidSrcPlayerUrl();
$getVidSrcPlayerUrlResult = $getVidSrcPlayerUrl->process($metrolaguPostId);
$vidSrcPlayerUrl = $getVidSrcPlayerUrlResult['data']['fullURL'] ?? null;
$vidSrcPlayerBaseUrl = $getVidSrcPlayerUrlResult['data']['baseURL'] ?? null;

if (!$vidSrcPlayerUrl || !$vidSrcPlayerBaseUrl) {
    return [
        'status' => 'error',
        'message' => 'Gagal mendapatkan URL player video',
        'step' => 3
    ];
    exit;
}

$vidSrcUrl = new GetVideoSrc();
$vidSrcUrlResult = $vidSrcUrl->process($vidSrcPlayerUrl, $vidSrcPlayerBaseUrl);
$videoSrc = $vidSrcUrlResult['data']['video_src'] ?? null;

if (!$videoSrc) {
    return [
        'status' => 'error',
        'message' => 'Gagal mendapatkan URL video',
        'step' => 4
    ];
    exit;
}

return [
    'status' => 'success',
    'message' => 'Tahap 4 berhasil. Video URL ditemukan.',
    'step' => 4,
    'data' => [
        'video_src' => $videoSrc,
    ]
];