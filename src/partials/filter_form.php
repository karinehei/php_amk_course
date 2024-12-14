<?php
global $filters;
require_once __DIR__ . '/../logic/filter_logic.php';

// Ensure filters are initialized
$filters = $filters ?? [];

$priceRange = getPriceRange();
$energyRange = getEnergyRange();
$type = getTypeOptions();
$country = getCountryOptions();
$bottle_size = getBottleSizeOptions();
?>

<form method="get" class="row g-3 mb-4">
    <!-- Type Dropdown -->
    <div class="col-md-4">
        <label for="type" class="form-label">Type</label>
        <select class="form-control" id="type" name="type">
            <option value=""><?php echo "Select a Type"; ?></option>
            <?php echo generateDropdownOptions(getTypeOptions(), $filters['type'] ?? null); ?>
        </select>
    </div>

    <!-- Country Dropdown -->
    <div class="col-md-4">
        <label for="country" class="form-label">Country</label>
        <select class="form-control" id="country" name="country">
            <option value=""><?php echo "Select a Country"; ?></option>
            <?php echo generateDropdownOptions(getCountryOptions(), $filters['country'] ?? null); ?>
        </select>
    </div>

    <!-- Bottle Size Dropdown -->
    <div class="col-md-4">
        <label for="bottle_size" class="form-label">Bottle Size</label>
        <select class="form-control" id="bottle_size" name="bottle_size">
            <option value=""><?php echo "Select a Bottle Size"; ?></option>
            <?php echo generateBottleSizeOptions(getBottleSizeOptions(), $filters['bottle_size'] ?? null); ?>
        </select>
    </div>

    <!-- Price Range Manual Input -->
    <div class="col-sm-4">
        <label for="price_range" class="form-label">Price Range (€)</label>
        <div class="row g-2">
            <div class="col">
                <input type="number" id="price_min" name="price_min" class="form-control"
                       placeholder="Min Price"
                       value="<?php echo htmlspecialchars($filters['price_min'] ?? ''); ?>"
                       min="0"
                       max="<?php echo htmlspecialchars($priceRange['max_price']); ?>"
                       step="0.01">
            </div>
            <div class="col">
                <input type="number" id="price_max" name="price_max" class="form-control"
                       placeholder="Max Price"
                       value="<?php echo htmlspecialchars($filters['price_max'] ?? $priceRange['max_price']); ?>"
                       min="0"
                       max="<?php echo htmlspecialchars($priceRange['max_price']); ?>"
                       step="0.01">
            </div>
        </div>
    </div>

    <!-- Energy Range Manual Input -->
    <div class="col-sm-4">
        <label for="energy_range" class="form-label">Energy Range (kcal/100ml)</label>
        <div class="row g-2">
            <div class="col">
                <input type="number" id="energy_min" name="energy_min" class="form-control"
                       placeholder="Min Energy"
                       value="<?php echo htmlspecialchars($filters['energy_min'] ?? ''); ?>"
                       min="0"
                       max="<?php echo htmlspecialchars($energyRange['max_energy']); ?>"
                       step="0.1">
            </div>
            <div class="col">
                <input type="number" id="energy_max" name="energy_max" class="form-control"
                       placeholder="Max Energy"
                       value="<?php echo htmlspecialchars($filters['energy_max'] ?? $energyRange['max_energy']); ?>"
                       min="0"
                       max="<?php echo htmlspecialchars($energyRange['max_energy']); ?>"
                       step="0.1">
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</form>




