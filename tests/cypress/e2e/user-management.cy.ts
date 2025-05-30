import {getLogoutButton} from "../support/commands";

function createUser(username: string, password: string) {
    cy.visit('/administration/users/add');

    cy.get('#add_user_username').type(username);
    cy.get('#add_user_plainPassword').type(password);
    cy.get('#add_user_role').select(0);
    cy.get('#add_user_add').click();

    cy.get('#user-2 > .user-name').should('have.text', 'test');
}

describe('user-management', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
    });

    it('should list users', () => {
        cy.visit('/administration/users/');

        cy.get('#user-1 > .user-name').should('have.text', 'admin');
    });

    it('should create a user', () => {
        createUser('test', 'password');
    });

    // @TODO need to find a way to make it work in test env
    /*
    it('should switch between users', () => {
        createUser('test', 'password');

        cy.visit('/administration/users/');

        cy.get('.user-switch').click();
        cy.url().should('contain', '/user/files');
        getLogoutButton().click();
    });
    */

    it('should reset a user\'s password', () => {
        cy.visit('/administration/users/');

        cy.get('.user-reset-password').click();
        cy.get('.modal-body').then($modal => {
            const password = $modal.text();
            cy.login('admin', password);
        })
    });

    it('should remove a user', () => {
        createUser('test', 'password');

        cy.visit('/administration/users/');
        cy.get('.user-remove').click();

        cy.get('#user-2 > .user-name').should('not.exist');
    });
});
