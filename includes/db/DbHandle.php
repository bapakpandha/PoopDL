<?php

class DbHandle {
    private $conn;
    private $historyTable = 'video_downloader_data';
    private $bulkTable = 'bulk_downloader_data';
    private $config;

    public function __construct() {
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

    public function initTables() {
        $this->createHistoryTable();
        $this->createBulkTable();
    }
    private function createHistoryTable() {
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

    private function createBulkTable() {
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
    public function insertBulkUrl($url) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO {$this->bulkTable} (url) VALUES (?)");
        $stmt->bind_param("s", $url);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function insertHistory($data) {
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

    public function updateBulkHistory($bulk_url_id, $video_id) {
        $stmt = $this->conn->prepare("UPDATE {$this->historyTable} SET bulk_url_id = ?, is_bulk = 1 WHERE video_id = ?");
        $stmt->bind_param("ss", $bulk_url_id, $video_id);
        return $stmt->execute();
    }

    public function getAllHistory($limit = 100, $offset = 0) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTable} ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getHistoryById($video_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->historyTable} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateHistory($video_id, $data) {
        $stmt = $this->conn->prepare("UPDATE {$this->historyTable} SET video_title = ?, video_size = ?, video_duration = ?, decoded_src = ? WHERE video_id = ?");
        $stmt->bind_param("siiss", $data['video_title'], $data['video_size'], $data['video_duration'], $data['decoded_src'], $video_id);
        return $stmt->execute();
    }

    public function deleteHistory($video_id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->historyTable} WHERE video_id = ?");
        $stmt->bind_param("s", $video_id);
        return $stmt->execute();
    }

    public function searchHistory($filters) {
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

    public function exportHistory($format = 'json') {
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

    public function getStats() {
        $stats = [];

        $total = $this->conn->query("SELECT COUNT(*) AS total FROM {$this->historyTable}")->fetch_assoc();
        $stats['total_scrapes'] = (int)$total['total'];

        $perDomain = $this->conn->query("SELECT domain_url, COUNT(*) as count FROM {$this->historyTable} GROUP BY domain_url ORDER BY count DESC")->fetch_all(MYSQLI_ASSOC);
        $stats['by_domain'] = $perDomain;

        $perDay = $this->conn->query("SELECT DATE(timestamp) as day, COUNT(*) as count FROM {$this->historyTable} GROUP BY day ORDER BY day DESC")->fetch_all(MYSQLI_ASSOC);
        $stats['by_day'] = $perDay;

        return $stats;
    }

    public function fetchHistory($page = 1, $perPage = 10) {
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

    public function close() {
        $this->conn->close();
    }
}
