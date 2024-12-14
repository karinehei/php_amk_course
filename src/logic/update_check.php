<?php
// update_check.php
function checkAndUpdatePriceList(): void {
    // Paths and variables
    $phpBinary = '/usr/bin/php'; // Adjust this path as needed
    $updateScript = __DIR__ . '/update_price_list.php';
    $logFile = '/tmp/update_log.txt';
    $debugLog = '/tmp/debug_log.txt';
    $lastUpdateFile = '/tmp/last_update.txt';
    $updateInterval = 3600; // 1 hour in seconds

    // Check if the update is needed
    if (!file_exists($lastUpdateFile) || (time() - filemtime($lastUpdateFile)) > $updateInterval) {
        try {
            // Execute the update script
            exec("$phpBinary $updateScript > $logFile 2>&1", $output, $returnVar);

            // Log the output and return code
            $logContent = sprintf(
                "[%s] Update executed.\nOutput: %s\nReturn Code: %d\n\n",
                date("Y-m-d H:i:s"),
                implode("\n", $output),
                $returnVar
            );
            file_put_contents($debugLog, $logContent, FILE_APPEND);

            // Update the last update timestamp
            touch($lastUpdateFile);
        } catch (Exception $e) {
            // Log any exceptions
            file_put_contents($debugLog, "[" . date("Y-m-d H:i:s") . "] Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

