/*jslint browser: true, white: true */
/*global require */

require(['jquery', 'athorrent'], function (jQuery, athorrent) {
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

    jQuery(document).on('click', '.user-remove', onRemoveUser);
});
