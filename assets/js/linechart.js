import chartColors from './chartcolors';
import jQuery from 'jquery';
import Chart from 'chart.js/auto';

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
      plugins: {
        title: {
          display: true,
          text: csubTitle,
          font: {
            size: 24
          }
        },
        legend: {
          position: 'top',
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {

            callback: function (value) {
              if (csign == '%') {
                return value + csign;
              }
              return csign + value;
            }
          }
        }
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
