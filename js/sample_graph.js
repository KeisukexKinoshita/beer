function drawBarChart(js_array) {
console.log(tmpLabels);
  var ctx = document.getElementById("myChart").getContext("2d");
  var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: tmpLabels,
      datasets: [
        { label: "Tokyo", data: tmpData1, backgroundColor: "red" },
        { label: "Osaka", data: tmpData2, backgroundColor: "blue" }
      ]
    }
  });
}
drawBarChart(js_array); 

