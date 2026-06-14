import {uploadFile} from "../support/commands";

function uploadAndShare(path) {
    const { basename, selector } = uploadFile(path);

    cy.dropdownItem('.add-sharing', selector).click();

    cy.get('.modal-header').should('contain', 'Lien de partage');

    return cy.get('.modal-body a').invoke('attr', 'href').then(async (url) => {
        await cy.get('.modal-dialog .close').click();
        return {basename, selector, url};
    });
}

describe('file-sharing', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
    });

    it('should allow to share files', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {
            cy.visit(url);
            cy.get('h1').should('contain', basename);
        });

    });

    it('shared files should be listed in sharing list', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {

            const relativeUrl = url.replace(/^.+(\/sharings\/.+\/files\/)$/, '$1');
            const sharingId = relativeUrl.replace(/^\/sharings\/(.+)\/files\/$/, '$1');

            cy.visit('/user/sharings/');

            cy.get(`#sharing-${sharingId} a`)
                .should('have.text', basename)
                .invoke('attr', 'href').should('deep.equal', relativeUrl);
        });
    });

    it('sharing list should allow to remove sharing', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {

            const sharingId = url.replace(/^.+\/sharings\/(.+)\/files\/$/, '$1');

            cy.visit('/user/sharings/');

            cy.get(`#sharing-${sharingId} .sharing-remove`).click();
            cy.get(`#sharing-${sharingId}`).should('not.exist');
        });
    });

    it('shared files should not be shareable or removable', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({selector, url}) => {

            cy.visit(url);

            cy.dropdownItem('.add-sharing', selector).should('not.exist');
            cy.dropdownItem('.file-remove', selector, false).should('not.exist');
        });
    });

    it('should allow to unshare shared files', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({selector}) => {
            cy.dropdownItem('.sharing-remove', selector).click();

            cy.dropdownItem('.add-sharing', selector).should('exist');
        });
    });
});
