import $ from 'jquery';
import '../css/search.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';

class SearchPage extends AbstractPage {

    init() {
        on(document, 'click', '.nav-tags a', (event: MouseEvent) => {
            event.preventDefault();
            $(event.target).tab('show');
        });
    }
}

Application.create().run(SearchPage);
