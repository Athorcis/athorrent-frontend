/*jslint browser: true, white: true */
/*global require */

require(['jquery', 'athorrent', 'base64_decode'], function (jQuery, athorrent, base64_decode) {
    'use strict';


    function getSharingToken(element, selector) {
        return athorrent.getItemId('sharing', element, selector);
    }

    function onSharingRemove(event) {
        athorrent.ajax.removeSharing({
            token: getSharingToken(event.target)
        }, function () {
            athorrent.getItem('sharing', event.target).remove();
        });
    }

    jQuery(document).on('click', '.sharing-remove', onSharingRemove);
});
