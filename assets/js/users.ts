import '../css/users.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {noParallelRun} from './core/utils';

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

    onRemoveUser = noParallelRun(async (event: MouseEvent) =>  {
        const target = event.target as HTMLElement;

        await this.confirm(
            'users.deletionConfirmation',
            { user: this.getUserName(target) },
            async () => {
                await this.sendRequest('removeUser', {
                    userId: this.getUserId(target)
                });

                this.getItem('user', target).remove();
            },
        );
    })

    onResetUserPassword = noParallelRun(async (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        let password: string | undefined;

        const confirmed = await this.confirm(
            'users.passwordResetConfirmation',
            { user: this.getUserName(target) },
            async () => {
                const data = await this.sendRequest<{password: string}>('resetUserPassword', {
                    userId: this.getUserId(target)
                });

                password = data.password;
            },
        );

        if (confirmed && password) {
            this.ui.showCopyableModal({
                title: 'users.newPasswordModalTitle',
                value: password,
                id: 'dialog-new-password',
            });
        }
    })
}

Application.create().run(UsersPage);
