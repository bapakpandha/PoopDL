<?php
/**
 * Konfigurasi utama untuk API backend
 */

return [
    // Aktifkan atau nonaktifkan fitur penyimpanan history ke database
    'enable_history' => true,

    // Konfigurasi koneksi database
    'db' => [
        'host' => 'localhost',
        'dbname' => 'test_vid_analyzer',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],

    // Timeout untuk scraping dalam detik
    'scrape_timeout' => 10,

    // User-Agent yang digunakan saat melakukan scraping
    'user_agent' => 'MyCustomScraperBot/1.0',

    // Jumlah maksimum URL untuk request bulk
    'bulk_limit' => 20,

    // URL sumber yang akan di-scrape
    'source_url' => 'https://poophd.video-src.com',

    // Parameter URL untuk streaming
    'stream_url_param' => 'pplayer?id=',



];
