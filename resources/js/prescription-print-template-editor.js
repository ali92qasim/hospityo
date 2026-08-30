const root = document.querySelector('#prescription-print-editor');

if (root) {
    const paperWidth = Number(root.dataset.paperWidthMm);
    const paperHeight = Number(root.dataset.paperHeightMm);
    const fields = JSON.parse(root.dataset.fields || '{}');
    const rx = JSON.parse(root.dataset.rx || '{}');
    const scale = Math.min(2.5, 760 / paperWidth);
    let unit = 'mm';
    let selectedKey = null;
    let drag = null;

    root.innerHTML = `
        <div class="mb-4 flex items-center justify-end gap-2">
            <label for="prescription-editor-unit" class="text-sm font-medium text-gray-700">Display units</label>
            <select id="prescription-editor-unit" class="rounded-lg border-gray-300 text-sm">
                <option value="mm">Millimetres (mm)</option>
                <option value="in">Inches (in)</option>
            </select>
        </div>
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="overflow-auto rounded-lg bg-gray-100 p-4">
                <div data-editor-stage class="relative mx-auto overflow-hidden bg-white shadow-lg"></div>
            </div>
            <aside class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h4 class="font-semibold text-gray-800">Field settings</h4>
                <p data-empty-settings class="mt-2 text-sm text-gray-500">Select a field on the page to edit its typography and visibility.</p>
                <div data-field-settings class="mt-4 hidden space-y-4">
                    <div>
                        <p data-field-name class="font-medium text-gray-800"></p>
                        <p data-field-position class="mt-1 text-xs text-gray-500"></p>
                    </div>
                    <label class="block text-sm font-medium text-gray-700">
                        Font size
                        <input data-setting="font_size" type="number" min="1" step="0.5"
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Font weight
                        <select data-setting="font_weight" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="normal">Normal</option>
                            <option value="bold">Bold</option>
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Alignment
                        <select data-setting="align" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="left">Left</option>
                            <option value="center">Centre</option>
                            <option value="right">Right</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input data-setting="visible" type="checkbox" class="rounded border-gray-300 text-medical-blue">
                        Print this field
                    </label>
                </div>
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <h4 class="font-semibold text-gray-800">Prescription region</h4>
                    <p data-rx-position class="mt-1 text-xs text-gray-500"></p>
                    <p class="mt-2 text-xs text-gray-500">Drag the region to move it. Drag its bottom handle to change row height.</p>
                </div>
            </aside>
        </div>
    `;

    const stage = root.querySelector('[data-editor-stage]');
    const settings = root.querySelector('[data-field-settings]');
    const emptySettings = root.querySelector('[data-empty-settings]');
    const fieldName = root.querySelector('[data-field-name]');
    const fieldPosition = root.querySelector('[data-field-position]');
    const rxPosition = root.querySelector('[data-rx-position]');
    const unitSelect = root.querySelector('#prescription-editor-unit');
    const backgroundUrl = root.dataset.backgroundUrl;

    stage.style.width = `${paperWidth * scale}px`;
    stage.style.height = `${paperHeight * scale}px`;

    if (backgroundUrl) {
        const background = document.createElement('img');
        background.src = backgroundUrl;
        background.alt = '';
        background.draggable = false;
        background.className = 'pointer-events-none absolute inset-0 h-full w-full select-none object-fill';
        stage.append(background);
    }

    const input = (name) => document.querySelector(`[name="${name}"]`);
    const fieldInput = (key, setting) => input(`fields[${key}][${setting}]`);
    const clamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);
    const roundMm = (value) => Math.round(value * 100) / 100;
    const displayUnit = (value) => unit === 'in'
        ? `${(value / 25.4).toFixed(2)} in`
        : `${value.toFixed(2)} mm`;

    const updateFieldPositionLabel = () => {
        if (!selectedKey) {
            return;
        }

        const field = fields[selectedKey];
        fieldPosition.textContent = `X ${displayUnit(Number(field.x_mm))} · Y ${displayUnit(Number(field.y_mm))}`;
    };

    const updateRxLabel = () => {
        rxPosition.textContent = `Starts at ${displayUnit(Number(rx.start_y))} · row height ${displayUnit(Number(rx.row_height))}`;
    };

    const selectField = (key) => {
        selectedKey = key;
        const field = fields[key];

        stage.querySelectorAll('[data-field-key]').forEach((chip) => {
            chip.classList.toggle('ring-2', chip.dataset.fieldKey === key);
            chip.classList.toggle('ring-medical-blue', chip.dataset.fieldKey === key);
        });

        emptySettings.classList.add('hidden');
        settings.classList.remove('hidden');
        fieldName.textContent = field.label;
        settings.querySelector('[data-setting="font_size"]').value = field.font_size;
        settings.querySelector('[data-setting="font_weight"]').value = field.font_weight;
        settings.querySelector('[data-setting="align"]').value = field.align;
        settings.querySelector('[data-setting="visible"]').checked = Boolean(field.visible);
        updateFieldPositionLabel();
    };

    const applyChipStyles = (chip, field) => {
        chip.style.left = `${Number(field.x_mm) * scale}px`;
        chip.style.top = `${Number(field.y_mm) * scale}px`;
        chip.style.fontSize = `${Math.max(9, Number(field.font_size) * scale * 0.55)}px`;
        chip.style.fontWeight = field.font_weight;
        chip.style.textAlign = field.align;
        chip.style.opacity = field.visible ? '1' : '0.4';
    };

    Object.entries(fields).forEach(([key, field]) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.dataset.fieldKey = key;
        chip.textContent = field.label;
        chip.className = 'absolute z-20 max-w-48 cursor-grab touch-none select-none truncate rounded border border-blue-400 bg-blue-50/90 px-2 py-1 text-blue-900 shadow-sm active:cursor-grabbing';
        applyChipStyles(chip, field);

        chip.addEventListener('click', () => selectField(key));
        chip.addEventListener('pointerdown', (event) => {
            event.preventDefault();
            selectField(key);
            drag = {
                type: 'field',
                key,
                startX: event.clientX,
                startY: event.clientY,
                originX: Number(field.x_mm),
                originY: Number(field.y_mm),
            };
            chip.setPointerCapture(event.pointerId);
        });

        stage.append(chip);
    });

    const rxBox = document.createElement('div');
    rxBox.dataset.rxRegion = '';
    rxBox.className = 'absolute z-10 cursor-move touch-none select-none border-2 border-dashed border-emerald-600 bg-emerald-50/40 text-emerald-900';
    rxBox.style.left = `${15 * scale}px`;
    rxBox.style.width = `${(paperWidth - 30) * scale}px`;

    const rxLines = document.createElement('div');
    rxLines.className = 'pointer-events-none flex h-full flex-col overflow-hidden';
    rxBox.append(rxLines);

    const handle = document.createElement('button');
    handle.type = 'button';
    handle.title = 'Resize prescription row height';
    handle.setAttribute('aria-label', 'Resize prescription row height');
    handle.className = 'absolute -bottom-2 left-1/2 h-4 w-16 -translate-x-1/2 cursor-ns-resize rounded-full bg-emerald-600';
    rxBox.append(handle);
    stage.append(rxBox);

    const renderRx = () => {
        const regionHeight = Number(rx.row_height) * Number(rx.max_rows);
        rxBox.style.top = `${Number(rx.start_y) * scale}px`;
        rxBox.style.height = `${regionHeight * scale}px`;
        rxLines.replaceChildren();

        for (let row = 1; row <= Number(rx.max_rows); row += 1) {
            const line = document.createElement('div');
            line.textContent = `${row}. Sample medicine`;
            line.className = 'border-b border-emerald-300 px-2 text-left';
            line.style.height = `${Number(rx.row_height) * scale}px`;
            line.style.fontSize = `${Math.max(8, Number(rx.row_height) * scale * 0.45)}px`;
            rxLines.append(line);
        }

        updateRxLabel();
    };

    rxBox.addEventListener('pointerdown', (event) => {
        if (event.target === handle) {
            return;
        }

        event.preventDefault();
        drag = {
            type: 'rx-move',
            startY: event.clientY,
            originY: Number(rx.start_y),
        };
        rxBox.setPointerCapture(event.pointerId);
    });

    handle.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        event.stopPropagation();
        drag = {
            type: 'rx-resize',
            startY: event.clientY,
            originHeight: Number(rx.row_height),
        };
        handle.setPointerCapture(event.pointerId);
    });

    root.addEventListener('pointermove', (event) => {
        if (!drag) {
            return;
        }

        if (drag.type === 'field') {
            const field = fields[drag.key];
            field.x_mm = roundMm(clamp(drag.originX + ((event.clientX - drag.startX) / scale), 0, paperWidth));
            field.y_mm = roundMm(clamp(drag.originY + ((event.clientY - drag.startY) / scale), 0, paperHeight));
            fieldInput(drag.key, 'x_mm').value = field.x_mm;
            fieldInput(drag.key, 'y_mm').value = field.y_mm;
            applyChipStyles(stage.querySelector(`[data-field-key="${drag.key}"]`), field);
            updateFieldPositionLabel();
        }

        if (drag.type === 'rx-move') {
            const regionHeight = Number(rx.row_height) * Number(rx.max_rows);
            rx.start_y = roundMm(clamp(
                drag.originY + ((event.clientY - drag.startY) / scale),
                0,
                Math.max(0, paperHeight - regionHeight),
            ));
            input('rx_start_y').value = rx.start_y;
            renderRx();
        }

        if (drag.type === 'rx-resize') {
            const deltaPerRow = ((event.clientY - drag.startY) / scale) / Number(rx.max_rows);
            const maximum = Math.max(1, (paperHeight - Number(rx.start_y)) / Number(rx.max_rows));
            rx.row_height = roundMm(clamp(drag.originHeight + deltaPerRow, 1, maximum));
            input('rx_row_height').value = rx.row_height;
            renderRx();
        }
    });

    root.addEventListener('pointerup', () => {
        drag = null;
    });
    root.addEventListener('pointercancel', () => {
        drag = null;
    });

    settings.querySelectorAll('[data-setting]').forEach((control) => {
        control.addEventListener('change', () => {
            if (!selectedKey) {
                return;
            }

            const setting = control.dataset.setting;
            const field = fields[selectedKey];
            field[setting] = setting === 'visible'
                ? control.checked
                : (setting === 'font_size' ? Number(control.value) : control.value);
            fieldInput(selectedKey, setting).value = setting === 'visible'
                ? (field.visible ? '1' : '0')
                : field[setting];
            applyChipStyles(stage.querySelector(`[data-field-key="${selectedKey}"]`), field);
        });
    });

    unitSelect.addEventListener('change', () => {
        unit = unitSelect.value;
        updateFieldPositionLabel();
        updateRxLabel();
    });

    renderRx();
}
