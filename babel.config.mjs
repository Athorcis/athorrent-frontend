import corejsPackage from "core-js/package.json" with { type: "json" };

export default {
    targets: "defaults",
    presets: [
        "@babel/preset-env",
        "@babel/preset-typescript",
    ],
    plugins: [
        [
            "babel-plugin-polyfill-corejs3",
            {
                method: "usage-global",
                version: corejsPackage.version,
            },
        ],
    ],
};
