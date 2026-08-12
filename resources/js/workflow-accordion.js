function setSectionOpen(root, sectionId, open) {
    const section = root.querySelector(`[data-workflow-section="${sectionId}"]`);
    if (!section) {
        return false;
    }

    const panel = section.querySelector(`[data-workflow-section-panel="${sectionId}"]`);
    const toggle = section.querySelector(`[data-workflow-section-toggle="${sectionId}"]`);
    const chevron = section.querySelector('[data-workflow-section-chevron]');

    if (panel) {
        panel.classList.toggle('hidden', !open);
    }

    if (toggle) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (chevron) {
        chevron.classList.toggle('rotate-180', open);
    }

    section.dataset.open = open ? '1' : '0';

    return true;
}

export function openWorkflowAccordionSection(sectionId, { scroll = true } = {}) {
    const root = document.querySelector('[data-workflow-accordion-root]');
    if (!root) {
        return false;
    }

    root.querySelectorAll('[data-workflow-section]').forEach((section) => {
        const id = section.dataset.workflowSection;
        setSectionOpen(root, id, sectionId !== '' && id === sectionId);
    });

    if (!sectionId) {
        return true;
    }

    const section = root.querySelector(`[data-workflow-section="${sectionId}"]`);
    if (!section) {
        return false;
    }

    if (scroll) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    return true;
}

export function initWorkflowAccordion(root) {
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-workflow-section-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const sectionId = toggle.dataset.workflowSectionToggle;
            const section = root.querySelector(`[data-workflow-section="${sectionId}"]`);

            if (section?.dataset.sectionDisabled === '1') {
                return;
            }

            const isOpen = section?.dataset.open === '1';
            if (isOpen) {
                setSectionOpen(root, sectionId, false);
            } else {
                openWorkflowAccordionSection(sectionId, { scroll: false });
            }
        });
    });

    const initial = root.dataset.initialSection;
    if (initial) {
        openWorkflowAccordionSection(initial, { scroll: false });
    }
}

window.openWorkflowAccordionSection = openWorkflowAccordionSection;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-workflow-accordion-root]').forEach(initWorkflowAccordion);
});
