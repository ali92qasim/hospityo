import $ from 'jquery';
import select2 from 'select2';

select2(window, $);

const SKIP_SELECTORS = '#allergies-select, .medicine-select, .instruction-select';

function isSkipped($select) {
    return $select.is(SKIP_SELECTORS);
}

function placeholderFor($select) {
    const blankOption = $select.find('option[value=""]').first().text().trim();

    if (blankOption) {
        return blankOption;
    }

    if ($select.hasClass('priority-select') || $select.attr('name')?.includes('priority')) {
        return 'Select priority';
    }

    if ($select.attr('name') === 'doctor_id' || $select.attr('name')?.includes('doctor_id')) {
        return 'Search doctor...';
    }

    if (
        $select.hasClass('investigation-item-select')
        || $select.attr('name')?.includes('lab_test_id')
        || $select.attr('name')?.includes('imaging_study_id')
        || $select.attr('name')?.includes('investigation')
    ) {
        return 'Search...';
    }

    if ($select.attr('name')?.includes('payment') || $select.attr('name')?.includes('refund')) {
        return 'Select method';
    }

    return 'Select...';
}

function initSingleSelect($select) {
    if (isSkipped($select) || ($select[0] && $select[0].closest('template'))) {
        return;
    }

    if ($select.hasClass('select2-hidden-accessible')) {
        try {
            $select.select2('destroy');
        } catch (error) {
            // Select2 was already torn down.
        }
    }

    const isPriority = $select.hasClass('priority-select')
        || ($select.attr('name')?.includes('priority') && ! $select.attr('name')?.includes('priority_level'));
    const isInvestigation = $select.hasClass('investigation-item-select');

    $select.select2({
        placeholder: placeholderFor($select),
        allowClear: isInvestigation || ! $select.prop('required'),
        width: '100%',
        minimumResultsForSearch: isPriority ? Infinity : 0,
    });
}

export function initVisitWorkflowSelect2(root = document) {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }

    const $root = root instanceof $ ? root : $(root);
    const $selects = $root.is('select') ? $root : $root.find('select');

    $selects.each(function () {
        initSingleSelect($(this));
    });
}

function cloneInvestigationTestRow(kind) {
    const tbody = document.getElementById(`${kind}-test-rows`);
    const template = document.getElementById(`${kind}-test-row-template`);

    if (!tbody || !template?.content) {
        return null;
    }

    const nextIndex = tbody.querySelectorAll('.test-row').length;
    const clone = template.content.cloneNode(true);
    const newRow = clone.querySelector('tr');

    if (!newRow) {
        return null;
    }

    newRow.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replaceAll('__INDEX__', String(nextIndex));
    });

    const itemSelect = newRow.querySelector('.investigation-item-select');
    if (itemSelect) {
        itemSelect.value = '';
        itemSelect.removeAttribute('data-select2-id');
        itemSelect.querySelectorAll('[data-select2-id]').forEach((el) => {
            el.removeAttribute('data-select2-id');
        });
    }

    tbody.appendChild(newRow);

    return newRow;
}

export function addVisitWorkflowTestRow(kind = 'lab') {
    const newRow = cloneInvestigationTestRow(kind);

    if (!newRow) {
        return;
    }

    initVisitWorkflowSelect2(newRow);

    if (typeof window.updateRemoveButtons === 'function') {
        window.updateRemoveButtons(kind);
    }

    if (typeof window.updateTestCount === 'function') {
        window.updateTestCount(kind);
    }
}

window.initVisitWorkflowSelect2 = initVisitWorkflowSelect2;
window.addVisitWorkflowTestRow = addVisitWorkflowTestRow;

$(function () {
    const workflowRoot = document.getElementById('visit-workflow');

    if (workflowRoot) {
        initVisitWorkflowSelect2(workflowRoot);
    }
});
