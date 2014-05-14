$(document).ready(function() {	

	//select all the a tag with name equal to modal
	$('a[name=modal]').click(function(e) {
		//Cancel the link behavior
		e.preventDefault();
		//Get the A tag
		var id = $(this).attr('href');
		
		var url = $(this).attr('url');
		var container = $(id + ' .dialogContainer');
		if (typeof url != 'undefined' && container.length) {
			container.html('<div class="loading"></div>');
			container.load(url);
		}
	
		//Get the screen height and width
		var maskHeight = $(document).height();
		var maskWidth = $(window).width();
	
		//Set height and width to mask to fill up the whole screen
		$('#mask').css({'width':maskWidth,'height':maskHeight});
		
		//transition effect		
		$('#mask').fadeTo("fast", 0.5);
		$('#mask').fadeIn(0);	
		
	
		//Get the window height and width
		var winH = $(window).height();
		var winW = $(window).width();
              
		//Set the popup window to center
		$(id).css('top',  winH/2-$(id).height()/2);
		$(id).css('left', winW/2-$(id).width()/2);
	
		//transition effect
		$(id).fadeIn(0); 
	
	});
	
	//if close button is clicked
	$('.window .close').click(function (e) {
		//Cancel the link behavior
		e.preventDefault();
		$('#mask, .window').hide();
	});		
	
	//if mask is clicked
	$('#mask').click(function () {
		$(this).hide();
		$('.window').hide();
	});			
	
});