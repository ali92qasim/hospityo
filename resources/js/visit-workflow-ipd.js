document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ipd-consultant-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const form = document.getElementById(targetId);

            if (!form) {
                return;
            }

            form.classList.remove('hidden');
            button.classList.add('hidden');
        });
    });

    document.querySelectorAll('.ipd-consultant-cancel').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const form = document.getElementById(targetId);
            const card = form?.closest('.ipd-consultant-card');
            const toggle = card?.querySelector('.ipd-consultant-toggle');

            if (form) {
                form.classList.add('hidden');
            }

            if (toggle) {
                toggle.classList.remove('hidden');
            }
        });
    });
});
