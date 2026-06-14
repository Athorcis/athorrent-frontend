
function searchTorrents(query: string, source: string) {
    cy.visit('/search/');

    if (source) {
        cy.get('[name=source]').select(source);
    }

    cy.get('[name=q]').type(query);
    cy.get('.search-control button').click();
}

describe('search', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data?clear-all=false');
        cy.login();
    });

    it('should return results', () => {
        searchTorrents('debian security');

        cy.get('tbody').should('have.descendants', 'tr');
    });

    it('should allow to search specific source', () => {
        searchTorrents('debian security', 'nyaasi');

        cy.get('tbody').should('not.exist');
    });

    it('should allow to add magnets', () => {
        searchTorrents('debian');

        cy.get('tbody > tr:first-child td:first-child a').invoke('text').then(function (name) {
            cy.get('tbody > tr:first-child .col-add-magnet > a').click();
            cy.get('.torrent-name').should('have.text', name);
        });
    });
});
