import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import '../css/doctor-share-rules-form.css';

$(function () {
    const $serviceSelect = $('#service_ids');
    const $investigationScope = $('#investigation_scope');
    const $doctorSelect = $('#doctor_id');
    const $serviceScopeLabel = $('#service-scope-label');

    if ($doctorSelect.length && typeof $.fn.select2 === 'function') {
        $doctorSelect.select2({
            placeholder: 'All doctors (global default)',
            allowClear: true,
            width: '100%',
        });
    }

    if ($serviceSelect.length && typeof $.fn.select2 === 'function') {
        $serviceSelect.select2({
            placeholder: 'Search and select specific services…',
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
        });
    }

    function setSelectDisabled($select, disabled) {
        $select.prop('disabled', disabled);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    }

    function updateServiceScopeLabel() {
        if (!$serviceScopeLabel.length) {
            return;
        }

        const selected = $serviceSelect.val() || [];

        if (selected.length === 0) {
            $serviceScopeLabel.text('All Services');
            return;
        }

        if (selected.length === 1) {
            const name = $serviceSelect.find(`option[value="${selected[0]}"]`).text().trim();
            $serviceScopeLabel.text(name || '1 selected service');
            return;
        }

        $serviceScopeLabel.text(`${selected.length} selected services`);
    }

    function syncScopeFields() {
        const hasSpecificServices = ($serviceSelect.val() || []).length > 0;

        setSelectDisabled($investigationScope, hasSpecificServices);
        updateServiceScopeLabel();
    }

    $serviceSelect.on('change', syncScopeFields);
    syncScopeFields();
});
