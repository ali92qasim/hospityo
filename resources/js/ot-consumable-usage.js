/**
 * OT Consumable Usage — Dynamic row management for recording per-surgery consumption.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('usage-form');
    const rowsContainer = document.getElementById('usage-rows');
    const addBtn = document.getElementById('add-usage-row');
    const optionsTemplate = document.getElementById('consumable-options-template');

    if (!form || !rowsContainer || !addBtn || !optionsTemplate) return;

    var rowIndex = 0;

    function addRow() {
        var options = optionsTemplate.innerHTML;
        var row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-3 items-start usage-row';
        row.innerHTML =
            '<div class="col-span-5">' +
            '<select name="items[' + rowIndex + '][consumable_id]" required class="w-full border-gray-300 rounded-lg text-sm consumable-select">' +
            options + '</select></div>' +
            '<div class="col-span-2">' +
            '<input type="number" name="items[' + rowIndex + '][quantity]" value="1" min="1" required ' +
            'class="w-full border-gray-300 rounded-lg text-sm" placeholder="Qty"></div>' +
            '<div class="col-span-3 serial-field hidden">' +
            '<input type="text" name="items[' + rowIndex + '][serial_number]" ' +
            'class="w-full border-gray-300 rounded-lg text-sm" placeholder="Serial #"></div>' +
            '<div class="col-span-3 notes-field">' +
            '<input type="text" name="items[' + rowIndex + '][notes]" ' +
            'class="w-full border-gray-300 rounded-lg text-sm" placeholder="Notes (optional)"></div>' +
            '<div class="col-span-2 flex items-center gap-1">' +
            '<button type="button" class="remove-row text-red-400 hover:text-red-600 text-sm p-1"><i class="fas fa-times"></i></button></div>';

        rowsContainer.appendChild(row);
        rowIndex++;

        // Bind select change to show/hide serial field
        var select = row.querySelector('.consumable-select');
        var serialField = row.querySelector('.serial-field');
        var notesField = row.querySelector('.notes-field');
        select.addEventListener('change', function () {
            var selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.serial === '1') {
                serialField.classList.remove('hidden');
                notesField.classList.add('hidden');
            } else {
                serialField.classList.add('hidden');
                notesField.classList.remove('hidden');
            }
        });
    }

    addBtn.addEventListener('click', addRow);

    // Remove row
    rowsContainer.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-row');
        if (btn) {
            btn.closest('.usage-row').remove();
        }
    });

    // Add one row by default
    addRow();
});
