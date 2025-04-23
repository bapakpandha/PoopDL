<?php
require_once __DIR__ . '/steps/ValidateUrl.php';


class BulkV2
{
    private $inputUrl;
    private $config;
    private $db;
    private $isDbEnabled;
    private $results = [];
    private $logs = [];

    public function __construct($inputUrl = null, $config = [])
    {
        $this->inputUrl = trim($inputUrl);
        $this->config = !empty($config) ? $config : include __DIR__ . '/../config.php';
        $this->isDbEnabled = $this->config['enable_history'] ?? false;
        if ($this->isDbEnabled) {
            require_once __DIR__ . '/../db/DbHandle.php';
            $this->db = new DbHandle();
        }
    }

    public function process()
    {
        $validateUrl = new ValidateUrl();
        $validationResult = $validateUrl->process($this->inputUrl);
        if ($validationResult['status'] === 'error') {
            return $this->formatError($validationResult['message']);
        }

        $html = $this->fetchHtml($this->inputUrl);
        if (!$html) {
            return $this->formatError("Gagal mengambil halaman dari URL folder");
        }

        $ids = $this->extractVideoIds($html);
        if (empty($ids)) {
            return $this->formatError("Tidak ditemukan URL /d/{id} pada halaman");
        }

        $title = $this->extractFolderTitle($html);
        $videoList = [];
        $listUrl = [];
        foreach ($ids as $id) {
            $videoBuildedUrl = $this->buildDUrl($this->inputUrl, $id);
            $videoUrl = $videoBuildedUrl['fullUrl'];

            $videoList[] = [
                "video_id" => $id,
                "domain" => $videoBuildedUrl['domain']
            ];

            $listUrl[] = [
                "url" => $videoUrl,
                "type" => "dood_video"
            ];
        }

        // Check db enabled
        if ($this->isDbEnabled && ($videoList)) {
            $bulk_id = $this->db->insertHistoryBulkWithBulkUrlV2($videoList, $this->inputUrl, $title);
        }

        return [
            "status" => "success",
            "message" => "Ditemukan " . count($listUrl) . " URL video pada folder",
            "data" => [
                "result" => $listUrl,
                "url" => $this->inputUrl,
                "folder_title" => $title,
            ],
            "failed" => []
        ];
    }

    private function extractVideoIds($html)
    {
        preg_match_all('/\/(d|e)\/([a-zA-Z0-9]+)/', $html, $matches);
        preg_match_all('/%2F(d|e)%2F([a-zA-Z0-9]+)/i', $html, $encoded_matches);
        $all_ids = array_merge($matches[2], $encoded_matches[2]);
        $unique_ids = array_values(array_unique($all_ids));

        return $unique_ids;
    }


    private function formatError($msg)
    {
        return [
            "status" => "error",
            "message" => $msg,
            "data" => [],
            "failed" => []
        ];
    }

    private function fetchHtml($url)
    {
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

    private function buildDUrl($baseFolderUrl, $videoId)
    {
        $parsed = parse_url($baseFolderUrl);
        $fullUrl =  $parsed['scheme'] . '://' . $parsed['host'] . '/d/' . $videoId;
        $domain = $parsed['host'];
        return [
            "fullUrl" => $fullUrl,
            "domain" => $domain,
        ];
    }

    private function extractVdeoUrls($html)
    {
        preg_match_all('/https?:\/\/[^\s"\'<>]*\/d\/[a-zA-Z0-9]+/', $html, $matches);
        preg_match_all('/https%3A%2F%2F[^\s"\'<>]*%2Fd%2F[a-zA-Z0-9]+/', $html, $encoded_matches);
        $decoded = array_map('urldecode', $encoded_matches[0]);

        $all_urls = array_merge($matches[0], $decoded);
        $unique_urls = array_values(array_unique($all_urls));

        return $unique_urls;
    }

    private function extractFolderTitle($html)
    {
        // <title>...</title>
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
