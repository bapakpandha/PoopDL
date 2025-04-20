<?php
// Set default header response type
header('Content-Type: application/json');

// Load dependencies
require_once '../includes/utils.php';
$config = include '../includes/config.php';

// Ambil method dan path
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$endpoint = trim(str_replace($scriptName, '', $requestUri), '/');

// Ambil verbose mode
$verbose = isset($_GET['verbose']) && $_GET['verbose'] === 'true';

// Helper untuk respon JSON konsisten
function jsonResponse($status, $message, $data = null, $extra = []) {
    global $verbose;
    if ($verbose) {
        $extra['debug'] = [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'script_name' => dirname($_SERVER['SCRIPT_NAME']),
            'endpoint' => trim(str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']), '/'),
            'input' => json_decode(file_get_contents('php://input'), true),
            'server_uri' => $_SERVER['REQUEST_URI'],
        ];
    }

    return json_encode(array_merge([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], $extra), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Routing
if ($method === 'GET' && $endpoint === '') {
        header('Location: docs');
        exit;
    exit;
}

if ($method === 'GET' && $endpoint === 'docs') {
    $docsFile = __DIR__ . '/../includes/docs/index.php';
    if (file_exists($docsFile)) {
        include $docsFile;
    } else {
        http_response_code(404);
        echo 'Docs not found.';
    }
    exit;
}   

// SWAGGER UI swagger.json
if (preg_match('#^docs/(.+)$#', $endpoint, $matches)) {
    $file = __DIR__ . '/../includes/docs/' . $matches[1];

    if (file_exists($file) && is_file($file)) {
        $mime = mime_content_type($file);
        header("Content-Type: $mime");
        readfile($file);
    } else {
        http_response_code(404);
        echo 'File not found.';
    }
    exit;
}

if ($method === 'POST' && $endpoint === 'get') {
    // POST /api => scraping tunggal
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
            echo jsonResponse('error', 'Parameter "url" wajib ada');
        exit;
    }

    $scraper = new GetScrapping();
    $result = $scraper->process($input['url']);
    echo jsonResponse($result['status'], $result['message'], $result['data'] ?? null, $verbose ? ['debug' => $result['debug'] ?? null] : []);
    exit;
}

if ($method === 'POST' && $endpoint === 'v2/get') {
    // POST /steps => scraping persteps
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
            echo jsonResponse('error', 'Parameter "url" wajib ada');
        exit;
    }
    $result = include __DIR__ . '/../includes/steps/direct.php';
    echo jsonResponse($result['status'], $result['message'], $result['data'] ?? null, $verbose ? ['debug' => $result['debug'] ?? null] : []);
    exit;
}

if ($method === 'POST' && $endpoint === 'get/steps') {
    // POST /steps => scraping persteps
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
            echo jsonResponse('error', 'Parameter "url" wajib ada');
        exit;
    }
    require_once __DIR__ . '/../includes/steps/steps.php';
    exit;
}

if ($method === 'POST' && $endpoint === 'bulk') {
    // POST /bulk => scraping banyak
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
            echo jsonResponse('error', 'Parameter "url" wajib ada');
        exit;
    }

    $bulk = new Bulk($input['url']);
    $result = $bulk->process();
    echo jsonResponse($result['status'], $result['message'], $result['data'] ?? null, $verbose ? ['logs' => $result['logs'] ?? null] : []);
    exit;
}

if ($method === 'POST' && $endpoint === 'history/search') {
    // GET /history/search => pencarian
    if (!$config['enable_history']) {
        echo jsonResponse('error', 'Fitur history dimatikan di konfigurasi.');
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $filters = [
        'domain_url' => $input['domain_url'] ?? null,
        'video_title' => $input['video_title'] ?? null,
        'decoded_src' => $input['decoded_src'] ?? null,
        'date_start' => $input['date_start'] ?? null,
        'date_end' => $input['date_end'] ?? null,
    ];

    // Validasi filter
    foreach ($filters as $key => $value) {
        if ($value && !is_string($value)) {
            echo jsonResponse('error', "Filter $key harus berupa string.");
            exit;
        }
    }
    // Validasi format tanggal
    if (isset($filters['date_start']) && !DateTime::createFromFormat('Y-m-d H:i:s', $filters['date_start'])) {
        echo jsonResponse('error', 'Format tanggal tidak valid untuk date_start.');
        exit;
    }
    
    try {
        $db = new DbHandle();
        $result = $db->searchHistory($filters);
        echo jsonResponse('success', count($result) . ' hasil ditemukan', $result ?? []);
    }  catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat pencarian: ' . $e->getMessage()
        ]);
    }

    exit;
}

if ($method === 'POST' && $endpoint === 'history/export') {
    // GET /history/export => mungkin untuk export
    if (!$config['enable_history']) {
        echo jsonResponse('error', 'Fitur history dimatikan di konfigurasi.');
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $filters = [
        'domain_url' => $input['domain_url'] ?? null,
        'video_title' => $input['video_title'] ?? null,
        'decoded_src' => $input['decoded_src'] ?? null,
        'date_start' => $input['date_start'] ?? null,
        'date_end' => $input['date_end'] ?? null,
    ];

    // Validasi filter
    foreach ($filters as $key => $value) {
        if ($value && !is_string($value)) {
            echo jsonResponse('error', "Filter $key harus berupa string.");
            exit;
        }
    }
    // Validasi format tanggal
    if (isset($filters['date_start']) && !DateTime::createFromFormat('Y-m-d H:i:s', $filters['date_start'])) {
        echo jsonResponse('error', 'Format tanggal tidak valid untuk date_start.');
        exit;
    }

    $format = $input['format'] ?? 'json';

    try {
        $db = new DbHandle();
        // if one of the filters is not empty, use searchHistory
        // otherwise, use getAllHistory
        if (array_filter($filters)) {
            $results = $db->searchHistory($filters);
        } else {
            $results = $db->getAllHistory(100000); // Limit besar agar semua data diekspor
        }

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="history_export.csv"');

            $output = fopen('php://output', 'w');
            if (!empty($results)) {
                // Tulis header
                fputcsv($output, array_keys($results[0]));
                // Tulis data
                foreach ($results as $row) {
                    fputcsv($output, $row);
                }
            } else {
                fputcsv($output, ['Data tidak ditemukan.']);
            }
            fclose($output);
            exit;
        } else {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="history_export.json"');

            echo jsonResponse('success', count($results) . ' hasil diekspor', $results ?? []);
            exit;
        }
    } catch (Exception $e) {
        echo jsonResponse('error', 'Gagal melakukan export: ' . $e->getMessage());
        exit;
    }
}

if ($method === 'POST' && $endpoint === 'history') {
    // GET /history => ambil history paginasi
    if (!$config['enable_history']) {
        echo jsonResponse('error', 'Fitur history dimatikan di konfigurasi.');
        exit;
    }
    $input = json_decode(file_get_contents("php://input"), true);
    $page = max((int)($input['page'] ?? 1), 1);
    $per_page = min(max((int)($input['per_page'] ?? 10), 1), 100);
    try {
        $db = new DbHandle($config);
        $result = $db->fetchHistory($page, $per_page);

        echo jsonResponse('success', count($result) . ' data ditemukan', $result ?? [], [
            'meta' => [
                'total_count' => $result['total_count'] ?? 0,
                'current_page' => $result['page'] ?? 1,
                'per_page' => $result['per_page'] ?? 10,
                'total_pages' => ceil($result['total_count'] / $result['per_page']),
            ]
        ]);

    } catch (Exception $e) {
        echo jsonResponse('error', 'Gagal mengambil data: ' . $e->getMessage());
    }
    exit;
}

echo jsonResponse('error', 'Endpoint tidak dikenal: ' . $endpoint);

exit;
