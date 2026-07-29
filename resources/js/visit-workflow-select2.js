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

    if ($select.attr('name')?.includes('lab_test_id') || $select.attr('name')?.includes('investigation')) {
        return 'Search investigation...';
    }

    if ($select.attr('name')?.includes('payment') || $select.attr('name')?.includes('refund')) {
        return 'Select method';
    }

    return 'Select...';
}

function initSingleSelect($select) {
    if ($select.hasClass('select2-hidden-accessible') || isSkipped($select)) {
        return;
    }

    const isPriority = $select.hasClass('priority-select')
        || ($select.attr('name')?.includes('priority') && ! $select.attr('name')?.includes('priority_level'));

    $select.select2({
        placeholder: placeholderFor($select),
        allowClear: ! $select.prop('required'),
        width: '100%',
        minimumResultsForSearch: isPriority ? Infinity : 0,
    });
}

export function initVisitWorkflowSelect2(root = document) {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }

    const $root = root instanceof $ ? root : $(root);

    $root.find('select').each(function () {
        initSingleSelect($(this));
    });
}

window.initVisitWorkflowSelect2 = initVisitWorkflowSelect2;

$(function () {
    const workflowRoot = document.getElementById('visit-workflow');

    if (workflowRoot) {
        initVisitWorkflowSelect2(workflowRoot);
    }
});
