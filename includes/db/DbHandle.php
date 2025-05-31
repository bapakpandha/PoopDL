<?php

class DbHandle
{
    public $conn;
    private $historyTable = 'video_downloader_data';
    private $bulkTable = 'bulk_downloader_data';
    public $historyTableV2 = 'poopDL_video_data';
    public $bulkTableV2 = 'poopDL_bulk_data';
    private $config;

    public function __construct()
    {
        $this->config = include __DIR__ . '/../config.php';
        $config = $this->config['db'];
        if (empty($config)) {
            die("Database configuration not found.");
        }

        if ($this->config['enable_history'] !== true) {
            die("Database connection is disabled.");
        }

        $this->conn = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['dbname']
        );

        if ($this->conn->connect_error) {
            throw new Exception("DB Connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
        $this->initTables();
    }

    public function initTables()
    {
        $this->createHistoryTable();
        $this->createBulkTable();
        $this->createHistoryTableV2();
        $this->createBulkTableV2();
    }
    private function createHistoryTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->historyTable} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `video_id` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
            `domain_url` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `hashed_key` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `auth_bearer` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `video_title` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `video_size` INT(11) NULL DEFAULT NULL,
            `video_duration` INT(11) NULL DEFAULT NULL,
            `decoded_src` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `is_bulk` TINYINT(1) NULL DEFAULT '0',
            `bulk_url_id` INT(11) NULL DEFAULT NULL,
            `user_ip` VARCHAR(45) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `timestamp` DATETIME NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `video_id` (`video_id`) USING BTREE,
            INDEX `timestamp` (`timestamp`) USING BTREE,
            INDEX `video_title` (`video_title`) USING BTREE,
            INDEX `domain_url` (`domain_url`) USING BTREE
        )
        COLLATE='utf8mb4_unicode_ci'
        ENGINE=InnoDB
        ;";

        $this->conn->query($sql);
    }

    private function createBulkTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->bulkTable} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `url` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
            `timestamp` DATETIME NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `url` (`url`) USING BTREE
        )
        COLLATE='utf8mb4_unicode_ci'
        ENGINE=InnoDB
        ;";

        $this->conn->query($sql);
    }

    public function insertBulkUrl($url)
    {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO {$this->bulkTable} (url) VALUES (?)");
        $stmt->bind_param("s", $url);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function insertHistory($data)
    {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO {$this->historyTable}
            (video_id, domain_url, hashed_key, auth_bearer, video_title, video_size, video_duration, decoded_src, is_bulk, bulk_url_id, user_ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssiisiis",
            $data['video_id'],
            $data['domain_url'],
            $data['hashed_key'],
            $data['auth_bearer'],
            $data['video_title'],
            $data['video_size'],
            $data['video_duration'],
            $data['decoded_src'],
            $data['is_bulk'],
            $data['bulk_url_id'],
            $data['user_ip']
        );

        return $stmt->execute();
    }

    public function updateBulkHistory($bulk_url_id, $video_id)
    {
        $stmt = $this->conn->prepare("UPDATE {$this->historyTable} SET bulk_url_id = ?, is_bulk = 1 WHERE video_id = ?");
        $stmt->bind_param("ss", $bulk_url_id, $video_id);
        return $stmt->execute();
    }

    public function getAllHistory($limit = 100, $offset = 0)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTable} ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHistoryById($video_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTable} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateHistory($video_id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE {$this->historyTable} SET video_title = ?, video_size = ?, video_duration = ?, decoded_src = ? WHERE video_id = ?");
        $stmt->bind_param("siiss", $data['video_title'], $data['video_size'], $data['video_duration'], $data['decoded_src'], $video_id);
        return $stmt->execute();
    }

    public function deleteHistory($video_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->historyTable} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        return $stmt->execute();
    }

    public function searchHistory($filters)
    {
        $query = "SELECT * FROM {$this->historyTable} WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['domain_url'])) {
            $query .= " AND domain_url LIKE ?";
            $params[] = "%" . $filters['domain_url'] . "%";
            $types .= "s";
        }

        if (!empty($filters['video_title'])) {
            $query .= " AND video_title LIKE ?";
            $params[] = "%" . $filters['video_title'] . "%";
            $types .= "s";
        }

        if (!empty($filters['decoded_src'])) {
            $query .= " AND decoded_src LIKE ?";
            $params[] = "%" . $filters['decoded_src'] . "%";
            $types .= "s";
        }

        if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
            $query .= " AND timestamp BETWEEN ? AND ?";
            $params[] = $filters['date_start'];
            $params[] = $filters['date_end'];
            $types .= "ss";
        } elseif (!empty($filters['date_start'])) {
            $query .= " AND timestamp >= ?";
            $params[] = $filters['date_start'];
            $types .= "s";
        } elseif (!empty($filters['date_end'])) {
            $query .= " AND timestamp <= ?";
            $params[] = $filters['date_end'];
            $types .= "s";
        }

        $query .= " ORDER BY timestamp DESC";

        $stmt = $this->conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function exportHistory($format = 'json')
    {
        $result = $this->getAllHistory(10000); // Limit besar agar semua data diekspor

        if ($format === 'csv') {
            $output = fopen('php://temp', 'r+');
            fputcsv($output, array_keys($result[0]));
            foreach ($result as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            return stream_get_contents($output);
        }

        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function getStats()
    {
        $stats = [];

        $total = $this->conn->query("SELECT COUNT(*) AS total FROM {$this->historyTable}")->fetch_assoc();
        $stats['total_scrapes'] = (int)$total['total'];

        $perDomain = $this->conn->query("SELECT domain_url, COUNT(*) as count FROM {$this->historyTable} GROUP BY domain_url ORDER BY count DESC")->fetch_all(MYSQLI_ASSOC);
        $stats['by_domain'] = $perDomain;

        $perDay = $this->conn->query("SELECT DATE(timestamp) as day, COUNT(*) as count FROM {$this->historyTable} GROUP BY day ORDER BY day DESC")->fetch_all(MYSQLI_ASSOC);
        $stats['by_day'] = $perDay;

        return $stats;
    }

    public function fetchHistory($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->historyTable}
            ORDER BY timestamp DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Ambil total count
        $countResult = $this->conn->query("SELECT COUNT(*) as total FROM {$this->historyTable}");
        $total = $countResult->fetch_assoc()['total'];

        return [
            'results' => $results,
            'total_count' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    public function close()
    {
        $this->conn->close();
    }

    ///////////////////////////////////////////
    //////////////// API V2 ///////////////////
    ///////////////////////////////////////////

    private function createHistoryTableV2()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->historyTableV2} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `video_id` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
            `domain` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `title` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `length` INT(11) NULL DEFAULT NULL,
            `size` INT(11) NULL DEFAULT NULL,
            `thumbnail_url` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `player_url` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `video_src` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `upload_at` DATETIME NULL DEFAULT NULL,
            `user_ip` VARCHAR(45) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
            `is_bulk` TINYINT(1) NOT NULL DEFAULT 0,
            `bulk_id` INT(11) NULL DEFAULT NULL,
            `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `fetch_attempts` INT(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `video_id` (`video_id`) USING BTREE,
            INDEX `domain` (`domain`) USING BTREE,
            INDEX `title` (`title`(255)) USING BTREE,
            INDEX `createdAt` (`createdAt`) USING BTREE,
            INDEX `user_ip` (`user_ip`) USING BTREE,
            INDEX `is_bulk` (`is_bulk`) USING BTREE,
            INDEX `bulk_id` (`bulk_id`) USING BTREE
        )
        COLLATE='utf8mb4_unicode_ci'
        ENGINE=InnoDB
        ;";

        $this->conn->query($sql);
    }

    private function createBulkTableV2()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->bulkTableV2} (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
            `url` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
            `timestamp` DATETIME NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `url` (`url`) USING BTREE,
            INDEX `title` (`title`(255)) USING BTREE
        )
        COLLATE='utf8mb4_bin'
        ENGINE=InnoDB
        ;";

        $this->conn->query($sql);
    }

    public function insertHistoryV2($data)
    {
        $video_id      = $data['video_id'];
        $domain        = $data['domain'] ?? null;
        $title         = $data['title'] ?? null;
        $length        = $data['length'] ?? null;
        $size          = $data['size'] ?? null;
        $thumbnail_url = $data['thumbnail_url'] ?? null;
        $player_url    = $data['player_url'] ?? null;
        $video_src     = $data['video_src'] ?? null;
        $upload_at     = $data['upload_at'] ?? null;
        $user_ip       = $data['user_ip'] ?? null;
        $is_bulk       = $data['is_bulk'] ?? 0;
        $bulk_id       = $data['bulk_id'] ?? null;

        // INSERT dengan kolom insert_attempts
        $sql = "
            INSERT INTO {$this->historyTableV2}
            (video_id, domain, title, length, size, thumbnail_url, player_url, video_src, upload_at, user_ip, is_bulk, bulk_id, fetch_attempts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                domain        = COALESCE(NULLIF(VALUES(domain), NULL), domain),
                title         = COALESCE(NULLIF(VALUES(title), NULL), title),
                length        = COALESCE(NULLIF(VALUES(length), NULL), length),
                size          = COALESCE(NULLIF(VALUES(size), NULL), size),
                thumbnail_url = COALESCE(NULLIF(VALUES(thumbnail_url), NULL), thumbnail_url),
                player_url    = COALESCE(NULLIF(VALUES(player_url), NULL), player_url),
                video_src     = COALESCE(NULLIF(VALUES(video_src), NULL), video_src),
                upload_at     = COALESCE(NULLIF(VALUES(upload_at), NULL), upload_at),
                user_ip       = COALESCE(NULLIF(VALUES(user_ip), NULL), user_ip),
                is_bulk       = COALESCE(NULLIF(VALUES(is_bulk), NULL), is_bulk),
                bulk_id       = COALESCE(NULLIF(VALUES(bulk_id), NULL), bulk_id),
                updatedAt     = CURRENT_TIMESTAMP,
                fetch_attempts = fetch_attempts + 1
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param(
            "sssissssssis",
            $video_id,
            $domain,
            $title,
            $length,
            $size,
            $thumbnail_url,
            $player_url,
            $video_src,
            $upload_at,
            $user_ip,
            $is_bulk,
            $bulk_id
        );

        $stmt->execute();
        $stmt->close();

        if (
            $this->config['enable_get_summary'] &&
            isset($this->config['summary_endpoint']) &&
            is_string($this->config['summary_endpoint']) &&
            trim($this->config['summary_endpoint']) !== ''
        ) {
            $this->getSummaryThumbnail($data, $this->config['summary_endpoint']);
        }
    }


    public function getHistoryV2($video_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTableV2} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAllHistoryV2($limit = 100, $offset = 0)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTableV2} ORDER BY createdAt DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function deleteHistoryV2($video_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->historyTableV2} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        return $stmt->execute();
    }

    public function fetchHistoryV2($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->historyTableV2}
            ORDER BY createdAt DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Ambil total count
        $countResult = $this->conn->query("SELECT COUNT(*) as total FROM {$this->historyTableV2}");
        $total = $countResult->fetch_assoc()['total'];

        return [
            'results' => $results,
            'total_count' => $total,
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    public function insertHistoryBulkWithBulkUrlV2(array $videoList, string $bulkUrl, ?string $bulkTitle = null)
    {
        if (empty($videoList)) return;

        $bulk_id = null;

        $sqlSelect = "SELECT id FROM {$this->bulkTableV2} WHERE url = ?";
        $stmtSelect = $this->conn->prepare($sqlSelect);
        $stmtSelect->bind_param("s", $bulkUrl);
        $stmtSelect->execute();
        $stmtSelect->bind_result($existingId);
        if ($stmtSelect->fetch()) {
            $bulk_id = $existingId;
        }
        $stmtSelect->close();

        if (!$bulk_id) {
            $sqlInsert = "INSERT INTO {$this->bulkTableV2} (url, title) VALUES (?, ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->bind_param("ss", $bulkUrl, $bulkTitle);
            $stmtInsert->execute();
            $bulk_id = $stmtInsert->insert_id;
            $stmtInsert->close();
        }

        $placeholders = [];
        $values = [];

        foreach ($videoList as $video) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $values[] = $video['video_id'];
            $values[] = $video['domain'] ?? null;
            $values[] = $video['title'] ?? null;
            $values[] = $video['length'] ?? null;
            $values[] = $video['size'] ?? null;
            $values[] = $video['thumbnail_url'] ?? null;
            $values[] = $video['player_url'] ?? null;
            $values[] = $video['video_src'] ?? null;
            $values[] = $video['upload_at'] ?? null;
            $values[] = $video['user_ip'] ?? null;
            $values[] = 1;
            $values[] = $bulk_id;
        }

        $sql = "
            INSERT INTO {$this->historyTableV2}
            (video_id, domain, title, length, size, thumbnail_url, player_url, video_src, upload_at, user_ip, is_bulk, bulk_id)
            VALUES " . implode(", ", $placeholders) . "
            ON DUPLICATE KEY UPDATE
                domain        = COALESCE(NULLIF(VALUES(domain), NULL), domain),
                title         = COALESCE(NULLIF(VALUES(title), NULL), title),
                length        = COALESCE(NULLIF(VALUES(length), NULL), length),
                size          = COALESCE(NULLIF(VALUES(size), NULL), size),
                thumbnail_url = COALESCE(NULLIF(VALUES(thumbnail_url), NULL), thumbnail_url),
                player_url    = COALESCE(NULLIF(VALUES(player_url), NULL), player_url),
                video_src     = COALESCE(NULLIF(VALUES(video_src), NULL), video_src),
                upload_at     = COALESCE(NULLIF(VALUES(upload_at), NULL), upload_at),
                user_ip       = COALESCE(NULLIF(VALUES(user_ip), NULL), user_ip),
                is_bulk       = COALESCE(NULLIF(VALUES(is_bulk), NULL), is_bulk),
                bulk_id       = COALESCE(NULLIF(VALUES(bulk_id), NULL), bulk_id),
                updatedAt     = CURRENT_TIMESTAMP
            ";

        $types = str_repeat("sssissssssis", count($videoList)); // 12 fields x N rows

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        return $bulk_id;
    }
    // dev
    private function getSummaryThumbnail($data, $endpoint)
    {
        if (isset($data['video_src']) && !empty($data['video_src']) &&  is_string($data['video_src']) && trim($data['video_src']) !== '') {
            $body_request = [
                "url" => $data['video_src'],
                "video_id" => $data['video_id'],
            ];

            $url_api = $endpoint;
            $ch = curl_init($url_api);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);

            $json_body = json_encode($body_request);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_body)
            ]);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                $error_message = curl_error($ch);
                error_log('CURL Error: ' . $error_message);
            }
            curl_close($ch);
        }
    }
}