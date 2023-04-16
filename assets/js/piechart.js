import chartColors from './chartcolors';
import jQuery from 'jquery';
import Chart from 'chart.js/auto';

const $ = jQuery;

$(function () {
  var chartElement = $('#pieChartCanvas');

  var chartTitle = chartElement.data('chartTitle');
  var chartSubTitle = chartElement.data('chartSubTitle');
  var chartLabels = JSON.parse(JSON.stringify(chartElement.data('chartLabels')));
  var chartData = JSON.parse(JSON.stringify(chartElement.data('chartData')));

  var config = {
    type: 'pie',
    data: {
      datasets: [
        {
          data: chartData,
          backgroundColor: chartColors,
          label: chartTitle
        }
      ],
      labels: chartLabels
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: chartSubTitle,
          font: {
            size: 24
          }
        },
        legend: {
          position: 'top',
        },
      },

    }
  };

  var ctxPieChart = document.getElementById('pieChartCanvas').getContext('2d');
  window.myPie = new Chart(ctxPieChart, config);
});
