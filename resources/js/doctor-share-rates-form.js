document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('doctor-share-rates-form');

    if (!form) {
        return;
    }

    const doctorSelect = document.getElementById('doctor-share-doctor-select');
    const addButton = document.getElementById('doctor-share-add-row');
    const rows = document.getElementById('doctor-share-rate-rows');
    const template = document.getElementById('doctor-share-rate-row-template');
    const emptyRow = rows.querySelector('[data-empty-row]');
    const addedIds = new Set(JSON.parse(form.dataset.addedIds || '[]').map(String));

    function syncRows() {
        const rateRows = [...rows.querySelectorAll('[data-rate-row]')];

        rateRows.forEach((row, index) => {
            row.querySelector('[data-doctor-id]').name = `doctors[${index}][doctor_id]`;
            row.querySelectorAll('[data-rate-input]').forEach((input) => {
                input.name = `doctors[${index}][${input.dataset.category}]`;
            });
        });

        emptyRow.classList.toggle('hidden', rateRows.length > 0);
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

        const row = removeButton.closest('[data-rate-row]');
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
