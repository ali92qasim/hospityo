<script>
let activeTab = '{{ $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] }}';
let itemIndex = 1;
let testRowIndex = 1;

function showTab(tabName) {
    if (typeof window.switchVisitWorkflowTab === 'function') {
        window.switchVisitWorkflowTab(tabName);
        activeTab = tabName;
        return;
    }

    activeTab = tabName;

    document.querySelectorAll('.tab-content, .workflow-panel').forEach(content => {
        content.classList.add('hidden');
    });

    document.querySelectorAll('.tab-button, .workflow-action-button, [data-workflow-panel]').forEach(button => {
        button.classList.remove('border-medical-blue', 'text-medical-blue', 'bg-medical-light', 'bg-purple-50');
        button.classList.add('border-transparent', 'text-gray-500');
        button.removeAttribute('aria-current');
        button.setAttribute('aria-selected', 'false');
    });

    const content = document.getElementById(tabName + '-content');
    if (content) {
        content.classList.remove('hidden');
        content.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const activeTabButton = document.getElementById(tabName + '-tab');
    if (activeTabButton) {
        activeTabButton.classList.remove('border-transparent', 'text-gray-500');
        activeTabButton.classList.add('border-medical-blue', 'text-medical-blue');
        activeTabButton.setAttribute('aria-current', 'page');
        activeTabButton.setAttribute('aria-selected', 'true');
    }

    document.querySelectorAll(`[data-workflow-panel="${tabName}"]`).forEach(button => {
        button.classList.remove('border-transparent', 'text-gray-500');
        button.classList.add('border-medical-blue', 'text-medical-blue', 'bg-purple-50');
        button.setAttribute('aria-current', 'page');
        button.setAttribute('aria-selected', 'true');
    });
}

function toggleAccordion(section) {
    const content = document.getElementById(section + '-content');
    const icon = document.getElementById(section + '-icon');

    if (!content || !icon) {
        return;
    }

    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function addItem() {
    if (typeof window.addPrescriptionItem === 'function') {
        window.addPrescriptionItem();
    }
}

function addTestRow(kind = 'lab') {
    if (typeof window.addVisitWorkflowTestRow === 'function') {
        window.addVisitWorkflowTestRow(kind);
        return;
    }

    const tbody = document.getElementById(`${kind}-test-rows`);
    const template = document.getElementById(`${kind}-test-row-template`);
    if (!tbody || !template?.content) {
        return;
    }

    const nextIndex = tbody.querySelectorAll('.test-row').length;
    const clone = template.content.cloneNode(true);
    const newRow = clone.querySelector('tr');
    if (!newRow) {
        return;
    }

    newRow.querySelectorAll('[name]').forEach((el) => {
        el.name = el.name.replaceAll('__INDEX__', String(nextIndex));
    });

    tbody.appendChild(newRow);
    const itemSelect = newRow.querySelector('.investigation-item-select');
    if (itemSelect) {
        itemSelect.value = '';
    }

    if (typeof window.initVisitWorkflowSelect2 === 'function') {
        window.initVisitWorkflowSelect2(newRow);
    }

    updateRemoveButtons(kind);
    updateTestCount(kind);
}

function removeTestRow(button, kind = 'lab') {
    const tbody = document.getElementById(`${kind}-test-rows`);
    const rows = tbody.querySelectorAll('.test-row');
    if (rows.length > 1) {
        const row = button.closest('.test-row');
        if (window.jQuery) {
            window.jQuery(row).find('select').each(function () {
                const $select = window.jQuery(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    try {
                        $select.select2('destroy');
                    } catch (error) {
                        // Ignore.
                    }
                }
            });
        }
        row.remove();
        updateRemoveButtons(kind);
        updateTestCount(kind);
    }
}

function updateRemoveButtons(kind = 'lab') {
    const tbody = document.getElementById(`${kind}-test-rows`);
    if (!tbody) {
        return;
    }
    const rows = tbody.querySelectorAll('.test-row');
    rows.forEach((row) => {
        const removeBtn = row.querySelector('button[onclick*="removeTestRow"]');
        if (removeBtn) {
            removeBtn.style.display = rows.length > 1 ? 'block' : 'none';
        }
    });
}

function updateTestCount(kind = 'lab') {
    const tbody = document.getElementById(`${kind}-test-rows`);
    const countElement = document.getElementById(`${kind}-test-count`);
    if (!tbody || !countElement) {
        return;
    }
    const count = tbody.querySelectorAll('.test-row').length;
    countElement.textContent = `${count} selected`;
}

function resetKindForm(kind = 'lab') {
    const form = document.getElementById(`${kind}-tests-form`);
    if (!form) {
        return;
    }

    form.reset();

    const tbody = document.getElementById(`${kind}-test-rows`);
    const rows = tbody.querySelectorAll('.test-row');
    for (let i = 1; i < rows.length; i++) {
        rows[i].remove();
    }

    const firstRow = tbody.querySelector('.test-row');
    const itemField = form.dataset.itemField || 'lab_test_id';
    firstRow.querySelector(`select[name*="${itemField}"]`).selectedIndex = 0;
    firstRow.querySelector('input[name*="quantity"]').value = 1;
    firstRow.querySelector('select[name*="priority"]').selectedIndex = 0;
    firstRow.querySelector('input[name*="clinical_notes"]').value = '';

    updateRemoveButtons(kind);
    updateTestCount(kind);
}

function resetForm() {
    resetKindForm('lab');
    resetKindForm('imaging');
}

function removeItem(button) {
    const items = document.querySelectorAll('.prescription-item');
    if (items.length > 1) {
        button.closest('.prescription-item').remove();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let defaultTab = '{{ $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] }}';

    if (typeof window.restoreVisitWorkflowTab === 'function') {
        window.restoreVisitWorkflowTab(defaultTab);
    } else if (document.querySelector('[data-workflow-accordion-root]') && typeof window.openWorkflowAccordionSection === 'function') {
        window.openWorkflowAccordionSection(defaultTab, { scroll: false });
    } else if (typeof showTab === 'function') {
        showTab(defaultTab);
    }

    updateTestCount();
});
</script>

@vite(['resources/css/visits-form.css', 'resources/css/visit-workflow-ipd.css', 'resources/js/workflow-accordion.js', 'resources/js/visits-form.js', 'resources/js/visit-workflow-ipd.js', 'resources/js/visit-workflow-select2.js', 'resources/js/visit-workflow-admission.js', 'resources/js/prescription-form.js'])
