import {DEFAULT_USERNAME, getLogoutButton} from "../support/commands";
import {createAltUser} from "../support/utils";

describe('user-management', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
    });

    it('should list users', () => {
        cy.visit('/administration/users/');

        cy.get('#user-1 > .user-name').should('have.text', DEFAULT_USERNAME);
    });

    it('should create a user', () => {
        createAltUser();
    });

    // @TODO need to find a way to make it work in test env
    /*
    it('should switch between users', () => {
        createAltUser();

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
            cy.login(DEFAULT_USERNAME, password);
        })
    });

    it('should remove a user', () => {
        createAltUser();

        cy.visit('/administration/users/');
        cy.get('.user-remove').click();

        cy.get('#user-2 > .user-name').should('not.exist');
    });
});
