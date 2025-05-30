/// <reference types="cypress" />

import Chainable = Cypress.Chainable;

declare namespace Cypress {
    interface Chainable {
        elementExists(selectior: string): Chainable<null>;
        login(username: string, password: string): Chainable<null>;
        logout(): Chainable<null>;
    }
}

const DEFAULT_USERNAME = 'admin';
const DEFAULT_PASSWORD = 'test';

Cypress.Commands.add('elementExists', function (selector: string) {
    cy.get('body').then(($body) => {
        return $body.find(selector).length > 0;
    })
});

Cypress.Commands.add('login', function (username: string = DEFAULT_USERNAME, password: string = DEFAULT_PASSWORD) {

    cy.session([username, password], () => {
        cy.visit('/');

        cy.get('.navbar-form input[name=_username]').clear().type(username);
        cy.get('.navbar-form input[name=_password]').clear().type(password);
        cy.get('.navbar-form button').click();

        cy.url().should('contain', '/user/files');
    }, {
        validate: () => {
            cy.visit('/');

            cy.elementExists(LOGOUT_BUTTON_SELECTOR).then(exists => {
                if (!exists) {
                    throw new Error('Login failed');
                }
            })
        }
    });
});

const LOGOUT_BUTTON_SELECTOR = '.navbar-form .btn-danger';

export function getLogoutButton() {
    return cy.get(LOGOUT_BUTTON_SELECTOR);
}
Cypress.Commands.add('logout', function () {
    cy.visit('/');
    getLogoutButton().click();
    Cypress.session.clearAllSavedSessions();
});

import './torrent.commands';
