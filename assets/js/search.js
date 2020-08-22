/* eslint-env browser */

import $ from 'jquery';
import './athorrent';
import '../css/search.scss';

$('.nav-tabs a').click(function (event) {
    event.preventDefault();
    $(this).tab('show');
});
