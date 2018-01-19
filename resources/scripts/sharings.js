/* eslint-env browser */

import $ from 'jquery';
import athorrent from 'athorrent';

function getSharingToken(element, selector) {
    return athorrent.getItemId('sharing', element, selector);
}

function onSharingRemove(event) {
    athorrent.ajax.removeSharing({
        token: getSharingToken(event.target)
    }, () => {
        athorrent.getItem('sharing', event.target).remove();
    });
}

$(document).on('click', '.sharing-remove', onSharingRemove);
