<?php
// update_check.php
function checkAndUpdatePriceList(): void {
    // Paths and variables
    $phpBinary = '/usr/bin/php'; // Adjust this path as needed
    $updateScript = __DIR__ . '/update_price_list.php';
    $tmpDir = sys_get_temp_dir();
    $logFile = $tmpDir . '/update_log.txt';
    $debugLog = $tmpDir . '/debug_log.txt';
    $lastUpdateFile = $tmpDir . '/last_update.txt';
    $updateInterval = 3600; // 1 hour in seconds

    // Check if the update is needed
    if (!file_exists($lastUpdateFile) || (time() - filemtime($lastUpdateFile)) > $updateInterval) {
        if (function_exists('exec')) {
            try {
                exec("$phpBinary $updateScript > $logFile 2>&1", $output, $returnVar);
                file_put_contents($debugLog, sprintf("[%s] Update executed. Return: %d\n", date("Y-m-d H:i:s"), $returnVar), FILE_APPEND);
            } catch (Exception $e) {
                file_put_contents($debugLog, "[" . date("Y-m-d H:i:s") . "] Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        } else {
            // exec() disabled on Hostinger – run update inline
            set_time_limit(120);
            ob_start();
            try {
                require $updateScript;
            } catch (Throwable $e) {
                error_log("Price list update failed: " . $e->getMessage());
            }
            ob_end_clean();
        }
        touch($lastUpdateFile);
    }
}

