import {MediaElementPlayer} from 'mediaelement/full';
import '../css/media.scss';
import 'mediaelement/build/mediaelementplayer.css';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import iconSprite from 'mediaelement/build/mejs-controls.svg';

class MediaPage extends AbstractPage {

    init() {
        new MediaElementPlayer(document.querySelector('audio, video') as HTMLElement, {
            stretching: 'responsive',
            iconSprite,
        });
    }
}

Application.create().run(MediaPage);
