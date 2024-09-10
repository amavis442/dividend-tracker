import { registerVueControllerComponents } from '@symfony/ux-vue';
import { registerReactControllerComponents } from '@symfony/ux-react';
import * as bootstrap from 'bootstrap';
import './bootstrap.js';
import './styles/global.scss';
import './styles/app.css';
import bsCustomFileInput from 'bs-custom-file-input';
import jQuery from 'jquery';

const $ = jQuery;
window.bootstrap = bootstrap;

jQuery(function () {
  $('[data-toggle="popover"]').popover();
  $('[data-toggle="tooltip"]').tooltip();
  bsCustomFileInput.init();

  //$('[data-bs-toggle="popover"]').popover()

  //const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
  //const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
});

const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));
