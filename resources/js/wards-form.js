import $ from 'jquery';
import select2 from 'select2';

select2(window, $);

import '../css/wards-form.css';

$(function () {
    $('#department_id').select2({
        placeholder: 'Search department...',
        allowClear: true,
        width: '100%',
    });
});
