<?php
// update_price_list.php
// This script downloads the latest price list from Alko's website and updates the database.
const PRICE_LIST_URL = 'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx';

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function updatePriceList(): void
{
    // Cache file to store last update time
    $cacheFile = '/tmp/last_update.txt';
    $cacheDuration = 3600; // Update every 1 hour

    // Check if update is needed
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
        return;
    }

    // Proceed with the update
    echo "Starting the update process...\n";

    $filePath = '/tmp/alko_price_list.xlsx';

    // Download the Excel file
    echo "Attempting to download the price list...\n";
    $fileContents = file_get_contents(PRICE_LIST_URL);
    if ($fileContents === false) {
        die("Failed to download the price list.\n");
    }
    file_put_contents($filePath, $fileContents);
    echo "File downloaded successfully to $filePath.\n";

    // Load the spreadsheet
    try {
        echo "Attempting to load the spreadsheet...\n";
        $spreadsheet = IOFactory::load($filePath);
        echo "Spreadsheet loaded successfully.\n";
    } catch (Exception $e) {
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

    // Cleanup: remove downloaded file
    unlink($filePath);
}

// Run the update function
updatePriceList();
