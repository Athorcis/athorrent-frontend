module.exports = {
    allowCypressEnv: false,

    e2e: {
        baseUrl: 'https://athorrent.local',

        modifyObstructiveCode: false,

        // Video.js HTML skins render controls inside open shadow roots.
        includeShadowDom: true,

        setupNodeEvents(on, config) {
            // implement node event listeners here
        },
    },
};
