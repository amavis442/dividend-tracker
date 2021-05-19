import Vue from 'vue';
import Stockprice from './components/Stockprice';
import process from  'process';

let baseURL;

if (!process.env.NODE_ENV || process.env.NODE_ENV === 'development') {
  baseURL = 'http://localhost/';
} else {
  baseURL = 'https://dividend.banpagi.com/';
}
Vue.prototype.$apiBaseUrl = baseURL;

new Vue({
  el: '#app',
  components: {Stockprice},
  data: { 
    url: baseURL
  }
})
