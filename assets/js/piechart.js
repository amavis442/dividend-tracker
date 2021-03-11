import Chart from 'chart.js';
import chartColors from './chartcolors';

$(function(){
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

export default Chart;
