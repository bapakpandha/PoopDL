<?php

class GetMetrolaguPostIdAndDetail
{
    private $validateUrl;

    public function __construct()
    {
        require_once 'ValidateUrl.php';
        $this->validateUrl = new ValidateUrl();
    }

    public function process($url)
    {
        $resultValidate = $this->validateUrl->process($url, 2);
        $url = $resultValidate['data']['url'] ?? null;

        if (!$url) {
            return [
                'status' => 'error',
                'message' => 'Tautan tidak valid',
                'data' => null,
                'step' => 2
            ];
        }

        $html = $this->curlGet($url, $httpcode, $finalUrl);

        if ($httpcode >= 400) {
            return [
                'status' => 'error',
                'message' => 'Gagal mengakses URL (HTTP ' . $httpcode . ')',
                'data' => [
                    'url' => $url,
                    'html' => $html
                ],
                'step' => 2
            ];
        }

        // Parsing HTML
        $result = [
            'video_id' => null,
            'domain' => null,
            'title' => null,
            'length' => null,
            'size' => null,
            'uploadate' => null,
            'thumbnail' => null,
            'metrolagu_url' => null,
            'metrolagu_post_id' => null,
            'origin_url' => $finalUrl
        ];

        // get $video_id from URL
        if (preg_match('/https?:\/\/([^\/]+)\/[de]\/([a-zA-Z0-9]+)/', $url, $matches)) {
            $domain = $matches[1];     // poop.onl
            $video_id = $matches[2];   // 2xyzy8ay2j2y
            $result['video_id'] = $video_id;
            $result['domain'] = $domain;
        } else {
            return [
                'status' => 'error',
                'message' => 'Gagal menemukan ID video (video_id)',
                'step' => 2
            ];
        }

        // <title>...</title>
        if (preg_match('/<title>(.*?)<\/title>/i', $html, $m)) {
            $result['title'] = trim($m[1]);
        }

        // <div class="length">...</div>
        if (preg_match('/<div class="length">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['length'] = trim($m[1]);
        }

        // <div class="size">...</div>
        if (preg_match('/<div class="size">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['size'] = trim($m[1]);
        }

        // <div class="uploadate">...</div>
        if (preg_match('/<div class="uploadate">\s*(.*?)\s*<\/div>/i', $html, $m)) {
            $result['uploadate'] = trim($m[1]);
        }

        if (preg_match('/#poopiframe\s*\{[^}]*background-image:\s*url\([\'"]?(.*?)[\'"]?\)/i', $html, $m)) {
            $result['thumbnail'] = trim($m[1]);
        }

        // Pola wajib: metrolagu_post_id
        if (preg_match('/poopiframe\'\s*,\s*\'https:\/\/berlagu\.com\/jembud\/\'\s*,\s*\'length\'\s*,\s*\'([a-z0-9]+)\'/i', $html, $m)) {
            $result['metrolagu_url'] = null;
            $result['metrolagu_post_id'] = $m[1];
        } elseif (preg_match("/'poopiframe'\s*,\s*'(https?:\/\/[^']+?)'\s*,\s*'length'\s*,\s*'([^']*)'\s*,\s*'([^']*)'/
", $html, $m)) {
                $result['metrolagu_url'] = $m[1];      // Contoh: https://berlagu.com/xxx/ Jadikan sebagai referer untuk mengakses data selanjutnya
                $result['metrolagu_post_id']      = $m[2];      // Contoh: 336d6537336c737674766930
        } else {
            return [
                'status' => 'retry',
                'message' => 'Gagal menemukan ID video (metrolagu_post_id), mencoba mendeteksi doodstream...',
                'data' => [
                    'fullURL' => $url,
                    'html' => $html,
                ],
                'step' => 4
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Sedang mengambil detail video...',
            'step' => 2,
            'data' => $result
        ];
    }

    public function curlGet($url, &$httpcode, &$finalUrl)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
            ]
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $response;
    }

    public function convertSizeToBytes($sizeStr)
    {
        $sizeStr = trim($sizeStr);
        if (preg_match('/([\d\.]+)\s*(KB|MB|GB|TB)/i', $sizeStr, $matches)) {
            $size = (float) $matches[1];
            $unit = strtoupper($matches[2]);

            switch ($unit) {
                case 'KB':
                    return (int) ($size * 1024);
                case 'MB':
                    return (int) ($size * 1024 * 1024);
                case 'GB':
                    return (int) ($size * 1024 * 1024 * 1024);
                case 'TB':
                    return (int) ($size * 1024 * 1024 * 1024 * 1024);
            }
        }
        return 0;
    }

    public function convertDurationToSeconds($duration)
    {
        // Pecah string dengan delimiter ":"
        $parts = explode(':', $duration);

        // Hitung panjang bagian
        $count = count($parts);

        if ($count === 3) {
            // Format: HH:MM:SS
            return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
        } elseif ($count === 2) {
            // Format: MM:SS
            return ((int)$parts[0] * 60) + (int)$parts[1];
        } elseif ($count === 1 && is_numeric($parts[0])) {
            // Format: SS (jarang, tapi untuk jaga-jaga)
            return (int)$parts[0];
        } else {
            // Format tidak dikenal, kembalikan null atau 0
            return null;
        }
    }

    public function getDomainAndVideoId($url)
    {
        $resultValidate = $this->validateUrl->process($url, 2);
        $url = $resultValidate['data']['url'] ?? null;
        if (preg_match('/https?:\/\/([^\/]+)\/[de]\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return [
                'domain' => $matches[1],
                'video_id' => $matches[2]
            ];
        }
        return null;
    }

    public function convertTanggalToDate($tanggalStr)
    {
        $bulanMap = [
            // Bahasa Indonesia
            'Januari' => '01',
            'Februari' => '02',
            'Maret' => '03',
            'April' => '04',
            'Mei' => '05',
            'Juni' => '06',
            'Juli' => '07',
            'Agustus' => '08',
            'September' => '09',
            'Oktober' => '10',
            'November' => '11',
            'Desember' => '12',
    
            // Bahasa Inggris (format pendek)
            'Jan' => '01',
            'Feb' => '02',
            'Mar' => '03',
            'Apr' => '04',
            'May' => '05',
            'Jun' => '06',
            'Jul' => '07',
            'Aug' => '08',
            'Sep' => '09',
            'Oct' => '10',
            'Nov' => '11',
            'Dec' => '12',
        ];
    
        // Format: Apr 15, 2025 atau 15 April 2025
        if (preg_match('/([a-zA-Z]+)\s+(\d{1,2}),?\s+(\d{4})/', $tanggalStr, $m)) {
            // Format Inggris: Apr 15, 2025
            $monthName = ucfirst(strtolower($m[1]));
            $day = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
        } elseif (preg_match('/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/', $tanggalStr, $m)) {
            // Format Indonesia: 15 April 2025
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $monthName = ucfirst(strtolower($m[2]));
            $year = $m[3];
        } else {
            return null;
        }
    
        if (isset($bulanMap[$monthName])) {
            $month = $bulanMap[$monthName];
            return "$year-$month-$day";
        }
    
        return null;
    }

}
