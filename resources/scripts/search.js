/* eslint-env browser */

import $ from 'jquery';
import athorrent from 'athorrent';

$('.nav-tabs a').click(function (event) {
    event.preventDefault();
    $(this).tab('show');
});
