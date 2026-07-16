/**
 * Public / admin lab report print helpers.
 * Auto-print when ?print=1 is present; wires Print button by id.
 */

document.addEventListener('DOMContentLoaded', function () {
    const printBtn = document.getElementById('lab-report-print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    const closeBtn = document.getElementById('lab-report-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            window.close();
        });
    }

    if (window.location.search.includes('print=1')) {
        window.print();
    }
});
