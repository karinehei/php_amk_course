<?php
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/data_fetch.php';

// Ensure $page and $filters are set correctly
$page = isset($page) ? (int)$page : 1;
$filters = isset($filters) ? (array)$filters : [];

// Fetch data and calculate pagination
$result = fetchAlkoData((int)$page, (array)$filters);
if ($result === false) {
    $data = [];
    $totalRows = 0;
} else {
    $data = $result['data'];
    $totalRows = $result['totalRows'];
}

$itemsPerPage = 25;
$totalPages = (int)ceil($totalRows / $itemsPerPage);

