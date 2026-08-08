import {uploadFile} from "../support/commands";
import {pathWithLocale, pathWithoutLocale, resetTestData} from "../support/utils";

function uploadAndShare(path: string) {
    const { basename, selector } = uploadFile(path);

    cy.dropdownItem('.add-sharing', selector).click();

    cy.get('#dialog-sharing-link').as('dialog');
    cy.get('@dialog').should('be.visible');

    return cy.get('@dialog').find('.modal-body a').invoke('attr', 'href').then(async (url) => {
        await cy.get('@dialog').find('.close').click();
        return {basename, selector, url};
    });
}

function getPathFromLocalizedUrl(url: string): string {
    const localizedPath = new URL(url).pathname;
    return pathWithoutLocale(localizedPath);
}

function getSharingId(path: string): string {
    return path.replace(/^\/shared\/(.+)\/files\/$/, '$1');
}

describe('file-sharing', () => {
    beforeEach(() => {
        resetTestData();
        cy.login();
    });

    it('should allow to share files', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {
            cy.visit(url);
            cy.get('h1').should('contain', basename);
        });

    });

    it('shared files should be accessible without login', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {
            cy.logout();

            cy.visit(url);
            cy.get('h1').should('contain', basename);
        });
    });

    it('shared files should be listed in sharing list', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, url}) => {

            const sharingPath = getPathFromLocalizedUrl(url);
            const sharingId = getSharingId(sharingPath);

            cy.visit(pathWithLocale('/user/sharings/'));

            cy.get(`#sharing-${sharingId} a`)
                .should('have.text', basename)
                .invoke('attr', 'href').should('deep.equal', pathWithLocale(sharingPath));
        });
    });

    it('sharing list should allow to remove sharing', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({url}) => {

            const sharingId = getSharingId(getPathFromLocalizedUrl(url));

            cy.visit(pathWithLocale('/user/sharings/'));

            cy.get(`#sharing-${sharingId} .sharing-remove`).click();
            cy.confirmModal();
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

    it('displaying a shared single text file should allow going back via root breadcrumb', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({basename, selector, url}) => {
            cy.visit(url);

            cy.dropdownItem('.display-file', selector).click();
            cy.get('h1').should('have.text', basename);

            cy.get('.breadcrumb li').first().find('a').should('exist').click();

            cy.get('.file-list').should('exist');
            cy.get(selector).should('exist');
        });
    });

    it('should allow to unshare shared files', () => {
        uploadAndShare('cypress/fixtures/files/test.txt').then(({selector}) => {
            cy.dropdownItem('.sharing-remove', selector).click();
            cy.confirmModal();

            cy.dropdownItem('.add-sharing', selector).should('exist');
        });
    });
});
