/* eslint-env browser, amd */

require(['jquery', 'athorrent'], function ($, athorrent) {
    'use strict';

    function getUserId(element) {
        return athorrent.getItemId('user', element);
    }

    function getUserName(element) {
        return athorrent.getItemAttr('user', element, 'name');
    }

    function onRemoveUser(event) {
        var target = event.target;

        if (window.confirm('Êtes-vous sur de vouloir supprimer l\'utilisateur ' + getUserName(target) + '?')) {
            athorrent.ajax.removeUser({
                userId: getUserId(target)
            }, function () {
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
});
