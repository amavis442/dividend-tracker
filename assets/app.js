import jquery from 'jquery';

const $ = jquery;
global.$ = global.jQuery = $;

import 'bootstrap/js/dist/popover';
import 'bootstrap/js/dist/tooltip';
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/tab';

//import 'bootstrap';
import 'summernote/dist/summernote-bs4.min.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/global.scss';

jQuery(function(){
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    $('.summernote').summernote({
        height: 600,   //set editable area's height
        codemirror: { // codemirror options
          theme: 'monokai'
        }
      });
});

