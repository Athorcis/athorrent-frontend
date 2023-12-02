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

        if (this.confirm('users.deletionConfirmation', { user: this.getUserName(target) })) {
            await this.sendRequest('removeUser', {
                userId: this.getUserId(target)
            });

            this.getItem('user', target).remove();
        }
    }

    onResetUserPassword = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        if (this.confirm('users.passwordResetConfirmation', { user: this.getUserName(target) })) {
            const data = await this.sendRequest<{password: string}>('resetUserPassword', {
                userId: this.getUserId(target)
            });

            this.ui.showModal('users.newPasswordModalTitle', data.password);
        }
    }
}

Application.create().run(UsersPage);
