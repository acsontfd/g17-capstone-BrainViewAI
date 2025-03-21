<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug ARRAY
$debug = [
    'session_exists' => isset($_SESSION) && !empty($_SESSION),
    'user_id_exists' => isset($_SESSION['user_id']),
    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
    'session_data' => $_SESSION,
    'get_params' => $_GET,
    'server' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'script_filename' => $_SERVER['SCRIPT_FILENAME'],
        'request_method' => $_SERVER['REQUEST_METHOD']
    ],
    'extensions' => [
        'gd' => [
            'installed' => extension_loaded('gd'),
            'info' => extension_loaded('gd') ? gd_info() : null
        ],
        'mysqli' => [
            'installed' => extension_loaded('mysqli')
        ],
        'curl' => [
            'installed' => extension_loaded('curl')
        ],
        'json' => [
            'installed' => extension_loaded('json')
        ]
    ],
    'ini_settings' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]
];

// DB Connection Testing
try {
    require_once 'db-connection.php';
    $db = new Database();
    $debug['database_connection'] = [
        'status' => 'connected',
        'connection_info' => $db->getConnectionInfo()
    ];

    $result = $db->conn->query("SELECT 1 AS test");
    if ($result) {
        $debug['database_query_test'] = 'successful';
    } else {
        $debug['database_query_test'] = 'failed: ' . $db->conn->error;
    }

    $tables = ['ct_scans', 'analysis_results'];
    $debug['tables'] = [];
    
    foreach ($tables as $table) {
        $result = $db->conn->query("SHOW TABLES LIKE '$table'");
        $debug['tables'][$table] = [
            'exists' => $result->num_rows > 0
        ];
        
        if ($result->num_rows > 0) {
            // Count rows
            $count = $db->conn->query("SELECT COUNT(*) AS count FROM $table");
            if ($count) {
                $row = $count->fetch_assoc();
                $debug['tables'][$table]['row_count'] = $row['count'];
            }
            
            // Get structure
            $structure = $db->conn->query("DESCRIBE $table");
            if ($structure) {
                $columns = [];
                while ($col = $structure->fetch_assoc()) {
                    $columns[] = $col;
                }
                $debug['tables'][$table]['columns'] = $columns;
            }
        }
    }
    
    $db->closeConnection();
    
} catch (Exception $e) {
    $debug['database_connection'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Output debug information
echo json_encode($debug, JSON_PRETTY_PRINT);
?> 