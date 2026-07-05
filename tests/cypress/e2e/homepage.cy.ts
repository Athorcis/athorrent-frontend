import {getLogoutButton} from "../support/commands";

describe('homepage', () => {

    it('should be accessible', () => {
        cy.visit('/');

        cy.get('h1').should('contain', 'Athorrent');
    });

    it('should allow login', () => {
        cy.login();
        cy.visit('/user/files');
        getLogoutButton().should('exist');
    });

    it('should allow logout', () => {
        cy.login();

        cy.logout();
        getLogoutButton().should('not.exist');
    });

    it('localized pages should be accessible', () => {
        cy.visit('/en/');

        cy.login();

        cy.visit('/en/');
    });
});
