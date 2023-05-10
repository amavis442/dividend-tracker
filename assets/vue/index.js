import { createApp } from 'vue';
import Stockprice from './components/Stockprice';
import process from 'process';

let baseURL;

if (!process.env.NODE_ENV || process.env.NODE_ENV === 'development') {
  baseURL = 'http://dividend.local/';
} else {
  baseURL = 'https://dividend.odroid/';
}

/*
{
  components: {Stockprice},
  data: {
    url: baseURL
  }
}
*/
const app = createApp({
  components: { Stockprice },
  provide: {
    apiBaseUrl: baseURL
  },
  data: function () {
    return {
      url: baseURL,
      prices: []
    };
  },
  mounted: function () {
    setInterval(this.getPrices, 60000);
  },
  created: async function () {
    this.getPrices();
  },
  methods: {
    getPrices: async function () {
      fetch(baseURL + "api/prices")
        .then((resp) => resp.json())
        .then((result) => {
          this.prices = result.data;
        });
    }
  }
}
);

app.mount('#app');

