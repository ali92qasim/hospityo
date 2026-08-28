/**
 * IPD visit workflow helpers (tab restore + visit note edit toggles).
 */
(function () {
    const TAB_STORAGE_KEY = 'visitWorkflowActiveTab';

    function resolveWorkflowTabName(tabName) {
        if (tabName === 'tests') {
            return 'lab';
        }
        if (tabName === 'gpe-tab') {
            return 'gpe';
        }
        return tabName;
    }

    window.switchVisitWorkflowTab = function (tabName) {
        if (!tabName) {
            return;
        }

        tabName = resolveWorkflowTabName(tabName);

        const accordionIds = new Set(
            [...document.querySelectorAll('[data-workflow-section]')].map((el) => el.dataset.workflowSection)
        );
        const isAccordionTab = accordionIds.has(tabName);

        document.querySelectorAll('.tab-content, .workflow-panel').forEach((el) => {
            el.classList.add('hidden');
        });

        document.querySelectorAll('.tab-button, .workflow-action-button, [data-workflow-panel]').forEach((button) => {
            button.classList.remove('border-medical-blue', 'text-medical-blue', 'bg-medical-light', 'bg-purple-50');
            button.classList.add('border-transparent');
            button.removeAttribute('aria-current');
            button.setAttribute('aria-selected', 'false');
            if (button.classList.contains('tab-button')) {
                button.classList.add('text-gray-500');
            }
        });

        const layout = document.getElementById('visit-workflow');
        const accordionRoot = document.querySelector('[data-workflow-accordion-root]');
        if (layout?.dataset.workflowLayout === 'ipd' && accordionRoot) {
            accordionRoot.classList.toggle('hidden', !isAccordionTab);
        }

        if (typeof window.openWorkflowAccordionSection === 'function') {
            window.openWorkflowAccordionSection(isAccordionTab ? tabName : '', { scroll: isAccordionTab });
        }

        if (!isAccordionTab) {
            const content = document.getElementById(`${tabName}-content`);
            if (content) {
                content.classList.remove('hidden');
                content.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        const activeTabButton = document.getElementById(`${tabName}-tab`);
        if (activeTabButton) {
            activeTabButton.classList.remove('border-transparent', 'text-gray-500');
            activeTabButton.classList.add('border-medical-blue', 'text-medical-blue');
            activeTabButton.setAttribute('aria-current', 'page');
            activeTabButton.setAttribute('aria-selected', 'true');
        }

        document.querySelectorAll(`[data-workflow-panel="${tabName}"]`).forEach((button) => {
            button.classList.add('bg-purple-50', 'text-medical-blue');
            button.setAttribute('aria-current', 'page');
            button.setAttribute('aria-selected', 'true');
        });
    };

    window.saveVisitWorkflowTab = function (tabName) {
        try {
            sessionStorage.setItem(TAB_STORAGE_KEY, resolveWorkflowTabName(tabName));
        } catch (error) {
            // Ignore storage errors in restricted browsers.
        }
    };

    window.restoreVisitWorkflowTab = function (fallbackTab) {
        let tabToShow = resolveWorkflowTabName(fallbackTab);

        try {
            const savedTab = resolveWorkflowTabName(sessionStorage.getItem(TAB_STORAGE_KEY));
            if (savedTab && (document.getElementById(`${savedTab}-content`) || document.querySelector(`[data-workflow-section="${savedTab}"]`))) {
                tabToShow = savedTab;
            }
            sessionStorage.removeItem(TAB_STORAGE_KEY);
        } catch (error) {
            // Ignore storage errors in restricted browsers.
        }

        window.switchVisitWorkflowTab(tabToShow);
    };

    window.openCareTeamTab = function () {
        window.switchVisitWorkflowTab('care-team');
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
