<?php
/**
 * Pagination Partial
 * Expects `$page`, `$totalPages`, and `$filters` to be defined.
 */

if (!isset($page, $totalPages, $filters)) {
    throw new Exception('Pagination variables ($page, $totalPages, $filters) are not set.');
}
?>

<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <!-- First Page -->
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=1&<?php echo http_build_query($filters); ?>" aria-label="First">
                First
            </a>
        </li>

        <!-- Previous Page -->
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" aria-label="Previous">
                Previous
            </a>
        </li>

        <!-- Page Numbers -->
        <?php
        $visiblePages = 5; // Number of page links to show
        $startPage = max(1, $page - floor($visiblePages / 2));
        $endPage = min($totalPages, $startPage + $visiblePages - 1);
        $startPage = max(1, $endPage - $visiblePages + 1);

        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Next Page -->
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" aria-label="Next">
                Next
            </a>
        </li>

        <!-- Last Page -->
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $totalPages; ?>&<?php echo http_build_query($filters); ?>" aria-label="Last">
                Last
            </a>
        </li>
    </ul>
</nav>


