import $ from 'jquery';
import select2 from 'select2';

select2(window, $);

import '../css/inventory-form.css';

$(function () {
    // Medicine dropdown — present on both stock-in and stock-out
    if ($('#medicine-select').length) {
        $('#medicine-select').select2({
            placeholder: 'Search medicine...',
            allowClear: true,
            width: '100%',
        });

        // Re-fire the native onchange handlers after Select2 changes
        $('#medicine-select').on('select2:select select2:clear', function () {
            if (typeof window.updateStock === 'function') window.updateStock();
            if (typeof window.updateUnits === 'function') window.updateUnits();
        });
    }

    // Unit dropdown — stock-in only
    if ($('#unit-select').length) {
        $('#unit-select').select2({
            placeholder: 'Search unit...',
            allowClear: true,
            width: '100%',
        });
    }

    // Supplier dropdown — stock-in only
    if ($('#supplier-select').length) {
        $('#supplier-select').select2({
            placeholder: 'Search supplier...',
            allowClear: true,
            width: '100%',
        });
    }
});
