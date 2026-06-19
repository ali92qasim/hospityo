/**
 * PAC Form — Handles toggle of decision forms on the PAC show page.
 */

document.addEventListener('DOMContentLoaded', function () {
    const buttons = [
        { btn: 'btn-clear', form: 'form-clear' },
        { btn: 'btn-decline', form: 'form-decline' },
        { btn: 'btn-further', form: 'form-further' },
    ];

    buttons.forEach(function (item) {
        const btn = document.getElementById(item.btn);
        const form = document.getElementById(item.form);
        if (!btn || !form) return;

        btn.addEventListener('click', function () {
            // Hide all other forms first
            buttons.forEach(function (other) {
                if (other.form !== item.form) {
                    const otherForm = document.getElementById(other.form);
                    if (otherForm) otherForm.classList.add('hidden');
                }
            });
            // Toggle this form
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
});
