$(document).ready(function() {
	var isef_actions = $('.pages ul.actions');//use it more than one's
	$('.pages ul').not(isef_actions).css('display', 'none'); //hide at the beginning

    	var slideSpeed = 'slow'; // 'slow', 'normal', 'fast', or miliseconds 
    	$('li.folder').each(function() {
        var thisHref = $(this).attr('href')
        if ((window.location.pathname.indexOf(thisHref) == 0) || (window.location.pathname.indexOf('/' + thisHref) == 0)) {
           $(this).addClass('Current');
        }

    });

 

    $('.Current').parent('li').children('ul').show();
    $('.Current').parents('ul').show();



    $('.pages li').click(function(event) {
        if ($(this).children('a').length == 0) {
            if ($(this).children('ul').html() != null) {
               $(this).parent('ul').children('li').children('ul').not(isef_actions).hide(slideSpeed);
		   if ($(this).children('ul').css('display') == "block") {
                    $(this).children('ul').not(isef_actions).hide(slideSpeed);
			 } else {
                    $(this).children('ul').show(slideSpeed);
                }
            }
      event.stopPropagation();
        }

    });


});
