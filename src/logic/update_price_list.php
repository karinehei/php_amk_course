<?php
/**
 * update_price_list.php
 * Downloads the latest Alko price list and updates the database.
 *
 * DATA SOURCE STRATEGY (Alko has no official public API for the full price list):
 *
 * 1) Sitemaps (recommended, implemented here)
 *    robots.txt lists static and product sitemaps. We parse these to find the
 *    current downloadable price list URL. This is the most durable approach –
 *    when Alko changes/versions the file path, we discover it automatically.
 *
 * 2) NOT used: Intershop View endpoints, /api/*, /rest/*
 *    These are internal, undocumented, and may change without notice.
 *    Do not build production dependencies on them.
 */
const PRICE_LIST_URL = 'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx';

// Fallback URLs if sitemap discovery fails or returns nothing
const PRICE_LIST_URLS = [
    PRICE_LIST_URL,
    'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Muut%20ladattavat%20tiedostot/Hinnastot/alkon-hinnasto-tekstitiedostona.xlsx',
];

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Enable error reporting for debugging

/**
 * Download file from URL. Tries cURL first (better for finicky servers), then file_get_contents.
 * Returns file contents or false on failure.
 *
 * @param string $url URL to fetch
 * @param string $accept Accept header (default: Excel; use */* for robots/sitemaps)
 * @param int $timeout Timeout in seconds
 */
function downloadWithHeaders(string $url, string $accept = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,*/*', int $timeout = 60): string|false
{
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: ' . $accept,
        'Accept-Language: fi-FI,fi;q=0.9,en;q=0.8',
        'Referer: https://www.alko.fi/',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content !== false ? $content : false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers) . "\r\n",
            'follow_location' => true,
        ],
    ]);
    return @file_get_contents($url, false, $context);
}

/**
 * Discover price list URL from Alko sitemaps.
 *
 * Strategy: robots.txt → Sitemap URLs → parse XML for hinnasto/tekstitiedostona links.
 * This avoids hardcoding a specific file path that may change when Alko versions the file.
 *
 * @return string|null The discovered .xlsx or .txt URL, or null if not found
 */
function discoverPriceListUrl(): ?string
{
    $robots = downloadWithHeaders('https://www.alko.fi/robots.txt', '*/*', 15);
    if ($robots === false) {
        return null;
    }

    if (!preg_match_all('/^Sitemap:\s*(\S+)/mi', $robots, $m)) {
        return null;
    }
    $sitemaps = array_unique($m[1]);
    // Prefer staticPages (price list likely there); skip products sitemap (11k+ URLs, can cause timeout/memory)
    $sitemaps = array_filter($sitemaps, static fn ($u) => !preg_match('/type=products/i', $u));
    usort($sitemaps, static fn ($a, $b) => (int) preg_match('/staticPages/i', $b) - (int) preg_match('/staticPages/i', $a));

    $xlsxUrls = [];
    $txtUrls = [];
    $toFetch = $sitemaps;
    $fetched = [];
    $maxSitemaps = 20;

    while (!empty($toFetch) && count($fetched) < $maxSitemaps) {
        $url = array_shift($toFetch);
        if (isset($fetched[$url])) {
            continue;
        }
        $fetched[$url] = true;

        $xml = downloadWithHeaders($url, '*/*', 15);
        if ($xml === false || strlen($xml) > 500000) {
            continue;
        }

        // Sitemap index: <sitemap><loc>...</loc></sitemap> – add to queue
        if (preg_match_all('/<sitemap>\s*<loc>([^<]+)<\/loc>/i', $xml, $subMaps)) {
            foreach ($subMaps[1] as $subUrl) {
                $subUrl = html_entity_decode(trim($subUrl), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $toFetch[] = $subUrl;
            }
        }

        // URL entries: <url><loc>...</loc></url>
        if (preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $locs)) {
            foreach ($locs[1] as $locUrl) {
                $locUrl = html_entity_decode(trim($locUrl), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $isHinnasto = preg_match('/tekstitiedostona/i', $locUrl)
                    || preg_match('/Hinnastot\//i', $locUrl)
                    || preg_match('/Muut[^"\'<]*ladattavat[^"\'<]*tiedostot[^"\'<]*Hinnastot/i', $locUrl);
                if ($isHinnasto && preg_match('/\.xlsx$/i', $locUrl)) {
                    $xlsxUrls[] = $locUrl;
                } elseif ($isHinnasto && preg_match('/\.txt$/i', $locUrl)) {
                    $txtUrls[] = $locUrl;
                }
            }
        }
    }

    return $xlsxUrls[0] ?? $txtUrls[0] ?? null;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Sanitize an xlsx file by removing DOCTYPE/ENTITY declarations from internal XML.
 * PhpSpreadsheet rejects files containing these to prevent XXE attacks, but some
 * Excel files (e.g. from Alko) include them. This creates a safe copy for loading.
 */
function sanitizeXlsxForPhpSpreadsheet(string $sourcePath): string
{
    clearstatcache(true, $sourcePath);
    if (!is_readable($sourcePath)) {
        throw new RuntimeException("File not readable: $sourcePath");
    }

    // Verify it's a valid ZIP (not HTML error page)
    $header = file_get_contents($sourcePath, false, null, 0, 2);
    if ($header === false || substr($header, 0, 2) !== 'PK') {
        throw new RuntimeException("Downloaded file is not a valid Excel/ZIP file (got " . ($header !== false ? bin2hex($header) : 'read error') . ")");
    }

    $baseTmp = is_dir(dirname(__DIR__, 2) . '/temp') ? dirname(__DIR__, 2) . '/temp' : sys_get_temp_dir();
    $tempDir = $baseTmp . '/phpspreadsheet_sanitize_' . uniqid();
    if (!mkdir($tempDir, 0700, true)) {
        throw new RuntimeException("Cannot create temp directory for sanitization");
    }

    $entries = null;

    // Try ZipArchive first
    $zip = new ZipArchive();
    $result = $zip->open($sourcePath);
    if ($result === true) {
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename !== false) {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    $entries[] = [$filename, $content];
                }
            }
        }
        $zip->close();
    }

    // Fallback: PharData (uses different code path, works when ZipArchive fails on some hosts)
    if ($entries === null && class_exists('PharData')) {
        try {
            $pharPath = realpath($sourcePath) ?: $sourcePath;
            $phar = new PharData($pharPath);
            $entries = [];
            $it = new RecursiveIteratorIterator($phar, RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $filename = $file->getSubPathname();
                    if ($filename === '') {
                        $filename = $file->getFilename();
                    }
                    $filename = str_replace('\\', '/', $filename);
                    $content = file_get_contents((string) $file->getPathname());
                    if ($content !== false) {
                        $entries[] = [$filename, $content];
                    }
                }
            }
        } catch (Throwable $e) {
            // PharData failed, will throw below
        }
    }

    if ($entries === null) {
        throw new RuntimeException("Cannot open xlsx file (ZipArchive and PharData both failed). ZipArchive error code: " . ($result ?? 'N/A'));
    }

    foreach ($entries as [$filename, $content]) {
        // Strip DOCTYPE and ENTITY declarations that trigger PhpSpreadsheet's XXE protection
        if (preg_match('/\.xml$/i', $filename)) {
            $content = preg_replace('/<!DOCTYPE[^>]*(?:\[[\s\S]*?\]\s*)?>/', '', $content);
            $content = preg_replace('/<!ENTITY[^>]+>/', '', $content);
        }
        $targetPath = $tempDir . '/' . $filename;
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0700, true);
        }
        file_put_contents($targetPath, $content);
    }

    $sanitizedPath = $baseTmp . '/alko_price_list_sanitized_' . uniqid() . '.xlsx';
    $outZip = new ZipArchive();
    if ($outZip->open($sanitizedPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        // Cleanup temp dir on failure
        $cleanupFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::CATCH_GET_CHILD),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($cleanupFiles as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($tempDir);
        throw new RuntimeException("Cannot create sanitized xlsx file");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($files as $file) {
        if ($file->isFile()) {
            $relativePath = substr($file->getPathname(), strlen($tempDir) + 1);
            $outZip->addFile($file->getPathname(), str_replace('\\', '/', $relativePath));
        }
    }
    $outZip->close();

    // Cleanup temp dir
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::CATCH_GET_CHILD),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($tempDir);

    return $sanitizedPath;
}

function updatePriceList(): void
{
    // Cache file to store last update time
    $cacheFile = sys_get_temp_dir() . '/last_update.txt';
    $cacheDuration = 3600; // Update every 1 hour

    // Check if update is needed
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
        return;
    }

    // Proceed with the update
    echo "Starting the update process...\n";

    // Use project temp dir - ZipArchive often fails with /tmp on shared hosting
    $projectTmp = dirname(__DIR__, 2) . '/temp';
    if (!is_dir($projectTmp)) {
        mkdir($projectTmp, 0755, true);
    }
    $filePath = $projectTmp . '/alko_price_list_' . getmypid() . '.xlsx';

    // Build URL list: discovered from sitemap first, then hardcoded fallbacks
    $urlsToTry = [];
    echo "Discovering price list URL from sitemaps...\n";
    $discoveredUrl = discoverPriceListUrl();
    if ($discoveredUrl !== null) {
        echo "Found in sitemap: $discoveredUrl\n";
        $urlsToTry[] = $discoveredUrl;
    } else {
        echo "No price list URL in sitemaps, using fallback URLs.\n";
    }
    $urlsToTry = array_merge($urlsToTry, PRICE_LIST_URLS);

    // Download the Excel file (Alko often blocks server requests - try multiple URLs and methods)
    $fileContents = false;
    foreach ($urlsToTry as $url) {
        echo "Attempting to download from " . parse_url($url, PHP_URL_HOST) . "...\n";
        $fileContents = downloadWithHeaders($url);
        if ($fileContents !== false && substr($fileContents, 0, 2) === 'PK') {
            break;
        }
        $fileContents = false;
    }

    // Fallback: use manually placed file (download in browser, upload to temp/alko_price_list_manual.xlsx)
    $manualPath = $projectTmp . '/alko_price_list_manual.xlsx';
    $usingManualFile = false;
    if ($fileContents === false && file_exists($manualPath) && is_readable($manualPath)) {
        $header = file_get_contents($manualPath, false, null, 0, 2);
        if ($header !== false && substr($header, 0, 2) === 'PK') {
            echo "Using manually placed file: $manualPath\n";
            $filePath = $manualPath;
            $fileContents = true; // signal success
            $usingManualFile = true;
        }
    }

    if ($fileContents === false) {
        echo "\n";
        echo "DOWNLOAD FAILED: Alko blocks automated requests from servers.\n";
        echo "\n";
        echo "MANUAL FIX: Download the price list in your browser from:\n";
        echo "  https://www.alko.fi/fi/valikoimat-ja-hinnasto\n";
        echo "  (Look for 'Alkon hinnasto tekstitiedostona' / price list download link)\n";
        echo "\n";
        echo "Then upload the .xlsx file to your server as:\n";
        echo "  $manualPath\n";
        echo "\n";
        echo "Re-run the update after uploading. The script will use the manual file.\n";
        die("\n");
    }

    if ($fileContents !== true) {
        file_put_contents($filePath, $fileContents);
        echo "File downloaded successfully to $filePath.\n";
    }

    // Sanitize the file to remove DOCTYPE/ENTITY declarations that trigger PhpSpreadsheet's XXE protection
    $loadPath = $filePath;
    try {
        echo "Sanitizing Excel file for safe loading...\n";
        $loadPath = sanitizeXlsxForPhpSpreadsheet($filePath);
        if (!$usingManualFile) {
            unlink($filePath);
        }
    } catch (Exception $e) {
        echo "Sanitization failed, attempting direct load: " . $e->getMessage() . "\n";
        $loadPath = $filePath;
    }

    // Load the spreadsheet
    try {
        echo "Attempting to load the spreadsheet...\n";
        $spreadsheet = IOFactory::load($loadPath);
        echo "Spreadsheet loaded successfully.\n";
    } catch (Exception $e) {
        if ($loadPath !== $filePath && file_exists($loadPath)) {
            unlink($loadPath);
        }
        die("Failed to load the Excel file: " . $e->getMessage() . "\n");
    }

    $sheet = $spreadsheet->getActiveSheet();

    // Connect to the database
    $pdo = getDbConnection();
    if (!$pdo) {
        die("Failed to connect to the database.\n");
    }
    echo "Database connection established successfully.\n";

    // Clear the old data
    echo "Clearing old data in the database...\n";
    $pdo->exec("TRUNCATE TABLE alko_price_list");

    // Prepare SQL for inserting rows
    $sql = "INSERT INTO alko_price_list (number, name, manufacturer, bottle_size, price, liter_price, type, country_of_manufacture, vintage, alcohol_percentage, energy_kcal_per_100ml)
            VALUES (:number, :name, :manufacturer, :bottle_size, :price, :liter_price, :type, :country_of_manufacture, :vintage, :alcohol_percentage, :energy_kcal_per_100ml)";
    $stmt = $pdo->prepare($sql);

    // Start processing rows from the spreadsheet
    echo "Processing rows from the spreadsheet...\n";
    $rowCount = 0;
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex = $row->getRowIndex();

        // Skip rows 1, 2, 3, and 4
        if ($rowIndex <= 4) {
            continue;
        }

        $data = [];
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            $data[] = isset($cell) ? trim((string)$cell->getValue()) : '';
        }

        // Map the data to table structure
        $data[4] = is_numeric($data[4]) ? $data[4] : 0.0; // price
        $data[5] = is_numeric($data[5]) ? $data[5] : 0.0; // liter_price
        $data[21] = is_numeric($data[21]) ? (float)$data[21] : 0.0; // alcohol_percentage
        $data[27] = is_numeric($data[27]) ? (float)$data[27] : 0.0; // energy_kcal_per_100ml

        $vintage = (is_numeric($data[14]) && $data[14] > 1000 && $data[14] <= date("Y")) ? $data[14] : null;

        $data[0] = !empty($data[0]) ? $data[0] : 'Unknown'; // number
        $data[1] = !empty($data[1]) ? $data[1] : 'Unknown'; // name
        $data[2] = !empty($data[2]) ? $data[2] : 'Unknown'; // manufacturer
        $data[3] = !empty($data[3]) ? $data[3] : 'Unknown'; // bottle_size
        $data[8] = !empty($data[8]) ? $data[8] : 'Unknown'; // type
        $data[12] = !empty($data[12]) ? $data[12] : 'Unknown'; // country

        // Insert row into the database
        try {
            $stmt->execute([
                ':number' => $data[0],
                ':name' => $data[1],
                ':manufacturer' => $data[2],
                ':bottle_size' => $data[3],
                ':price' => $data[4],
                ':liter_price' => $data[5],
                ':type' => $data[8],
                ':country_of_manufacture' => $data[12],
                ':vintage' => $vintage,
                ':alcohol_percentage' => $data[21],
                ':energy_kcal_per_100ml' => $data[27],
            ]);
            $rowCount++;
        } catch (PDOException $e) {
            error_log("Failed to insert row $rowIndex: " . $e->getMessage());
        }
    }

    echo "Processed $rowCount rows successfully.\n";

    // Update cache timestamp after successful update
    file_put_contents($cacheFile, time());
    echo "Price list updated successfully!\n";

    // Cleanup: remove temp files (keep manual file for reuse)
    if (file_exists($loadPath) && $loadPath !== $manualPath) {
        unlink($loadPath);
    }
}

// Run the update function
updatePriceList();
