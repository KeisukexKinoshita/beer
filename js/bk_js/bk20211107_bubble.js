var ctx = document.getElementById('mychart');
var data_b = [];
Chart.register(ChartDataLabels);

for (var i = 0; i < arr_ID.length; i++){
     data_b[i]=({label:arr_NAME[i],backgroundColor:'black',borderColor:'rgba(0,0,0,0)',title:'title1',data:[{x:arr_IBU[i],y:arr_COLOR[i],r:arr_ALCOHOL[i]}]});
}
console.log(data_b);

var myChart = new Chart(ctx, {
  type: 'bubble',
  data: {
    labels: arr_NAME,
    datasets:data_b,
  },
  plugins: [ChartDataLabels],
        options: {
        },
  datalabels: {
              formatter: (value, context) => {
          //return context.chart.data.labels[context.dataIndex];
              }
            },
  options: {
    //maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: arr_BREWERY[0]+' (IBU*Color)',
	 padding: 50,
             },
      datalabels: {
        formatter: function(value, context) {
          var value = context.chart.data.datasets[context.datasetIndex];
          return value.label;
        },
	 anchor: function() {
          return 'end';
        },
	 align: function() {
          return 'end';
        }
      },
     legend: {
            display: false
         },
     tooltip: {
            enabled: false,
        },
    },
     scales: {
      yAxes: { 
	   title: {
             display: true,
             text: 'Color',
	     align: 'end',
           },
	   position: 'center',
	   suggestedMin: 0,
           suggestedMax: 12,
	    grid:{
	    drawOnChartArea: false,
            display:false,
	    },
	    ticks: {
            display: false,
	    },
           },
      xAxes: {
	   title: {
             display: true,
             text: 'IBU',
	     align: 'end',
           },
	   position: 'center',
	   suggestedMin: 0,
           suggestedMax: 90,
	   grid:{
	      drawOnChartArea: false,
	     display:false,
            },
	    ticks: {
            display: false,
            },
         },
             },
    // events: ['click'],
     tooltips: {
             enabled: false
         },
     onClick: (e, activeEls) => {
	       if (!activeEls.length) {
                 return;
	       }
	     let datasetIndex = activeEls[0].datasetIndex;
             let dataIndex = activeEls[0].index;
	     let datasetLabel = e.chart.data.datasets[datasetIndex].label;
	     //window.open(arr_URL[datasetIndex]);
	     window.location.href="../php/uchu_mars.php?beer_id="+arr_ID[datasetIndex];
             },
           }
});

