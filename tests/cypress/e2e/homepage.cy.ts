import {getLogoutButton} from "../support/commands";
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

    it('should allow logout', () => {
        cy.login();

        cy.logout();
        getLogoutButton().should('not.exist');
    });
});
