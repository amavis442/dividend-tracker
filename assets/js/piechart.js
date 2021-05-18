import Chart from 'chart.js';
import chartColors from './chartcolors';
import jQuery from 'jquery';

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
      title: {
        display: true,
        fontSize: 24,
        text: chartSubTitle
      }
    }
  };

  var ctxPieChart = document.getElementById('pieChartCanvas').getContext('2d');
  window.myPie = new Chart(ctxPieChart, config);
});
