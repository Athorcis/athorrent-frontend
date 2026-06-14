import {uploadFile, uploadFiles} from "../support/commands";

describe('user-files', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
        cy.visit('/user/files');
    });

    it('should be accessible', () => {
        cy.get('h1').should('contain', 'Fichiers');
    });


    it('should be empty by default', () => {
        cy.get('.alert-info').should('contain', 'Aucun fichier à afficher');
    });

    it('should allow file upload', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.txt')

        cy.get(`${selector} > .file-name`).should('contain', basename);
        cy.get(`${selector} > .file-size`).should('contain', '13 B');

        cy.dropdownItem('.download-file', selector).click();
        cy.readFile('cypress/downloads/test.txt', 'binary').then(buffer => {
            cy.readFile('cypress/fixtures/files/test.txt', 'binary').should('deep.equal', buffer);
        });
    });

    it('should allow to remove file', () => {
        const { selector } = uploadFile('cypress/fixtures/files/test.txt');

        cy.intercept('DELETE', '/user/files').as('deleteFiles');

        cy.get(`${selector} .file-remove`).click();

        cy.wait('@deleteFiles');

        cy.get(selector).should('not.exist');
    });

    it('should allow multiple files upload', () => {

        uploadFiles(['cypress/fixtures/files/test.txt', 'cypress/fixtures/files/test.txt'], ['a.txt', 'b.txt']);
    });

    it('should handle text', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.txt')
        const CONTENT = 'Hello world!\n';

        cy.dropdownItem('.open-file', selector).click();
        cy.get('body').should('have.text', CONTENT);
        cy.go('back');

        cy.dropdownItem('.display-file', selector).click();
        cy.get('h1').should('have.text', basename);
        cy.get('pre').should('have.text', CONTENT);
    });

    it('should handle image', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.png')

        cy.dropdownItem('.open-file', selector).invoke('attr', 'href').then(href => {
            cy.dropdownItem('.display-file', selector, true).click();

            cy.get('h1').should('have.text', basename);
            cy.get('img').should('have.attr', 'src').and('equal', href);
        });
    });

    it('should handle audio', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.mp3')

        cy.dropdownItem('.play-file', selector).click();
        cy.get('h1').should('have.text', basename);

        cy.wait(1500);
        cy.get('.mejs__pause > button').click();
        cy.get('.mejs__currenttime').should('have.text', '00:01');
    });

    it('should handle video', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.mp4')

        cy.dropdownItem('.play-file', selector).click();
        cy.get('h1').should('have.text', basename);

        cy.wait(1500);
        cy.get('.mejs__pause > button').click();
        cy.get('.mejs__currenttime').should('have.text', '00:01');
    });

    // @TODO check directory upload

    //@TODO check inner directories
});
