import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import '../css/investigations-form.css';

$(function () {
    // Investigation definition pages (create/edit test)
    $('#category-select').select2({ placeholder: 'Search category...', allowClear: true, width: '100%' });
    $('#sample-type-select').select2({ placeholder: 'Search sample type...', allowClear: true, width: '100%' });
});
