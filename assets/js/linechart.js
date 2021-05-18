import Chart from 'chart.js';
import chartColors from './chartcolors';
import jQuery from 'jquery';

const $ = jQuery;

const lineChart = function (canvasName, cdata, clabels, csubTitle, csign) {
  var s = cdata.length;
  var cdatasets = [];
  for (var n = 0; n < s; n++) {
    var cdataset = cdata[n];
    cdatasets.push({
      data: cdataset.data,
      borderColor: chartColors[n],
      label: cdataset.label
    });
  }

  var config = {
    type: 'line',
    data: {
      datasets: cdatasets,
      labels: clabels
    },
    options: {
      title: {
        display: true,
        fontSize: 24,
        text: csubTitle
      },
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true,
            callback: function (value) {
              if (csign == '%') {
                return value + csign;
              }
              return csign + value;
            }
          }
        }]
      }
    }
  };

  var ctxBarChart = document.getElementById(canvasName).getContext('2d');
  new Chart(ctxBarChart, config);
}


$(function () {
  var chartElements = $('.chart');

  for (var n = 0; n < chartElements.length; n++) {
    var chartElement = chartElements[n];
    var chartData = JSON.parse(chartElement.dataset.chartData);
    var chartLabels = JSON.parse(chartElement.dataset.chartLabels);
    //var chartTitle = chartElement.dataset.chartTitle;
    var chartSubTitle = chartElement.dataset.chartSubTitle;
    var chartSign = chartElement.dataset.chartSign;

    lineChart(chartElement.id, chartData, chartLabels, chartSubTitle, chartSign);
  }
});
