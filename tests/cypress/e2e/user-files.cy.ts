import {getFileSelector, uploadFile, uploadFiles} from "../support/commands";
import {resetTestData} from "../support/utils";


function waitForPlayerStart(selector: string) {
    cy.get(selector).then(async ($media) => {
        const media = $media[0] as HTMLMediaElement;

        if (!media.paused && !media.ended) {
            return;
        }

        return new Promise<void>((resolve) => {
            media.addEventListener('playing', () => resolve(), {once: true});
        });
    });
}

function playPlayer(mediaSelector: string) {
    cy.get('media-play-button').click();

    waitForPlayerStart(mediaSelector);
}

function pausePlayerAndAssertElapsed(mediaSelector: string) {
    playPlayer(mediaSelector);
    cy.wait(1500);

    cy.get('.media-controls').trigger('mouseover');
    cy.get('media-play-button')
        .click();

    cy.get(mediaSelector).should(($el) => {
        const media = $el[0] as HTMLMediaElement;

        expect(media.paused).to.eq(true);
        expect(media.currentTime).to.be.at.least(1);
        expect(media.currentTime).to.be.lessThan(2);
    });
}

describe('user-files', () => {
    beforeEach(() => {
        resetTestData();
        cy.login();
        cy.visit('/user/files/');
    });

    it('should be accessible', () => {
        cy.location('pathname').should('eq', '/user/files/');
        cy.get('.main-header h1').should('be.visible');
        cy.get('.add-button').should('exist');
    });

    it('should be empty by default', () => {
        cy.get('#alert-empty-dir').should('be.visible');
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
            cy.get('main img').should('have.attr', 'src').and('equal', href);
        });
    });

    it('should handle audio', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.mp3')

        cy.dropdownItem('.play-file', selector).click();
        cy.get('h1').should('have.text', basename);

        pausePlayerAndAssertElapsed('audio');
    });

    it('should handle video', () => {
        const { basename, selector } = uploadFile('cypress/fixtures/files/test.mp4')

        cy.dropdownItem('.play-file', selector).click();
        cy.get('h1').should('have.text', basename);

        pausePlayerAndAssertElapsed('video');
    });

    it('should allow directory download as tar.gz', () => {
        uploadFiles(
            ['cypress/fixtures/files/test.txt', 'cypress/fixtures/files/test.txt'],
            ['folder/a.txt', 'folder/b.txt'],
        );

        const dirSelector = getFileSelector('folder');

        cy.dropdownItem('.download-file', dirSelector).click();

        cy.readFile('cypress/downloads/folder.tar.gz', null).should('not.be.null');

        cy.exec('tar -tzf cypress/downloads/folder.tar.gz').then(({stdout}) => {
            const members = stdout.trim().split(/\r?\n/).filter(Boolean);

            expect(members).to.deep.equals(['a.txt', 'b.txt']);
        });
    });

    // @TODO check directory upload

    //@TODO check inner directories
});
