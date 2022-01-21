/* eslint-env node */
const fs = require('fs');
const {fontawesomeSubset} = require('fontawesome-subset');

const iconsMap = {
    solid: [
        'arrow-up',
        'arrow-down',
        'sync-alt',
        'file',
        'times',
        'level-up-alt',
        'download',
        'play',
        'search-plus',
        'link',
        'unlink',
        'info',
        'pause',
        'magnet',
        'sign-out-alt',
        'user',
        'cog',
        'share',
        'lock',
        'wrench',
        'undo',
        'exchange-alt',
        'exclamation-triangle'
    ],
    regular: [
        'folder-open',
        'file-alt',
        'file-image',
        'file-audio',
        'file-video',
        'file-pdf',
        'file-archive',
        'file'
    ],
    brands: [
        'github'
    ]
}

// Create or append a task to be ran with your configuration
fontawesomeSubset(iconsMap, 'assets/fonts/font-awesome');

const icons = Object.values(iconsMap).reduce(function (accumulator, icons) {

    for (const icon of icons) {
        if (!accumulator.includes(icon)) {
            accumulator.push(icon);
        }
    }

    return accumulator;
}, []);

const sass = `// Do not edit this file as it is auto-generated by script/subset-font-awesome.js

$icons: (
    ${icons.map(icon => `${icon}: $fa-var-${icon}`).join(',\n    ')}
);

@each $key, $value in $icons {
    .#{$fa-css-prefix}-#{$key}:before {
        content: fa-content($value);
    }
}
`;

fs.writeFileSync('assets/css/font-awesome/_icons.scss', sass);
