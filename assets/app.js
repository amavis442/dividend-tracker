import './bootstrap.js';
//import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.css';
//import 'summernote/dist/summernote-bs5.css';
import './styles/app.css';

import * as bootstrap from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import './js/Tooltip.js';
import bsCustomFileInput from 'bs-custom-file-input';
import './js/fileupload.js';
import './js/ckeditor5.js';
import './js/turbo/turbo-helper.js';

bsCustomFileInput.init();
window.bootstrap = bootstrap;

