$(function() {
  $('.image_box .disabled_checkbox').click(function() {
    return false;
  });

  $('img.thumbnail').click(function() {
    var $imageList = $('.image_list');

    $imageList.find('img.thumbnail.checked').removeClass('checked');

    $(this).addClass('checked');
  });
});
