/// <reference types="cypress" />

import Chainable = Cypress.Chainable;

declare namespace Cypress {
    interface Chainable {
        elementExists(selectior: string): Chainable<null>;
        login(username: string, password: string): Chainable<null>;
        logout(): Chainable<null>;
        dropdownItem(selector: string, parentSelector: string): Chainable<null>;
        dropdownItem(selector: string, parentSelector: string, skipOpen: boolean): Chainable<null>;
        expectModal(text: string): Chainable<null>;
    }
}

export const DEFAULT_USERNAME = 'admin';
export const DEFAULT_PASSWORD = 'test';

export const ALT_USERNAME = 'test';
export const ALT_PASSWORD = 'password';

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

Cypress.Commands.add('dropdownItem', function (selector: string, parentSelector: string, skipOpen = false) {

    if (!skipOpen) {
        cy.get(`${parentSelector} .dropdown > button`).click();
    }

    cy.get(`${parentSelector} .dropdown ${selector}`);
});

Cypress.Commands.add('expectModal', function (text: string) {
    cy.get('.modal-body').should('have.text', text);
    cy.get('.modal button.close').click();
});


function getBasename(path: string): string {
    return path.replace(/^(?:.*\/)?([^/]+)$/, '$1');
}

export function uploadFiles(paths: string[], relativePaths: string[] = [], asDirectory = false) {
    cy.visit('/user/files');
    cy.get('.add-button').click();

    if (asDirectory) {
        cy.get('.add-directory').click();
    }
    else {
        cy.get('.add-file').click();
    }

    cy.get('.dz-hidden-input').selectFile(paths.map((path, index) => {

        return {
            contents: path,
            fileName: relativePaths[index] ?? getBasename(path),
        };
    }), { force: true });

    const result = paths.map((path, index) => {
        const basename = getBasename(relativePaths[index] ?? path);
        const selector = getFileSelector(basename);

        return { basename, selector };
    });

    for (const { selector } of result) {
        cy.get(`${selector}`).should('exist');
    }

    return result;
}

export function uploadFile(path: string) {
    return uploadFiles([path])[0];
}

export function getFileSelector(filename: string) {
    return `[data-name="${btoa(filename)}"]`;
}
