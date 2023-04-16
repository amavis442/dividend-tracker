import chartColors from './chartcolors';
import jQuery from 'jquery';
import Chart from 'chart.js/auto';

const $ = jQuery;

const barChart = function (canvasName, cdata, clabels, ctitle, csubTitle, csign) {
  var config = {
    type: 'bar',
    data: {
      labels: clabels,
      datasets: [
        {
          data: cdata,
          backgroundColor: chartColors,
          label: ctitle
        }
      ]
    },
    options: {
      responsive: true,
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
          ticks: {
            beginAtZero: true,
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
    var chartTitle = chartElement.dataset.chartTitle;
    var chartSubTitle = chartElement.dataset.chartSubTitle;
    var chartSign = chartElement.dataset.chartSign;

    barChart(chartElement.id, chartData, chartLabels, chartTitle, chartSubTitle, chartSign);
  }
});
