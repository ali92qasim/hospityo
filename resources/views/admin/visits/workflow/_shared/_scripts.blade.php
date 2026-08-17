<script>
let activeTab = '{{ $workflowData['resolved_initial_tab'] ?? $workflowData['default_tab'] }}';
let itemIndex = 1;
let testRowIndex = 1;

function showTab(tabName) {
    if (typeof window.openWorkflowAccordionSection === 'function' && window.openWorkflowAccordionSection(tabName)) {
        activeTab = tabName;
        return;
    }

    activeTab = tabName;

    document.querySelectorAll('.tab-content, .workflow-panel').forEach(content => {
        content.classList.add('hidden');
    });

    document.querySelectorAll('.tab-button, .workflow-action-button').forEach(button => {
        button.classList.remove('border-medical-blue', 'text-medical-blue', 'bg-medical-light');
        button.classList.add('border-transparent', 'text-gray-500');
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
    }

    document.querySelectorAll(`[data-workflow-panel="${tabName}"]`).forEach(button => {
        button.classList.remove('border-transparent', 'text-gray-500');
        button.classList.add('border-medical-blue', 'text-medical-blue', 'bg-medical-light');
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
    const tbody = document.getElementById(`${kind}-test-rows`);
    if (!tbody) {
        return;
    }

    const form = document.getElementById(`${kind}-tests-form`);
    const itemField = form?.dataset.itemField || 'lab_test_id';
    const firstRow = tbody.querySelector('.test-row');
    const testSelect = firstRow.querySelector(`select[name*="${itemField}"]`);
    const testOptions = testSelect.innerHTML;
    const nextIndex = tbody.querySelectorAll('.test-row').length;

    const newRow = document.createElement('tr');
    newRow.className = 'test-row border-b border-gray-100 hover:bg-gray-25';
    newRow.innerHTML = `
        <td class="py-3 pr-4">
            <select name="tests[${nextIndex}][${itemField}]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                ${testOptions}
            </select>
        </td>
        <td class="py-3 px-3 text-center">
            <input type="number" name="tests[${nextIndex}][quantity]" value="1" min="1" max="10" class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
        </td>
        <td class="py-3 px-3">
            <select name="tests[${nextIndex}][priority]" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors priority-select" required>
                <option value="routine">Routine</option>
                <option value="urgent">Urgent</option>
                <option value="stat">STAT</option>
            </select>
        </td>
        <td class="py-3 px-3">
            <input type="text" name="tests[${nextIndex}][clinical_notes]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" placeholder="Optional notes...">
        </td>
        <td class="py-3 text-center">
            <button type="button" onclick="removeTestRow(this, '${kind}')" class="text-red-500 hover:text-red-700 p-1 rounded transition-colors" title="Remove test">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);

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
        button.closest('.test-row').remove();
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
