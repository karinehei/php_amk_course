<?php
/**
 * Table Rows Partial
 * Expects `$data` to be defined as an array of rows.
 */

if (!isset($data)) {
    throw new Exception('The $data variable is not defined for the table rows.');
}
?>
 <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>Number</th>
                <th>Name</th>
                <th>Manufacturer</th>
                <th>Bottle Size</th>
                <th>Price</th>
                <th>Liter Price</th>
                <th>Type</th>
                <th>Country of Manufacture</th>
                <th>Vintage</th>
                <th>Alcohol %</th>
                <th>Energy (kcal/100ml)</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($data): ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['manufacturer'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['bottle_size'] ?? ''); ?></td>
                        <td>€<?php echo htmlspecialchars($row['price'] ?? ''); ?></td>
                        <td>€<?php echo htmlspecialchars($row['liter_price'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['type'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['country_of_manufacture'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['vintage'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['alcohol_percentage'] ?? ''); ?>%</td>
                        <td><?php echo htmlspecialchars($row['energy_kcal_per_100ml'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="11" class="text-center">No results found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

