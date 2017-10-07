/* eslint-env browser, amd */

require(['jquery', 'athorrent', 'mediaelement'], function ($) {
    'use strict';

    $('audio, video').mediaelementplayer({
        stretching: 'responsive'
    });
});
