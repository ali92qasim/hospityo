import {
    renderMedicineRowActions,
    renderUnitRowActions,
} from '../../resources/js/pharmacy-catalog-row-actions.js';

function assert(condition, message) {
    if (!condition) {
        console.error(message);
        process.exit(1);
    }
}

function assertContains(haystack, needle, message) {
    assert(haystack.includes(needle), `${message}: expected to contain ${JSON.stringify(needle)}`);
}

function assertNotContains(haystack, needle, message) {
    assert(!haystack.includes(needle), `${message}: expected not to contain ${JSON.stringify(needle)}`);
}

function assertEmpty(html, label) {
    assert(html === '', `${label}: expected empty string, got ${JSON.stringify(html)}`);
}

function assertHasEdit(html, resource, id, label) {
    assertContains(html, `href="/${resource}/${id}/edit"`, `${label}: edit href`);
    assertContains(html, 'title="Edit"', `${label}: edit title`);
}

function assertHasDelete(html, resource, id, label) {
    assertContains(html, `action="/${resource}/${id}"`, `${label}: delete action`);
    assertContains(html, 'title="Delete"', `${label}: delete title`);
    assertContains(html, 'name="_method" value="DELETE"', `${label}: delete method`);
}

function assertNoEdit(html, label) {
    assertNotContains(html, 'title="Edit"', `${label}: no edit`);
}

function assertNoDelete(html, label) {
    assertNotContains(html, 'title="Delete"', `${label}: no delete`);
}

const csrf = 'test-csrf-token';

const cases = [
    {
        label: 'both flags "1"',
        canEdit: '1',
        canDelete: '1',
        expectEdit: true,
        expectDelete: true,
    },
    {
        label: 'neither flag',
        canEdit: '0',
        canDelete: '0',
        expectEdit: false,
        expectDelete: false,
        expectEmpty: true,
    },
    {
        label: 'edit only',
        canEdit: '1',
        canDelete: '0',
        expectEdit: true,
        expectDelete: false,
    },
    {
        label: 'delete only',
        canEdit: '0',
        canDelete: '1',
        expectEdit: false,
        expectDelete: true,
    },
    {
        label: 'missing flags',
        canEdit: undefined,
        canDelete: undefined,
        expectEdit: false,
        expectDelete: false,
        expectEmpty: true,
    },
    {
        label: 'other flag values',
        canEdit: 'true',
        canDelete: 'yes',
        expectEdit: false,
        expectDelete: false,
        expectEmpty: true,
    },
    {
        label: 'empty string flags',
        canEdit: '',
        canDelete: '',
        expectEdit: false,
        expectDelete: false,
        expectEmpty: true,
    },
];

const renderers = [
    { name: 'medicines', resource: 'medicines', id: 42, render: renderMedicineRowActions },
    { name: 'units', resource: 'units', id: 7, render: renderUnitRowActions },
];

for (const { name, resource, id, render } of renderers) {
    for (const scenario of cases) {
        const label = `${name}: ${scenario.label}`;
        const html = render(id, scenario.canEdit, scenario.canDelete, csrf);

        if (scenario.expectEmpty) {
            assertEmpty(html, label);
            continue;
        }

        if (scenario.expectEdit) {
            assertHasEdit(html, resource, id, label);
        } else {
            assertNoEdit(html, label);
        }

        if (scenario.expectDelete) {
            assertHasDelete(html, resource, id, label);
            assertContains(html, csrf, `${label}: csrf token`);
        } else {
            assertNoDelete(html, label);
        }
    }
}

process.exit(0);
