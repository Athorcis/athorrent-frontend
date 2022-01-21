import $ from 'jquery';
import '../css/search.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';

class SearchPage extends AbstractPage {

    init() {
        $('.nav-tabs a').on('click',function (event) {
            event.preventDefault();
            $(this).tab('show');
        });
    }
}

Application.create().run(SearchPage);
