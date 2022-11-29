import { createApp } from 'vue';
import Stockprice from './components/Stockprice';
import process from 'process';

let baseURL;

if (!process.env.NODE_ENV || process.env.NODE_ENV === 'development') {
  baseURL = 'http://localhost/';
} else {
  baseURL = 'https://dividend.banpagi.com/';
}

/*
{
  components: {Stockprice},
  data: {
    url: baseURL
  }
}
*/
console.log('VUEJS......');
const app = createApp({
  components: { Stockprice },
  provide: {
    apiBaseUrl: baseURL
  },
  data: function () {
    return {
      url: baseURL
    };
  },

}
);
//app.prototype.$apiBaseUrl = baseURL;
app.mount('#app');

