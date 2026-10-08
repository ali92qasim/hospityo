/**
 * Pure row-action HTML builders for pharmacy catalog DataTables.
 * Flags come from Blade data-can-edit / data-can-delete ("1" = allowed).
 */

function flagAllowed(flag) {
    return flag === '1';
}

export function renderMedicineRowActions(id, canEdit, canDelete, csrf = '') {
    const editAllowed = flagAllowed(canEdit);
    const deleteAllowed = flagAllowed(canDelete);

    if (!editAllowed && !deleteAllowed) {
        return '';
    }

    const editLink = editAllowed ? `
                            <a href="/medicines/${id}/edit" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>` : '';
    const deleteForm = deleteAllowed ? `
                            <form method="POST" action="/medicines/${id}" data-confirm="Are you sure?" data-confirm-variant="danger" data-confirm-text="Delete">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>` : '';

    return `
                        <div class="flex items-center space-x-3">${editLink}${deleteForm}
                        </div>
                    `;
}

export function renderUnitRowActions(id, canEdit, canDelete, csrf = '') {
    const editAllowed = flagAllowed(canEdit);
    const deleteAllowed = flagAllowed(canDelete);

    if (!editAllowed && !deleteAllowed) {
        return '';
    }

    const editLink = editAllowed ? `
                            <a href="/units/${id}/edit" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>` : '';
    const deleteForm = deleteAllowed ? `
                            <form method="POST" action="/units/${id}" data-confirm="Delete this unit?" data-confirm-detail="This action cannot be undone if the unit is not linked elsewhere." data-confirm-variant="danger" data-confirm-text="Delete">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>` : '';

    return `
                        <div class="flex items-center space-x-3">${editLink}${deleteForm}
                        </div>
                    `;
}
