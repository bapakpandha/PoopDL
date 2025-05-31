<?php
require_once __DIR__ . '/steps/ValidateUrl.php';


class BulkV2justpaste {
    private $inputUrl;
    private $config;
    private $db;
    private $folderUrls = [];
    private $videoUrls= [];
    private $isDbEnabled;

    public function __construct($inputUrl = null, $config = []) {
        $this->inputUrl = trim($inputUrl);
        $this->config = !empty($config) ? $config : include __DIR__ . '/../config.php';
        $this->isDbEnabled = $this->config['enable_history'] ?? false;
        if ($this->isDbEnabled) {
            require_once __DIR__ . '/../db/DbHandle.php';
            $this->db = new DbHandle();
        }
    }

    public function process() {
        $validateUrl = new ValidateUrl();
        $validationResult = $validateUrl->process($this->inputUrl);
        if ($validationResult['status'] === 'error') {
            return $this->formatError($validationResult['message']);
        }

        $html = $this->fetchHtml($this->inputUrl);
        if (!$html) {
            return $this->formatError("Gagal mengambil halaman dari URL folder");
        }

        $this->folderUrls = $this->extractFolderUrls($html);
        $this->videoUrls = $this->extractVideoUrlsData($html);

        if (empty($this->folderUrls) && empty($this->videoUrls)) {
            return $this->formatError("Tidak ditemukan URL folder dan URL video pada halaman");
        }

        foreach ($this->folderUrls as $folderUrl) {
            $listUrl[] = [
            "url" => $folderUrl,
            "type" => "dood_folder"
            ];
        }

        foreach ($this->videoUrls as $videoUrl) {
            $listUrl[] = [
                "url" => $videoUrl["url"],
                "type" => "dood_video",
            ];
        }

        if(!empty($this->videoUrls)) {
            $videoList = [];

            foreach ($this->videoUrls as $videoUrl) {
            $videoList[] = [
                "video_id" => $videoUrl["video_id"],
                "domain" => $videoUrl["domain"],
            ];
            }
            if ($this->isDbEnabled && ($videoList)) {
                $bulk_id = $this->db->insertHistoryBulkWithBulkUrlV2($videoList, $this->inputUrl, $this->inputUrl);
            }
        }

        return [
            "status" => "success",
            "message" => "Ditemukan " . count($listUrl) . " URL",
            "data" => [
                "result" => $listUrl,
                "url" => $this->inputUrl
            ],
            "failed" => [],
        ];
    }

    private function extractFolderids($html) {
        preg_match_all('/href="([^"]+\/f\/[a-zA-Z0-9]+)"/', $html, $matches);
        preg_match_all('/%2Ff%2F([a-zA-Z0-9]+)/i', $html, $encoded_matches);
        $all_urls = array_merge($matches[1], $encoded_matches[1]);
        $unique_urls = array_values(array_unique($all_urls));

        return $unique_urls;
    }

    private function extractFolderUrls($html) {
        preg_match_all('/https?:\/\/[^\s"\'<>]*\/f\/[a-zA-Z0-9]+/', $html, $matches);
        preg_match_all('/https%3A%2F%2F[^\s"\'<>]*%2Ff%2F[a-zA-Z0-9]+/', $html, $encoded_matches);

        $decoded = array_map('urldecode', $encoded_matches[0]);

        // 4. Gabungkan semua hasil & hapus duplikat
        $all_urls = array_merge($matches[0], $decoded);
        $unique_urls = array_values(array_unique($all_urls));

        return $unique_urls;
    }

    private function extractVideoUrls($html) {
        // 1. Match URL biasa: https://domain.com/d/abc123 atau /e/abc123
        preg_match_all('/https?:\/\/[^\s"\'<>]*\/[de]\/[a-zA-Z0-9]+/', $html, $matches);

        // 2. Match encoded URLs: https%3A%2F%2Fdomain.com%2Fd%2Fabc123 atau %2Fe%2Fabc123
        preg_match_all('/https%3A%2F%2F[^\s"\'<>]*%2F[de]%2F[a-zA-Z0-9]+/', $html, $encoded_matches);

        // 3. Decode encoded matches
        $decoded = array_map('urldecode', $encoded_matches[0]);

        // 4. Gabungkan semua hasil & hapus duplikat
        $all_urls = array_merge($matches[0], $decoded);
        $unique_urls = array_values(array_unique($all_urls));

        return $unique_urls;
    }

    private function extractVideoIds($html) {
        preg_match_all('/\/(d|e)\/([a-zA-Z0-9]+)/', $html, $matches);
        preg_match_all('/%2F(d|e)%2F([a-zA-Z0-9]+)/i', $html, $encoded_matches);
        $all_ids = array_merge($matches[2], $encoded_matches[2]);
        $unique_ids = array_values(array_unique($all_ids));

        return $unique_ids;
    }

    private function formatError($msg) {
        return [
            "status" => "error",
            "message" => $msg,
            "data" => [],
            "failed" => [],
            "url" => $this->inputUrl
        ];
    }

    private function fetchHtml($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, isset($this->config['scrape_timeout']) ? $this->config['scrape_timeout'] : 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code >= 400 ? false : $html;
    }

    private function buildDUrl($baseFolderUrl, $videoId) {
        $parsed = parse_url($baseFolderUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/d/' . $videoId;
    }

    private function buildFUrl($baseFolderUrl, $videoId) {
        $parsed = parse_url($baseFolderUrl);
        return $parsed['scheme'] . '://' . $parsed['host'] . '/f/' . $videoId;
    }

    private function extractVideoUrlsData($html)
    {
        preg_match_all('/https?:\/\/([^\s"\'<>\/]+)\/d\/([a-zA-Z0-9]+)/', $html, $matches);
        preg_match_all('/https%3A%2F%2F([^\s"\'<>%]+)%2Fd%2F([a-zA-Z0-9]+)/', $html, $encoded_matches);

        $decoded = [];
        foreach ($encoded_matches[0] as $index => $encoded_url) {
            $decoded_url = urldecode($encoded_url);
            $domain = urldecode($encoded_matches[1][$index]);
            $video_id = $encoded_matches[2][$index];
            $decoded[] = [
                'domain' => $domain,
                'video_id' => $video_id,
                'url' => $decoded_url
            ];
        }

        $result = [];

        foreach ($matches[0] as $index => $full_url) {
            $result[] = [
                'domain' => $matches[1][$index],
                'video_id' => $matches[2][$index],
                'url' => $full_url
            ];
        }
        $merged = array_merge($result, $decoded);
        $unique = [];
        $seen = [];

        foreach ($merged as $entry) {
            if (!in_array($entry['url'], $seen)) {
                $seen[] = $entry['url'];
                $unique[] = $entry;
            }
        }

        return $unique;
    }
}