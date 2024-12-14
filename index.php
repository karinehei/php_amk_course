<!-- Data preparation -->
<?php
require_once __DIR__ . '/src/logic/data_preparation.php';
require_once __DIR__ . '/src/logic/update_price_list.php';
require_once __DIR__ . '/src/logic/update_check.php';
checkAndUpdatePriceList();
?>

<!-- Header -->
<?php require_once __DIR__ . '/src/partials/header.php'; ?>

<div class="container">
    <!-- Title -->
    <h1 class="text-center mb-4">Alko Product Catalog <?php echo date("d.m.Y"); ?></h1>
    <!-- Update Feedback -->
    <div id="update-feedback">
        <?php
        if (file_exists('/tmp/last_update.txt')) {
            $lastUpdate = date("d.m.Y H:i:s", filemtime('/tmp/last_update.txt'));
            echo "Last updated on: $lastUpdate";
        } else {
            echo "Data is being updated...";
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
