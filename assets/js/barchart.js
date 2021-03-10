import Chart from 'chart.js';

$(function(){
	var config = {
		type: 'bar',
		data: {
			datasets: [
			{
				data: chartData,
				backgroundColor: [
					"#0074D9", 
						"#FF4136", 
						"#2ECC40", 
						"#FF851B", 
						"#7FDBFF", 
						"#B10DC9", 
						"#FFDC00", 
						"#001f3f", 
						"#39CCCC", 
						"#01FF70", 
						"#85144b", 
						"#F012BE", 
						"#3D9970", 
						"#111111", 
						"#AAAAAA",
						"#0074D9", 
						"#FF4136", 
						"#2ECC40", 
						"#FF851B", 
						"#7FDBFF", 
						"#B10DC9", 
						"#FFDC00", 
						"#001f3f", 
						"#39CCCC", 
						"#01FF70", 
						"#85144b", 
						"#F012BE", 
						"#3D9970", 
						"#111111", 
						"#AAAAAA",
						"#0074D9", 
						"#FF4136", 
						"#2ECC40", 
						"#FF851B", 
						"#7FDBFF", 
						"#B10DC9", 
						"#FFDC00", 
						"#001f3f", 
						"#39CCCC", 
						"#01FF70", 
						"#85144b", 
						"#F012BE", 
						"#3D9970", 
						"#111111", 
						"#AAAAAA"
				],
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
			},
			scales: {
				yAxes: [{
					ticks: {
						beginAtZero: true,
						callback: function(value, index, values) {
						   return '€' + value;
						}
					}
				}]
			}
		}
	};

	var ctxBarChart = document.getElementById('barChartCanvas').getContext('2d');
	window.myPie = new Chart(ctxBarChart, config);
});
