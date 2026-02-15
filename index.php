<!-- Data preparation -->
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/src/logic/data_preparation.php';
require_once __DIR__ . '/src/logic/update_check.php';
checkAndUpdatePriceList();
?>

<!-- Header -->
<?php require_once __DIR__ . '/src/partials/header.php'; ?>

<div class="container">
    <!-- Title -->
    <h1 class="text-center mb-4">Alko Catalog <?php echo date("d.m.Y"); ?></h1>
    <!-- Update Feedback -->
    <div id="update-feedback">
        <?php
        $lastUpdateFile = sys_get_temp_dir() . '/last_update.txt';
        if (file_exists($lastUpdateFile)) {
            $lastUpdate = date("d.m.Y H:i:s", filemtime($lastUpdateFile));
            echo "Last updated: $lastUpdate";
        } else {
            echo "Update pending or download failed – upload manually to temp/alko_price_list_manual.xlsx if needed";
        }
        ?>
    </div>

    <!-- Filter Form -->
    <?php require_once __DIR__ . '/src/partials/filter_form.php'; ?>

    <!-- Data Table -->
    <?php require_once __DIR__ . '/src/partials/table_rows.php'; ?>

    <!-- Pagination -->
    <?php require_once __DIR__ . '/src/partials/pagination_logic.php'; ?>
</div>

<!-- JS Scripts -->
<?php require_once __DIR__ . '/src/partials/footer.php'; ?>
