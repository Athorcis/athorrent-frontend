import $ from 'jquery';
import 'mediaelement';
import '../css/media.scss';
import 'mediaelement/build/mediaelementplayer.css';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import iconSprite from 'mediaelement/build/mejs-controls.svg';

class MediaPage extends AbstractPage {

    init() {
        $('audio, video').mediaelementplayer({
            stretching: 'responsive',
            iconSprite,
        });
    }
}

Application.create().run(MediaPage);
