  var get_reviewNum = document.getElementById("review_num");
  //var reviewNum = review_num.textContent;
  var review_rating = document.getElementById("reviewStar_color");

  review_rating.dataset.rate = reviewNum;

  review_rating.style.width = reviewNum * 20 + "%";
