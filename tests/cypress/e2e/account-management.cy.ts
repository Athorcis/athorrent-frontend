import {ALT_PASSWORD, ALT_USERNAME, DEFAULT_PASSWORD, DEFAULT_USERNAME} from "../support/commands";
import {checkIfFieldHasError, createUser} from "../support/utils";
import * as assert from "node:assert";

describe('account-management', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
    });

    it('should display form', () => {
        cy.visit('/user/account/');

        cy.get('#edit_account_username').should('have.value', DEFAULT_USERNAME);
    });

    it('should update username', () => {
        const NEW_USERNAME = 'admin2';

        assert.notStrictEqual(DEFAULT_USERNAME, NEW_USERNAME);

        cy.visit('/user/account/');
        cy.get('#edit_account_username').clear().type(NEW_USERNAME);
        cy.get('#edit_account_current_password').type(DEFAULT_PASSWORD);
        cy.get('#edit_account_update').click();

        cy.login(NEW_USERNAME);
    });

    it('should update password', () => {
        const NEW_PASSWORD = 'test2';

        assert.notStrictEqual(DEFAULT_PASSWORD, NEW_PASSWORD);

        cy.visit('/user/account/');
        cy.get('#edit_account_current_password').type(DEFAULT_PASSWORD);
        cy.get('#edit_account_plainPassword_first').type(NEW_PASSWORD);
        cy.get('#edit_account_plainPassword_second').type(NEW_PASSWORD);
        cy.get('#edit_account_update').click();

        cy.login(DEFAULT_USERNAME, NEW_PASSWORD);
    });

    it('should validate username', () => {
        createUser(ALT_USERNAME, ALT_PASSWORD);

        cy.visit('/user/account/');

        cy.get('#edit_account_username').clear().type(ALT_USERNAME);
        cy.get('#edit_account_current_password').type(DEFAULT_PASSWORD);
        cy.get('#edit_account_update').click();

        checkIfFieldHasError('#edit_account_username_error1');
    });

    it('should validate password', () => {
        const INVALID_PASSWORD = 'test2';

        assert.notStrictEqual(DEFAULT_PASSWORD, INVALID_PASSWORD);

        cy.visit('/user/account/');
        cy.get('#edit_account_current_password').type(INVALID_PASSWORD);
        cy.get('#edit_account_update').click();

        checkIfFieldHasError('#edit_account_current_password');
    });

    it('should validate password confirmation', () => {
        cy.visit('/user/account/');
        cy.get('#edit_account_current_password').type(DEFAULT_PASSWORD);
        cy.get('#edit_account_plainPassword_first').type('x');
        cy.get('#edit_account_plainPassword_second').type('y');
        cy.get('#edit_account_update').click();

        checkIfFieldHasError('#edit_account_plainPassword_first');
    });
});
