import $ from 'jquery';
import '../css/search.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';

class SearchPage extends AbstractPage {

    init() {
        document.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.nav-tabs a')) {
                event.preventDefault();
                $(target).tab('show');
            }
        })
    }
}

Application.create().run(SearchPage);
