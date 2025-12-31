import {ALT_PASSWORD, ALT_USERNAME} from "./commands";

export function createUser(username: string, password: string) {
    cy.visit('/administration/users/add');

    cy.get('#add_user_username').type(username);
    cy.get('#add_user_plainPassword').type(password);
    cy.get('#add_user_role').select(0);
    cy.get('#add_user_add').click();

    cy.get('#user-2 > .user-name').should('have.text', username);
}

export function createAltUser() {
    createUser(ALT_USERNAME, ALT_PASSWORD);
}

export function checkIfFieldHasError(selector: string) {
    cy.get(selector).parents('.form-group').should('have.class', 'has-error');
}
