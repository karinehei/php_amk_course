<?php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$filters = [
    'type' => $_GET['type'] ?? null,
    'country' => $_GET['country'] ?? null,
    'bottle_size' => $_GET['bottle_size'] ?? null,
    'price_min' => $_GET['price_min'] ?? null,
    'price_max' => $_GET['price_max'] ?? null,
    'energy_min' => $_GET['energy_min'] ?? null,
    'energy_max' => $_GET['energy_max'] ?? null,
];

