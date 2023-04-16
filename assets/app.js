import jQuery from 'jquery';

const $ = jQuery;

import 'bootstrap';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/global.scss';

jQuery(function () {
  $('[data-toggle="popover"]').popover();
  $('[data-toggle="tooltip"]').tooltip();
});

import { startStimulusApp } from '@symfony/stimulus-bridge';

// eslint-disable-next-line no-undef
export const app = startStimulusApp(require.context(
  '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
  true,
  /\.(j|t)sx?$/
));
