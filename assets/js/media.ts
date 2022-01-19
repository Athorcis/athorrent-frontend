/* eslint-env browser */

import $ from 'jquery';
import 'mediaelement';
import '../css/media.scss';
import 'mediaelement/build/mediaelementplayer.css';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';

class MediaPage extends AbstractPage {

    init() {
        ($('audio, video') as any).mediaelementplayer({
            stretching: 'responsive',
            iconSprite: require('mediaelement/build/mejs-controls.svg')
        });
    }
}

Application.create().run(MediaPage);
