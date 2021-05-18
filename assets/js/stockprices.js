import jQuery from 'jquery';

const $ = jQuery;

function findStockprice(symbol) {
  return fetch('http://localhost/api/stock_prices/' + symbol).then(resp => resp.json());
}

function setPrice(elmId, price) {
  document.getElementById(elmId).innerHTML = price;
}

function updatePrice(elementId, symbol) {
  findStockprice(symbol).then(res => {
    console.log('Symbol is ' + res.symbol);
    console.log('Price is ' + res.price);
    console.log('Element is ' + elementId);
    console.log('---------------');
    setPrice(elementId, res.price);
  }, rejected => { console.log(rejected) })
    .catch(console.log.bind(console));
}

function updateStockprices() {
  var symbols = $('.stockprice');

  for (var n = 0; n < symbols.length; n++) {
    var element = symbols[n];
    var elementId = element.id;
    var searchSymbol = element.dataset.symbol;
    updatePrice(elementId, searchSymbol);
  }
}

$(function () {
  setInterval(updateStockprices(), 15000);
});
