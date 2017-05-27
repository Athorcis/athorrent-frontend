/* eslint-env amd */

require(['jquery', 'athorrent'], function ($) {
    'use strict';

    $('.nav-tabs a').click(function (event) {
        event.preventDefault();
        $(this).tab('show');
    });
});
