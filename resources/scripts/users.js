/* eslint-env browser */

import $ from 'jquery';
import athorrent from 'athorrent';

function getUserId(element) {
    return athorrent.getItemId('user', element);
}

function getUserName(element) {
    return athorrent.getItemAttr('user', element, 'name');
}

function onRemoveUser(event) {
    let { target } = event;

    if (window.confirm(`Êtes-vous sur de vouloir supprimer l'utilisateur ${ getUserName(target) } ?`)) {
        athorrent.ajax.removeUser({
            userId: getUserId(target)
        }, () => {
            athorrent.getItem('user', target).remove();
        }, 'json');
    }
}

$(document).on('click', '.user-remove', onRemoveUser);
