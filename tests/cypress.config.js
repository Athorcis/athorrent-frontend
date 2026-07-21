const AdmZip = require('adm-zip');

module.exports = {
    allowCypressEnv: false,

    e2e: {
        baseUrl: 'https://athorrent.local',

        modifyObstructiveCode: false,

        // Video.js HTML skins render controls inside open shadow roots.
        includeShadowDom: true,

        setupNodeEvents(on, config) {
            on('task', {
                listZipEntries(filePath) {
                    const zip = new AdmZip(filePath);

                    return zip.getEntries()
                        .filter((entry) => !entry.isDirectory)
                        .map((entry) => entry.entryName)
                        .sort();
                },
            });
        },
    },
};
