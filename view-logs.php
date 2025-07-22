<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file paths
$logFiles = [
    'error_log' => 'error.log',
    'test_log' => 'test.log',
    'php_error_log' => 'php_errors.log'
];

// Function to read log file
function readLogFile($file) {
    if (!file_exists($file)) {
        return "Log file not found: $file";
    }
    
    $content = file_get_contents($file);
    if ($content === false) {
        return "Error reading log file: $file";
    }
    
    return $content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-container {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Monaco', monospace;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
        .log-entry {
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
        }
        .log-file {
            color: #4ec9b0;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #4ec9b0;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container mt-4">
        <h1 class="mb-4">System Logs</h1>
        
        <?php foreach ($logFiles as $name => $logFile): 
            $filePath = __DIR__ . '/logs/' . $logFile;
        ?>
            <div class="log-entry">
                <div class="log-file"><?= htmlspecialchars($name) ?> (<?= htmlspecialchars($logFile) ?>)</div>
                <div class="log-container">
                    <?= nl2br(htmlspecialchars(readLogFile($filePath))) ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="mt-4">
            <h3>PHP Info</h3>
            <div class="log-container">
                <?php 
                ob_start();
                phpinfo();
                $phpinfo = ob_get_clean();
                echo $phpinfo; 
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
