/**
 * Role create/edit — module-level select-all toggles for permission checkboxes.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-role-permissions-form]');

    if (!form) {
        return;
    }

    form.querySelectorAll('.role-module-select-all').forEach(function (toggle) {
        const module = toggle.dataset.module;

        if (!module) {
            return;
        }

        const checkboxes = form.querySelectorAll(
            '.role-permission-checkbox[data-module="' + module + '"]'
        );

        function syncToggleFromCheckboxes() {
            const total = checkboxes.length;
            const checkedCount = Array.from(checkboxes).filter(function (cb) {
                return cb.checked;
            }).length;

            toggle.checked = total > 0 && checkedCount === total;
            toggle.indeterminate = checkedCount > 0 && checkedCount < total;
        }

        syncToggleFromCheckboxes();

        toggle.addEventListener('change', function () {
            checkboxes.forEach(function (cb) {
                cb.checked = toggle.checked;
            });
            toggle.indeterminate = false;
        });

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', syncToggleFromCheckboxes);
        });
    });
});
