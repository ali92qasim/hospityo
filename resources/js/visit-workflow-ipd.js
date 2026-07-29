/**
 * IPD visit workflow helpers (tab restore + visit note edit toggles).
 */
(function () {
    const TAB_STORAGE_KEY = 'visitWorkflowActiveTab';

    window.saveVisitWorkflowTab = function (tabName) {
        try {
            sessionStorage.setItem(TAB_STORAGE_KEY, tabName);
        } catch (error) {
            // Ignore storage errors in restricted browsers.
        }
    };

    window.restoreVisitWorkflowTab = function (fallbackTab) {
        let tabToShow = fallbackTab;

        try {
            const savedTab = sessionStorage.getItem(TAB_STORAGE_KEY);
            if (savedTab && document.getElementById(savedTab + '-content')) {
                tabToShow = savedTab;
            }
            sessionStorage.removeItem(TAB_STORAGE_KEY);
        } catch (error) {
            // Ignore storage errors in restricted browsers.
        }

        if (typeof window.showTab === 'function') {
            window.showTab(tabToShow);
        }
    };

    window.openCareTeamTab = function () {
        if (typeof window.showTab === 'function') {
            window.showTab('care-team');
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-save-tab]').forEach((form) => {
            form.addEventListener('submit', () => {
                const tabName = form.getAttribute('data-save-tab');
                if (tabName) {
                    window.saveVisitWorkflowTab(tabName);
                }
            });
        });

        document.querySelectorAll('.ipd-visit-note-toggle').forEach((button) => {
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

        document.querySelectorAll('.ipd-visit-note-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.dataset.target;
                const form = document.getElementById(targetId);
                const card = form?.closest('.ipd-visit-note-card');
                const toggle = card?.querySelector('.ipd-visit-note-toggle');

                if (form) {
                    form.classList.add('hidden');
                }

                if (toggle) {
                    toggle.classList.remove('hidden');
                }
            });
        });
    });
})();
