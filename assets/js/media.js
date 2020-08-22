/* eslint-env browser */

import $ from 'jquery';
import 'mediaelement';
import './athorrent';
import '../css/media.scss';
import 'mediaelement/build/mediaelementplayer.css';

$('audio, video').mediaelementplayer({
    stretching: 'responsive'
});
