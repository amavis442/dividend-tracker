import Chart from 'chart.js';

const chartRawData = document.querySelector('#chartAllocation');
var chartData = JSON.parse(chartRawData.dataset.chartData);
var chartLabels = JSON.parse(chartRawData.dataset.chartLabels);

$(function(){
	var config = {
		type: 'pie',
		data: {
			datasets: [
			{
				data: chartData,
				backgroundColor: [
					chartColors.red,
					chartColors.orange,
					chartColors.yellow,
					chartColors.green,
					chartColors.blue,
					chartColors.purple,
					chartColors.beige,
					chartColors.lightgreen,
					chartColors.grey,
					chartColors.aqua,
					chartColors.lightorange,
					chartColors.purple,
				],
				label: 'Allocation'
			}
			],
				labels: chartLabels
		},
		options: {
			responsive: true,
			title: {
				display: true,
				fontSize: 24,
				text: 'Allocation per sector'
			}
		}
	};

	var ctxAllocation = document.getElementById('chartAllocation').getContext('2d');
	window.myPie = new Chart(ctxAllocation, config);
});
