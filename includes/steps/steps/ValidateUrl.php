<?php
header('Content-Type: application/json');

// Baca input JSON
$input = json_decode(file_get_contents('php://input'), true);

// Cek apakah input valid
if (!is_array($input) || !isset($input['step'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Request tidak valid: step tidak ditemukan.',
    ]);
    exit;
}

// Ambil step dan url (kalau ada)
$step = (int) $input['step'];
$url = isset($input['url']) ? $input['url'] : null;

// Routing berdasarkan step
switch ($step) {
    case 1:
        handleStep1($url);
        break;
    
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Step tidak dikenali.',
            'step' => $step
        ]);
        break;
}

// ----------------------------------------
// Fungsi-fungsi
// ----------------------------------------

function handleStep1($url)
{
    if (!$url || !validateUrl($url)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tautan tidak valid',
            'step' => 1
        ]);
        return;
    }

    // Jika valid
    echo json_encode([
        'status' => 'success',
        'message' => 'Memproses Tautan...',
        'step' => 1
    ]);
}

function validateUrl($url)
{
    return preg_match('/https?:\/\/(.+?)\/(d|e)\/([a-zA-Z0-9]+)/', $url);
}
