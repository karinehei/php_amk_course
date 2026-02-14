<?php
/**
 * Generate dropdown options for a given array of values.
 *
 * @param array $values Array of values for the dropdown.
 * @param string|null $selectedValue The value to mark as selected in the dropdown.
 * @return string The generated HTML string for the dropdown options.
 */
function generateDropdownOptions(array $values, ?string $selectedValue = null): string {
    $values = array_map('strtolower', $values); // Convert all values to lowercase for sorting
    sort($values, SORT_LOCALE_STRING); // Sort values alphabetically
    $values = array_unique($values); // Ensure unique values

    $html = '';
    foreach ($values as $value) {
        $selected = ($selectedValue === $value) ? 'selected' : '';
        $html .= "<option value=\"$value\" $selected>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "</option>";
    }
    return $html;
}

/**
 * Fetch distinct product types from the database for the 'Type' dropdown.
 *
 * @return array Array of product types.
 */
function getTypeOptions(): array {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    try {
        $sql = "SELECT DISTINCT type FROM alko_price_list ORDER BY type ASC";
        $stmt = $pdo->query($sql);
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $types ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching product types: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch distinct countries of manufacture from the database for the 'Country' dropdown.
 *
 * @return array Array of countries.
 */
function getCountryOptions(): array {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    try {
        $sql = "SELECT DISTINCT country_of_manufacture FROM alko_price_list ORDER BY country_of_manufacture ASC";
        $stmt = $pdo->query($sql);
        $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $countries ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching countries: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate dropdown options for bottle sizes, sorted numerically.
 *
 * @param array $sizes Array of bottle sizes.
 * @param string|null $selectedSize The bottle size to mark as selected.
 * @return string The generated HTML string for bottle size options.
 */
function generateBottleSizeOptions(array $sizes, ?string $selectedSize = null): string {
    usort($sizes, function ($a, $b) {
        $valueA = is_numeric($numA = filter_var($a, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)) ? $numA : PHP_INT_MAX;
        $valueB = is_numeric($numB = filter_var($b, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)) ? $numB : PHP_INT_MAX;
        return $valueA <=> $valueB;
    });

    $html = '';
    foreach ($sizes as $size) {
        $selected = ($selectedSize === $size) ? 'selected' : '';
        $html .= "<option value=\"$size\" $selected>" . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . "</option>";
    }
    return $html;
}

/**
 * Fetch distinct bottle sizes from the database for the 'Bottle Size' dropdown.
 *
 * @return array Array of bottle sizes.
 */
function getBottleSizeOptions(): array {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }
    try {
        $sql = "SELECT DISTINCT bottle_size FROM alko_price_list ORDER BY bottle_size ASC";
        $stmt = $pdo->query($sql);
        $bottleSizes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $bottleSizes ?: [];
    } catch (PDOException $e) {
        error_log("Error fetching bottle sizes: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch the minimum and maximum price range from the database.
 *
 * @return array Associative array containing 'min_price' and 'max_price'.
 */
function getPriceRange(): array {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['min_price' => 0, 'max_price' => 500];
    }
    try {
        $sql = "SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM alko_price_list";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_price' => 0, 'max_price' => 500];
    } catch (PDOException $e) {
        error_log("Error fetching price range: " . $e->getMessage());
        return ['min_price' => 0, 'max_price' => 500];
    }
}

/**
 * Fetch the minimum and maximum energy range from the database.
 *
 * @return array Associative array containing 'min_energy' and 'max_energy'.
 */
function getEnergyRange(): array {
    $pdo = getDbConnection();
    if (!$pdo) {
        return ['min_energy' => 0, 'max_energy' => 500];
    }
    try {
        $sql = "SELECT MIN(energy_kcal_per_100ml) AS min_energy, MAX(energy_kcal_per_100ml) AS max_energy FROM alko_price_list";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['min_energy' => 0, 'max_energy' => 500];
    } catch (PDOException $e) {
        error_log("Error fetching energy range: " . $e->getMessage());
        return ['min_energy' => 0, 'max_energy' => 500];
    }
}






