import {DEFAULT_PASSWORD, DEFAULT_USERNAME, getLogoutButton} from "../support/commands";
import {pathWithLocale} from "../support/utils";

describe('homepage', () => {

    it('should be accessible', () => {
        cy.visit(pathWithLocale('/'));

        cy.get('h1').should('contain', 'Athorrent');
    });

    it('should allow login', () => {
        cy.login();
        cy.visit(pathWithLocale('/user/files/'));
        getLogoutButton().should('exist');
    });

    it('should redirect back to the restricted page after login', () => {
        Cypress.session.clearAllSavedSessions();
        cy.clearCookies();

        const restrictedPath = pathWithLocale('/search/');

        cy.visit(restrictedPath);
        cy.location('pathname').should('eq', pathWithLocale('/login'));

        cy.get('form input[name=_username]').clear().type(DEFAULT_USERNAME);
        cy.get('form input[name=_password]').clear().type(DEFAULT_PASSWORD);
        cy.get('form button').click();

        cy.location('pathname').should('eq', restrictedPath);
    });

    it('should allow logout', () => {
        cy.login();

        cy.logout();
        getLogoutButton().should('not.exist');
    });
});
