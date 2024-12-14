<?php
require_once 'db_connection.php';

function fetchAlkoData(int $page = 1, array $filters = []): array|bool {
    $pdo = getDbConnection();
    if (!$pdo) {
        return false;
    }

    $itemsPerPage = 25;
    $offset = max(($page - 1) * $itemsPerPage, 0);

    $whereClauses = [];
    $params = [];

    if (!empty($filters['type'])) {
        $whereClauses[] = 'type = :type';
        $params[':type'] = $filters['type'];
    }
    if (!empty($filters['country'])) {
        $whereClauses[] = 'country_of_manufacture = :country';
        $params[':country'] = $filters['country'];
    }
    if (!empty($filters['bottle_size'])) {
        $whereClauses[] = 'bottle_size = :bottle_size';
        $params[':bottle_size'] = $filters['bottle_size'];
    }
    if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
        $whereClauses[] = 'price >= :price_min';
        $params[':price_min'] = $filters['price_min'];
    }
    if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
        $whereClauses[] = 'price <= :price_max';
        $params[':price_max'] = $filters['price_max'];
    }
    if (isset($filters['energy_min']) && is_numeric($filters['energy_min'])) {
        $whereClauses[] = 'energy_kcal_per_100ml >= :energy_min';
        $params[':energy_min'] = $filters['energy_min'];
    }
    if (isset($filters['energy_max']) && is_numeric($filters['energy_max'])) {
        $whereClauses[] = 'energy_kcal_per_100ml <= :energy_max';
        $params[':energy_max'] = $filters['energy_max'];
    }

    $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $sql = "SELECT * FROM alko_price_list $whereSQL LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Debugging: Log SQL query
    error_log("SQL Query: $sql");
    error_log("Parameters: " . print_r($params, true));

    try {
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Data fetch error: " . $e->getMessage());
        return false;
    }

    $countSql = "SELECT COUNT(*) FROM alko_price_list $whereSQL";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }

    try {
        $countStmt->execute();
        $totalRows = $countStmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Count fetch error: " . $e->getMessage());
        return false;
    }

    return [
        'data' => $data,
        'totalRows' => $totalRows,
    ];
}







