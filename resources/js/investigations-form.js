import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import '../css/investigations-form.css';

$(function () {
    // Investigation definition pages (create/edit test)
    $('#category-select').select2({ placeholder: 'Search category...', allowClear: true, width: '100%' });
    $('#sample-type-select').select2({ placeholder: 'Search sample type...', allowClear: true, width: '100%' });

    // Lab order create page
    $('#patient_id').select2({ placeholder: 'Select Patient', allowClear: true, width: '100%' });
    $('#doctor_id').select2({ placeholder: 'Select Doctor', allowClear: true, width: '100%' });
    $('#investigation_id').select2({ placeholder: 'Search investigation...', allowClear: true, width: '100%' });
    $('#priority_id').select2({ placeholder: 'Select Priority', allowClear: true, width: '100%', minimumResultsForSearch: Infinity });
});
