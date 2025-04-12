<?php

class Bulk {
    private $inputUrl;
    private $config;
    private $db;
    private $results = [];
    private $logs = [];

    public function __construct($inputUrl = null, $config = []) {
        $this->inputUrl = trim($inputUrl);
        $this->config = !empty($config) ? $config : include __DIR__ . '/../config.php';
    }

    public function process() {
        if (!$this->isValidFolderUrl($this->inputUrl)) {
            return $this->formatError("URL tidak valid");
        }

        $html = $this->fetchHtml($this->inputUrl);
        if (!$html) {
            return $this->formatError("Gagal mengambil halaman dari URL folder");
        }

        $ids = $this->extractVideoIds($html);
        if (empty($ids)) {
            return $this->formatError("Tidak ditemukan URL /d/{id} pada halaman");
        }

        $sukses = [];
        $gagal = [];

        if ($this->config['enable_history']) {
            $this->db = new DbHandle();
            if (count($ids) > 0) {
                $bulk_uid = $this->db->insertBulkUrl($this->inputUrl);
            } else {
                $bulk_uid = null;
            }
        } else {
            $bulk_uid = null;
        }

        foreach ($ids as $id) {
            $videoUrl = $this->buildDUrl($this->inputUrl, $id);

            $scraper = new GetScrapping();
            $result = $scraper->process($videoUrl);

            if ($result['status'] === 'success') {
                if ($bulk_uid !== null) {
                    $videoId = $result['data']['video_id'];
                    $this->db->updateBulkHistory($bulk_uid, $videoId);
                }
                $sukses[] = $result['data'];
            } else {
                $gagal[] = $videoUrl;
                $this->logs[] = "Gagal scraping: $videoUrl - {$result['message']}";
            }
        }

        return [
            "status" => "success",
            "message" => "Ditemukan " . count($ids) . " URL. Berhasil: " . count($sukses) . ", Gagal: " . count($gagal),
            "data" => $sukses,
            "failed" => $gagal,
            "logs" => $this->logs
        ];
    }

    private function isValidFolderUrl($url) {
        return preg_match('#/f/([a-zA-Z0-9]+)#', $url);
    }

    private function fetchHtml($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['scrape_timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->config['user_agent'] ?? "Mozilla/5.0"
        ]);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200 ? $html : false;
    }

    private function extractVideoIds($html) {
        preg_match_all('/\/d\/([a-zA-Z0-9]+)/', $html, $matches);
        preg_match_all('/%2Fd%2F([a-zA-Z0-9]+)/', $html, $encoded_matches);

        $decoded_matches = array_map(function ($match) {
            return preg_replace('/.*%2Fd%2F([a-zA-Z0-9]+)/', '$1', urldecode($match));
        }, $encoded_matches[0]);

        $sanitized_matches = array_map(function ($item) {
            return preg_replace('/^\/d\//', '', $item);
        }, $decoded_matches);

        $all_matches = array_unique(array_merge($matches[1], $sanitized_matches));
        return array_values($all_matches);
    }

    private function buildDUrl($baseFolderUrl, $videoId) {
        $parsed = parse_url($baseFolderUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/d/' . $videoId;
    }

    private function formatError($msg) {
        return [
            "status" => "error",
            "message" => $msg,
            "data" => [],
            "failed" => []
        ];
    }

    public function getLog() {
        return $this->logs;
    }
}
