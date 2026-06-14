
describe('Cypress Studio Demo', () => {
    beforeEach(() => {
        cy.request('POST', '/tests/reset-data');
        cy.login();
        cy.visit('/user/torrents/');
    });

    it('should add torrent file', function() {
        cy.torrentFile('sintel.torrent')

            .torrentStatus('En téléchargement');
    });

    it('should pause and resume torrents', function () {
        cy.torrentFile('sintel.torrent')

            .torrentClick('.torrent-pause')
            .torrentStatus('En pause')

            .torrentClick('.torrent-resume')
            .torrentStatus('En téléchargement')
    });

    it('should allow to remove torrents', function () {
        cy.torrentFile('sintel.torrent')

            .torrentClick('.torrent-remove');
    });

    it('should fail for invalid torrent file', function() {
        cy.torrentFile('invalid.torrent', false);
        cy.expectModal('Fichier torrent invalide');
    });


    it('should add magnet uri', function() {
        cy.torrentMagnet('magnet:?xt=urn:btih:a88fda5954e89178c372716a6a78b8180ed4dad3&dn=The+WIRED+CD+-+Rip.+Sample.+Mash.+Share&tr=udp%3A%2F%2Fexplodie.org%3A6969&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Ftracker.empire-js.us%3A1337&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337&tr=wss%3A%2F%2Ftracker.btorrent.xyz&tr=wss%3A%2F%2Ftracker.fastcast.nz&tr=wss%3A%2F%2Ftracker.openwebtorrent.com&ws=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2F&xs=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2Fwired-cd.torrent');

        cy.get('.torrent-name').should('have.text', 'The WIRED CD - Rip. Sample. Mash. Share')

    });

    it ('should fail for invalid magnet uri', function() {
        cy.torrentMagnet('magnet:?', false);
        cy.expectModal('Lien magnet invalide');
    });

    it('should show list of trackers', function() {
        cy.torrentFile('sintel.torrent');

        cy.get('.torrent-detail').click();
        cy.get('#torrent-trackers tbody').should('have.descendants', 'tr');
    });

    it('should allow access to qbittorrent web version', function () {
        cy.get('.open-qbittorrent-ui').should('exist');
        // qbittorrent web won't work since it's the tested pages are opened in an iframe
    });
});
