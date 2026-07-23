import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import '../css/doctor-share-rules-form.css';

$(function () {
    const $serviceSelect = $('#service_ids');
    const $investigationSelect = $('#investigation_id');
    const $doctorSelect = $('#doctor_id');

    if ($doctorSelect.length && typeof $.fn.select2 === 'function') {
        $doctorSelect.select2({
            placeholder: 'All doctors (global default)',
            allowClear: true,
            width: '100%',
        });
    }

    if ($serviceSelect.length && typeof $.fn.select2 === 'function') {
        $serviceSelect.select2({
            placeholder: 'All',
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
        });
    }

    if ($investigationSelect.length && typeof $.fn.select2 === 'function') {
        $investigationSelect.select2({
            placeholder: 'All',
            allowClear: true,
            width: '100%',
        });
    }

    function setSelectDisabled($select, disabled) {
        $select.prop('disabled', disabled);
        $select.trigger('change.select2');
    }

    function syncScopeFields() {
        const hasServices = ($serviceSelect.val() || []).length > 0;
        const hasInvestigation = !!$investigationSelect.val();

        if (hasServices) {
            $investigationSelect.val(null).trigger('change');
            setSelectDisabled($investigationSelect, true);
        } else {
            setSelectDisabled($investigationSelect, false);
        }

        if (hasInvestigation) {
            $serviceSelect.val(null).trigger('change');
            setSelectDisabled($serviceSelect, true);
        } else {
            setSelectDisabled($serviceSelect, false);
        }
    }

    $serviceSelect.on('change', syncScopeFields);
    $investigationSelect.on('change', syncScopeFields);
    syncScopeFields();
});
