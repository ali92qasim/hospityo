/**
 * IPD admission bed selection and ward filter (visit workflow).
 */
document.addEventListener('DOMContentLoaded', () => {
    const bedCards = document.querySelectorAll('.bed-card');
    const selectedBedId = document.getElementById('selected-bed-id');
    const selectedBedInfo = document.getElementById('selected-bed-info');
    const selectedBedDetails = document.getElementById('selected-bed-details');
    const admitBtn = document.getElementById('admit-btn');
    const wardFilter = document.getElementById('ward-filter');

    if (! bedCards.length || ! selectedBedId || ! admitBtn) {
        return;
    }

    bedCards.forEach((card) => {
        card.addEventListener('click', function () {
            bedCards.forEach((entry) => {
                entry.classList.remove('border-medical-blue', 'bg-medical-light');
                entry.classList.add('border-gray-200');
            });

            this.classList.remove('border-gray-200');
            this.classList.add('border-medical-blue', 'bg-medical-light');

            const bedNumber = this.querySelector('.font-medium')?.textContent ?? '';
            const wardName = this.dataset.ward ?? '';
            const bedType = this.querySelector('.text-medical-blue')?.textContent ?? '';
            const dailyRate = this.querySelector('.text-gray-600')?.textContent ?? '';

            selectedBedId.value = this.dataset.bedId ?? '';
            if (selectedBedDetails) {
                selectedBedDetails.textContent = `${bedNumber} - ${wardName} (${bedType}) - ${dailyRate}`;
            }
            selectedBedInfo?.classList.remove('hidden');
            admitBtn.disabled = false;
        });
    });

    if (wardFilter) {
        wardFilter.addEventListener('change', function () {
            const selectedWard = this.value;

            bedCards.forEach((card) => {
                card.style.display = selectedWard === '' || card.dataset.ward === selectedWard
                    ? 'block'
                    : 'none';
            });
        });
    }
});
