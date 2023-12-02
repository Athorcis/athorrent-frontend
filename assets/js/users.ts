import '../css/users.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';

class UsersPage extends AbstractPage {

    init() {
        on(document, 'click', new Map([
            ['.user-reset-password', this.onResetUserPassword],
            ['.user-remove', this.onRemoveUser],
        ]))
    }

    getUserId(element: HTMLElement) {
        return this.getItemId('user', element);
    }

    getUserName(element: HTMLElement) {
        return this.getItemAttr('user', element, 'name');
    }

    onRemoveUser = async (event: MouseEvent) =>  {
        const target = event.target as HTMLElement;

        if (window.confirm(`Êtes-vous sur de vouloir supprimer l'utilisateur ${ this.getUserName(target) } ?`)) {
            await this.sendRequest('removeUser', {
                userId: this.getUserId(target)
            });

            this.getItem('user', target).remove();
        }
    }

    onResetUserPassword = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        if (window.confirm(`Êtes-vous sur de vouloir réinitialiser le mot de passe de l'utilisateur ${this.getUserName(target)}?`)) {
            const data = await this.sendRequest<{password: string}>('resetUserPassword', {
                userId: this.getUserId(target)
            });

            this.ui.showModal('Nouveau mot de passe', data.password);
        }
    }
}

Application.create().run(UsersPage);
