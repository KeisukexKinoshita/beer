//color
$('[class='+class_color+']').on("click", function(){                                              
                if ($(this).prop('checked')){                                                       
                    $('[class='+class_color+']').prop('checked', false);
                    $(this).prop('checked', true);
                }
            }); 

//clarity
$('[class='+class_clarity+']').on("click", function(){                                              
                if ($(this).prop('checked')){                                                       
                    $('[class='+class_clarity+']').prop('checked', false);
                    $(this).prop('checked', true);
                }
            }); 

//fruity
$('[class='+class_fruity+']').on("click", function(){                                              
                if ($(this).prop('checked')){                                                       
                    $('[class='+class_fruity+']').prop('checked', false);
                    $(this).prop('checked', true);
                }
            }); 

//favorite
$('[class='+class_favorite+']').on("click", function(){                                              
                if ($(this).prop('checked')){                                                       
                    $('[class='+class_favorite+']').prop('checked', false);
                    $(this).prop('checked', true);
                }
            }); 
