import {DEFAULT_PASSWORD, DEFAULT_USERNAME} from "../support/commands";
import {createAltUser, pathWithLocale, resetTestData} from "../support/utils";

describe('user-management', () => {
    beforeEach(() => {
        resetTestData();
        cy.login();
    });

    it('should list users', () => {
        cy.visit(pathWithLocale('/administration/users/'));

        cy.get('#user-1 > .user-name').should('have.text', DEFAULT_USERNAME);
    });

    it('should create a user', () => {
        createAltUser();
    });

    // @TODO need to find a way to make it work in test env
    /*
    it('should switch between users', () => {
        createAltUser();

        cy.visit(pathWithLocale('/administration/users/'));

        cy.get('.user-switch').click();
        cy.url().should('contain', pathWithLocale('/user/files/'));
        getLogoutButton().click();
    });
    */

    it('should reset a user\'s password', () => {
        cy.visit(pathWithLocale('/administration/users/'));

        cy.get('.user-reset-password').click();
        cy.confirmModal();

        cy.get('dialog:open .modal-body').should('be.visible').then($modal => {
            const password = $modal.text();

            assert.notEqual(password, DEFAULT_PASSWORD);

            cy.login(DEFAULT_USERNAME, password);
        });
    });

    it('should remove a user', () => {
        createAltUser();

        cy.visit(pathWithLocale('/administration/users/'));
        cy.get('#user-2 .user-remove').click();
        cy.confirmModal();

        cy.get('#user-2 > .user-name').should('not.exist');
    });
});
