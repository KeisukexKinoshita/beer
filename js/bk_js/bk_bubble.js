var ctx = document.getElementById('mychart');
var x_commment = 3;

var data_b = [];

for (var i = 0; i < arr_hazy.length; i++){
     data_b[i]=({x:arr_hazy[i],y:arr_alcohol[i],r:arr_popularity[i]});
}

console.log(data_b);


var myChart = new Chart(ctx, {
  type: 'bubble',
  data: {
    labels: arr_name,
    datasets: [{
      label:'label2',
      data:data_b,
      backgroundColor: 'black',
    }],
  },
  options: {
    scales: {
      y: { min: 0, max: 15 },
      x: { min: 0, max: 10 },
    },
  },
});
function updateChart(){
  // Update chart
  //myChart.data.datasets[0].data[0].r = comment_js;
  myChart.update();
}
updateChart();
