<?php
//error_reporting(E_ERROR | E_PARSE);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set local timezone
date_default_timezone_set('Asia/Jakarta');
//session_start();

// Konfigurasi koneksi MySQL

$dbname = 'test_vid_analyzer';

// ========= PROD =============
$host = '172.19.0.3'; // Host database
$username = 'test_vid_analyzer'; // Username MySQL
$password = '1sampai8*analyzer'; // Password MySQL

// // ========= PROD =============
// $host = 'localhost'; // Host database
// $username = 'root'; // Username MySQL
// $password = ''; // Password MySQL

// Membuat koneksi ke MySQL menggunakan PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// Membuat tabel jika belum ada
$pdo->exec("
            -- Tabel untuk menyimpan data video
            CREATE TABLE IF NOT EXISTS video_data (
                id INT AUTO_INCREMENT PRIMARY KEY, 
                video_id VARCHAR(255) NOT NULL UNIQUE, -- Unik untuk memastikan id video tidak duplikat
                user_ip VARCHAR(45),                   -- Untuk mendukung IPv4 dan IPv6
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, -- Secara otomatis menyimpan waktu saat data dibuat
                video_tag TEXT,                        -- HTML tag video, tipe TEXT karena bisa panjang
                decoded_src TEXT,                      -- URL decoded source
                title VARCHAR(255),                    -- Nama file atau judul video
                INDEX (user_ip),                       -- Index untuk mempercepat pencarian berdasarkan IP
                INDEX (timestamp)                      -- Index untuk pencarian berdasarkan waktu
            );

            -- Tabel untuk menyimpan thumbnail video
            CREATE TABLE IF NOT EXISTS thumbnails (
                id INT AUTO_INCREMENT PRIMARY KEY,
                video_id VARCHAR(255) NOT NULL,        -- Referensi ke tabel video_data
                thumbnail_data LONGBLOB NOT NULL,      -- Data gambar dalam format binary
                FOREIGN KEY (video_id) REFERENCES video_data(video_id) 
                    ON DELETE CASCADE                  -- Jika data video dihapus, thumbnail juga ikut dihapus
            );

            -- Opsional: Tambahkan index untuk meningkatkan performa pencarian
            CREATE INDEX IF NOT EXISTS idx_video_id ON thumbnails (video_id);

            -- Tabel untuk menyimpan konfigurasi
            CREATE TABLE IF NOT EXISTS config (
                name VARCHAR(255) NOT NULL PRIMARY KEY,        
                value VARCHAR(255) NOT NULL      -- Data gambar dalam format binary
            );

            -- Tabel untuk menyimpan data video baru
            CREATE TABLE IF NOT EXISTS video_downloader_data (
                id INT AUTO_INCREMENT PRIMARY KEY, 
                video_id VARCHAR(255) NOT NULL UNIQUE, -- Unik untuk memastikan id video tidak duplikat
                domain_url VARCHAR(255),
                hashed_key VARCHAR(255),
                auth_bearer VARCHAR(255),
                video_title TEXT,                        -- HTML tag video, tipe TEXT karena bisa panjang
                video_size INT,                         -- Ukuran video dalam Kilobyte
                video_duration INT,                     -- Durasi video dalam detik
                decoded_src TEXT,                      -- URL decoded source
                is_bulk BOOLEAN DEFAULT 0,              -- Jika 1, berarti video ini diambil dari bulk downloader
                bulk_url_id INT,                        -- Jika is_bulk = 1, maka ini adalah ID dari bulk downloader
                user_ip VARCHAR(45),                   -- Untuk mendukung IPv4 dan IPv6
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, -- Secara otomatis menyimpan waktu saat data dibuat
                INDEX (timestamp)                      -- Index untuk pencarian berdasarkan waktu
            );

            -- Tabel untuk menyimpan data video bulk baru
            CREATE TABLE IF NOT EXISTS bulk_downloader_data (
                id INT AUTO_INCREMENT PRIMARY KEY, 
                url VARCHAR(255) NOT NULL UNIQUE, -- Unik untuk memastikan url tidak duplikat
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP -- Secara otomatis menyimpan waktu saat data dibuat
            );
");

function insertVideoData($pdo, $video_id, $domain_url, $video_download_hashed_key, $Authorization_download_hashed, $video_title, $direct_link, $is_bulk = false, $bulk_url_id = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO video_downloader_data (video_id, domain_url, hashed_key, auth_bearer, video_title, decoded_src, user_ip, is_bulk, bulk_url_id) 
                       VALUES (:video_id, :domain_url, :hashed_key, :auth_bearer, :video_title, :decoded_src, :user_ip, :is_bulk, :bulk_url_id)
                       ON DUPLICATE KEY UPDATE 
                       domain_url = VALUES(domain_url), 
                       hashed_key = VALUES(hashed_key), 
                       auth_bearer = VALUES(auth_bearer), 
                       video_title = VALUES(video_title), 
                       decoded_src = VALUES(decoded_src), 
                       user_ip = VALUES(user_ip),
                       is_bulk = IF(is_bulk = 0, VALUES(is_bulk), is_bulk),
                       bulk_url_id = IF(is_bulk = 0, VALUES(bulk_url_id), bulk_url_id)");

        $stmt->bindParam(':video_id', $video_id, PDO::PARAM_STR);
        $stmt->bindParam(':domain_url', $domain_url, PDO::PARAM_STR);
        $stmt->bindParam(':hashed_key', $video_download_hashed_key, PDO::PARAM_STR);
        $stmt->bindParam(':auth_bearer', $Authorization_download_hashed, PDO::PARAM_STR);
        $stmt->bindParam(':video_title', $video_title, PDO::PARAM_STR);
        $stmt->bindParam(':decoded_src', $direct_link, PDO::PARAM_STR);
        $stmt->bindParam(':user_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $stmt->bindParam(':is_bulk', $is_bulk, PDO::PARAM_BOOL);
        $stmt->bindParam(':bulk_url_id', $bulk_url_id, PDO::PARAM_INT);

        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log(time() . "Database Insert Error: " . $e->getMessage());
        return false;
    }
}

function getHistoryData($pdo, $count = 30, $offset = 0)
{
    try {
        $stmt = $pdo->prepare("SELECT * FROM video_downloader_data ORDER BY timestamp DESC LIMIT :count OFFSET :offset");
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log(time() . "Database Insert Error: " . $e->getMessage());
        return false;
    }
}

function countHistoryData($pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM video_downloader_data");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log(time() . "Database Insert Error: " . $e->getMessage());
        return false;
    }
}

function getLatestDomain($pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT domain_url FROM video_downloader_data WHERE id = (SELECT MAX(id) FROM video_downloader_data)");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log(time() . "Database Insert Error: " . $e->getMessage());
        return false;
    }
}

function insertBulkData($pdo, $url)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO bulk_downloader_data (url) VALUES (:url) ON DUPLICATE KEY UPDATE url = VALUES(url)");
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->execute();

        $stmt2 = $pdo->prepare("SELECT id FROM bulk_downloader_data WHERE url = :url");
        $stmt2->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt2->execute();

        return $stmt2->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log(time() . "Database Insert Error: " . $e->getMessage());
        return false;
    }
}

function initLogDir()
{
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
        chmod($log_dir, 0777);
    }
}

function logToFile($logFileName, $title, $message)
{
    initLogDir();
    $log_dir = __DIR__ . '/logs';
    $log_file = $log_dir . '/' . $logFileName;
    if (!file_exists($log_file)) {
        file_put_contents($log_file, '====================' . PHP_EOL . 'Log file created at ' . date('Y-m-d H:i:s') . PHP_EOL . '====================' . PHP_EOL . PHP_EOL);
        chmod($log_file, 0777);
    }
    $log_message = date('Y-m-d H:i:s') . ' - ' . $title . PHP_EOL . $message . PHP_EOL . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
