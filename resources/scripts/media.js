/* eslint-env browser, amd */

import $ from 'jquery';
import athorrent from 'athorrent';
import MediaElement from 'mediaelement';

$('audio, video').mediaelementplayer({
    stretching: 'responsive'
});
