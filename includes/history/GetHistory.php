<?php

class GetHistory
{
    private $db;
    private $input;

    public function __construct()
    {
        header('Content-Type: application/json');
        $this->input = json_decode(file_get_contents('php://input'), true);
        $config = include __DIR__ . '/../config.php';
        $isDbEnabled = $config['enable_history'] ?? false;
        if ($isDbEnabled) {
            require_once __DIR__ . '/../db/DbHandle.php';
            $this->db = new DbHandle();
        }
    }

    public function getHistory()
    {
        if (!$this->db) {
            echo json_encode(['status' => 'error', 'message' => 'Database connection is disabled.']);
            return;
        }

        // Extract filters from input
        $filters = [
            'search_type' => in_array($this->input['filterSearchType']['label'] ?? 'Files', ['Files', 'Folders']) ? $this->input['filterSearchType']['label'] : 'Files', // string: 'Files' || 'Folders'. Default is 'Files'
            'video_title' => is_string($this->input['searchKeyword'] ?? null) ? htmlspecialchars($this->input['searchKeyword']) : null, // string: title of the video
            'date_start' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->input['filterDateScrappedStart'] ?? '') ? $this->input['filterDateScrappedStart'] : '2020-01-01', // string: 'YYYY-MM-DD'. Default is 2020-01-01
            'date_end' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->input['filterDateScrappedEnd'] ?? '') ? $this->input['filterDateScrappedEnd'] : date('Y-m-d'), // string: 'YYYY-MM-DD'. Default is current date
            'is_fetched' => in_array($this->input['filterIsFetched']['label'] ?? 'All', ['All', 'Fetched', 'Has Not Fetched Yet']) ? $this->input['filterIsFetched']['label'] : 'All', // string: 'All' || 'Fetched' || 'Has Not Fetched Yet'. Default is 'All'
            'has_summarized' => in_array($this->input['filterHasSummarized']['label'] ?? 'All', ['All', 'Summarized', 'Has Not Summarized Yet']) ? $this->input['filterHasSummarized']['label'] : 'All', // string: 'All' || 'Summarized' || 'Has Not Summarized Yet'. Default is 'All'
            'sort_by' => in_array($this->input['filterSortBy']['label'] ?? 'timestamp', ['Time Fetched', 'Name', 'Size', 'Length', 'Total Video']) ? $this->input['filterSortBy']['label'] : 'timestamp', // string: 'Time Fetched' || 'Name' || 'Size' || 'Length' || 'Total Video'. Default is 'Time Fetched'
            'sort_type' => in_array($this->input['filterSortType']['label'] ?? 'DESC', ['Ascending', 'Descending']) ? $this->input['filterSortType']['label'] : 'DESC', // string: 'Ascending' || 'Descending'. Default is 'Ascending'
            'pagination_num' => is_numeric($this->input['pagination_num'] ?? null) && $this->input['pagination_num'] > 0 ? intval($this->input['pagination_num']) : 1,
        ];

        if ($filters['search_type'] == 'Folders') {
            $query = "SELECT * FROM {$this->db->bulkTableV2} WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($filters['video_title'])) {
                $query .= " AND LOWER(title) LIKE LOWER(?)";
                $params[] = "%" . $filters['video_title'] . "%";
                $types .= "s";
            }

            if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
                $query .= " AND timestamp BETWEEN ? AND ?";
                $params[] = $filters['date_start'];
                $params[] = $filters['date_end'] . ' 23:59:59';
                $types .= "ss";
            } elseif (!empty($filters['date_start'])) {
                $query .= " AND timestamp >= ?";
                $params[] = $filters['date_start'];
                $types .= "s";
            } elseif (!empty($filters['date_end'])) {
                $query .= " AND timestamp <= ?";
                $params[] = $filters['date_end'] . ' 23:59:59';
                $types .= "s";
            }

            switch ($filters['sort_by']) {
                case 'Time Fetched':
                    $query .= " ORDER BY timestamp " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                case 'Name':
                    $query .= " ORDER BY title " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                default:
                    break;
            }
        } else {
            $query = "SELECT * FROM {$this->db->historyTableV2} WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($filters['video_title'])) {
                $query .= " AND LOWER(title) LIKE LOWER(?)";
                $params[] = "%" . $filters['video_title'] . "%";
                $types .= "s";
            }

            if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
                $query .= " AND updatedAt BETWEEN ? AND ?";
                $params[] = $filters['date_start'];
                $params[] = $filters['date_end'] . ' 23:59:59';
                $types .= "ss";
            } elseif (!empty($filters['date_start'])) {
                $query .= " AND updatedAt >= ?";
                $params[] = $filters['date_start'];
                $types .= "s";
            } elseif (!empty($filters['date_end'])) {
                $query .= " AND updatedAt <= ?";
                $params[] = $filters['date_end'] . ' 23:59:59';
                $types .= "s";
            }

            if (!empty($filters['is_fetched']) && $filters['is_fetched'] === 'Fetched') {
                $query .= " AND length IS NOT NULL AND size > 0 AND video_src IS NOT NULL";
            } elseif (!empty($filters['is_fetched']) && $filters['is_fetched'] === 'Has Not Fetched Yet') {
                $query .= " AND (length IS NULL OR size <= 0 OR video_src IS NULL)";
            }

            switch ($filters['sort_by']) {
                case 'Time Fetched':
                    $query .= " ORDER BY updatedAt " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                case 'Name':
                    $query .= " ORDER BY title " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                case 'Size':
                    $query .= " ORDER BY size " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                case 'Length':
                    $query .= " ORDER BY length " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
                default:
                    $query .= " ORDER BY updatedAt " . ($filters['sort_type'] === 'Ascending' ? "ASC" : "DESC");
                    break;
            }
        }

        $query .= " LIMIT 20 OFFSET " . (($filters['pagination_num'] ?? 1) - 1) * 20;

        $stmt = $this->db->conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $formattedResult = [
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'data' => [
                'result' => [
                    'video_data' => [],
                    'folder_data' => [],
                ],
                'pagination_num' => 1,
            ],
            'logs' => [
                'filter' => $filters,
            ],
        ];

        foreach ($result as $row) {
            if ($filters['search_type'] == 'Folders') {
                $formattedResult['data']['result']['folder_data'][] = [
                    'id' => $row['id'],
                    'folder_url' => $row['url'],
                    'title' => $row['title'],
                    'fetched_at' => $row['timestamp'],
                    'total_video' => 0,
                    'data' => [],
                ];
                $videoList = $this->getVideoListInsideFolder($row['id']);
                $formattedResult['data']['result']['folder_data'][count($formattedResult['data']['result']['folder_data']) - 1]['total_video'] = $videoList['count'];
                $formattedResult['data']['result']['folder_data'][count($formattedResult['data']['result']['folder_data']) - 1]['data'] = $videoList['data'];
            } else {
                $formattedResult['data']['result']['video_data'][] = [
                    'id' => $row['id'],
                    'video_url' => 'https://' . $row['domain'] . '/d/' . $row['video_id'],
                    'title' => $row['title'],
                    'thumbnail_url' => $row['thumbnail_url'],
                    'summary_url' => '/video_summary/data/' . $row['video_id'] . '_summary.jpg',
                    'video_src' => $row['video_src'],
                    'fetched_at' => $row['updatedAt'],
                    'size' => $row['size'] !== null ? $this->convertBytesToHumanReadable($row['size']) : null,
                    'length' => $row['length'] !==null ? $this->convertSecondsToDuration($row['length']) : null,
                ];
            }
        }

        if ($filters['search_type'] == 'Folders' && $filters['sort_by'] == 'Total Video') {
            usort($formattedResult['data']['result']['folder_data'], function ($a, $b) use ($filters) {
                if ($filters['sort_type'] === 'Ascending') {
                    return $a['total_video'] <=> $b['total_video'];
                } else {
                    return $b['total_video'] <=> $a['total_video'];
                }
            });
        }

        echo json_encode($formattedResult);
    }

    private function getVideoListInsideFolder($bulk_id)
    {
        $query = "SELECT * FROM {$this->db->historyTableV2} WHERE bulk_id = ?";
        $stmt = $this->db->conn->prepare($query);

        if (!$stmt) {
            return [
                'data' => [],
                'count' => 0,
                'error' => 'Failed to prepare statement.'
            ];
        }

        $stmt->bind_param('i', $bulk_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return [
                'data' => [],
                'count' => 0,
                'error' => 'Failed to execute query.'
            ];
        }

        $listResults = [];
        while ($row = $result->fetch_assoc()) {
            $listResults[] = [
                'id' => $row['id'],
                'video_url' => 'https://' . $row['domain'] . '/d/' . $row['video_id'],
                'title' => $row['title'],
                'thumbnail_url' => $row['thumbnail_url'],
                'summary_url' => '/video_summary/data/' . $row['video_id'] . '_summary.jpg',
                'video_src' => $row['video_src'],
                'fetched_at' => $row['updatedAt'],
                'size' => $row['size'] !== null ? $this->convertBytesToHumanReadable($row['size']) : null,
                'length' => $row['length'] !==null ? $this->convertSecondsToDuration($row['length']) : null,
            ];
        }

        return [
            'data' => $listResults,
            'count' => count($listResults)
        ];
    }

    public function convertBytesToHumanReadable($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $exp = floor(log($bytes, 1024));
        $exp = min($exp, count($units) - 1); // maksimal TB

        $value = $bytes / pow(1024, $exp);

        return round($value, $precision) . ' ' . $units[$exp];
    }

    public function convertSecondsToDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            // Format: HH:MM:SS
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        } else {
            // Format: MM:SS
            return sprintf('%02d:%02d', $minutes, $remainingSeconds);
        }
    }

    
}
