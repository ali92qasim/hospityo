document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('lab-report-roster-form');

    if (!form) {
        return;
    }

    const doctorSelect = document.getElementById('lab-report-roster-doctor-select');
    const addButton = document.getElementById('lab-report-roster-add-row');
    const rows = document.getElementById('lab-report-roster-rows');
    const template = document.getElementById('lab-report-roster-row-template');
    const emptyRow = rows.querySelector('[data-empty-row]');
    const addedIds = new Set(JSON.parse(form.dataset.addedIds || '[]').map(String));

    function syncRows() {
        const rosterRows = [...rows.querySelectorAll('[data-roster-row]')];

        rosterRows.forEach((row, index) => {
            row.querySelector('[data-doctor-id]').name = `doctor_ids[${index}]`;
        });

        emptyRow.classList.toggle('hidden', rosterRows.length > 0);
        form.dataset.addedIds = JSON.stringify([...addedIds]);
    }

    function syncAddButton() {
        addButton.disabled = !doctorSelect.value;
    }

    addButton.addEventListener('click', () => {
        const option = doctorSelect.selectedOptions[0];
        const doctorId = option?.value;

        if (!doctorId || addedIds.has(doctorId)) {
            return;
        }

        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelector('[data-doctor-name]').textContent = option.textContent.trim();
        row.querySelector('[data-doctor-id]').value = doctorId;
        rows.insertBefore(row, emptyRow);

        addedIds.add(doctorId);
        option.disabled = true;
        doctorSelect.value = '';
        syncRows();
        syncAddButton();
    });

    doctorSelect.addEventListener('change', syncAddButton);

    rows.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-row]');

        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('[data-roster-row]');
        const doctorId = row.querySelector('[data-doctor-id]').value;
        const option = [...doctorSelect.options].find((candidate) => candidate.value === doctorId);

        addedIds.delete(doctorId);
        if (option) {
            option.disabled = false;
        }
        row.remove();
        syncRows();
        syncAddButton();
    });

    syncRows();
    syncAddButton();
});
