document.addEventListener("DOMContentLoaded", function () {
    const priceMin = document.getElementById('price_min');
    const priceMax = document.getElementById('price_max');
    const energyMin = document.getElementById('energy_min');
    const energyMax = document.getElementById('energy_max');

    function validateInput(minField, maxField) {
        minField.addEventListener('input', function () {
            if (parseFloat(minField.value) > parseFloat(maxField.value)) {
                maxField.setCustomValidity('Max value must be greater than or equal to Min value');
            } else {
                maxField.setCustomValidity('');
            }
        });

        maxField.addEventListener('input', function () {
            if (parseFloat(maxField.value) < parseFloat(minField.value)) {
                maxField.setCustomValidity('Max value must be greater than or equal to Min value');
            } else {
                maxField.setCustomValidity('');
            }
        });
    }

    validateInput(priceMin, priceMax);
    validateInput(energyMin, energyMax);
});
