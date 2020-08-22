/* eslint-env browser */

import $ from 'jquery';
import athorrent from './athorrent';
import '../css/users.scss';

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

function onResetUserPassword(event) {
    var target = event.target;

    if (window.confirm('Êtes-vous sur de vouloir réinitialiser le mot de passe de l\'utilisateur ' + getUserName(target) + '?')) {
        athorrent.ajax.resetUserPassword({
            userId: getUserId(target)
        }, function (data) {
            athorrent.showModal('Nouveau mot de passe', data.password);
        }, 'json');
    }
}

$(document).on('click', '.user-reset-password', onResetUserPassword);
$(document).on('click', '.user-remove', onRemoveUser);
