var ctx = document.getElementById('mychart');
var data_b = [];
var yAxes_max =12,yAxes_min=0,xAxes_max=90,xAxes_min=0;
Chart.register(ChartDataLabels);

for (var i = 0; i < prd_ProductID.length; i++){
     data_b[i]=({label:prd_ProductName[i],backgroundColor:'black',borderColor:'rgba(0,0,0,0)',title:'title1',data:[{x:prd_IBU[i],y:prd_Color[i],r:prd_Alcohol[i]}]});
}
console.log(data_b);

var myChart = new Chart(ctx, {
  type: 'bubble',
  data: {
    labels: prd_ProductName,
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
        text: mak_MakerName[0],
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
             //text: 'Color',
	     align: 'end',
           },
	   position: 'center',
	   suggestedMin: yAxes_min,
           suggestedMax: yAxes_max,
	    grid:{
	    drawOnChartArea: false,
            display:false,
	    },
	    ticks: {
	      callback:function(label){
	        if(label ==yAxes_min){
		      return "Light";
	        }else if(label ==yAxes_max){
			return "Dark";
		}else{
			return ;
		     }
            //display: false,
	      },
                 },
           },
      xAxes: {
	   title: {
             display: true,
             //text: 'IBU',
	     align: 'end',
           },
	   position: 'center',
	   suggestedMin: xAxes_min,
           suggestedMax: xAxes_max,
	   grid:{
	      drawOnChartArea: false,
	     display:false,
            },
	    ticks: {
	      callback:function(label){
	        if(label ==xAxes_min){
		      return "Sweet";
	        }else if(label ==xAxes_max){
			return "Bitter";
		}else{
			return ;
		     }
              //display: false,
	      },
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
	     //window.open(prd_URL[datasetIndex]);
	     window.location.href="../php/product.php?product_id="+prd_ProductID[datasetIndex]+"&maker_id="+mak_MakerID[0];
             },
           }
});

