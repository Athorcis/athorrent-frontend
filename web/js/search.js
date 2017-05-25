
require(['jquery', 'athorrent'], function ($) {
    $('.nav-tabs a').click(function (event) {
        event.preventDefault();
        $(this).tab('show');
    });
});
