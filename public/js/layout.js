$(document).ready(function() {	
	
	//select all the a tag with name equal to modal
	$(document).on('click', 'a[name=modal],button[name=modal]', function(e) {
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
	/*$('#mask').click(function () {
		$(this).hide();
		$('.window').hide();
	});*/	
	
	//placeholders
	$(document).on('blur change DOMAutoComplete AutoComplete focus keydown keyup', "input[type='text'], input[type='password'], textarea, select",
	    function(event) {
			var label = $("label[for='" + $(this).attr('id') + "']");
			if ($(this).val() == '' && event.type != 'keydown') {
				label.fadeTo("fast", $(this).is(":focus") || event.type == 'focusin' ? 0.5 : 1);
			} else {
				label.css("display", "none");
			}
		}
	);
	
});

function MainLayout()
{
    this.setFocus = function(container) {
    	if (typeof container == 'undefined') {
    		container = document;
    	}
        $(container).find("input.fieldCheck.fieldError").first().focus().length
        	|| $(container).find("input").first().focus();
    };
    
    this.dialogLoaded = function(container) {
    	FieldCheck.initAll();
    	this.setFocus(container);
    	$(container).submit(function(event) {
    	    event.preventDefault();
    	    var form = $(event.target);
    	    form.find('.button-text').hide();
    	    form.find('.button-wait').show();
    	    $.post(form.attr("action"), form.serialize(),
    	        function(data) {
    	            if (data.status == 'error') {
    	                form.find('.button-wait').hide();
    	                form.find('.button-text').show();
    	                if (typeof data.hash != 'undefined') {
    	                    form.find('input[name="hash"]').val(data.hash);
    	                }
    	                $(".dialogMessage").html(data.message);
    	                $(".dialogMessageBorder").css("width", $(".dialogMessage").css("width")).css("height", $(".dialogMessage").css("height"));
    	                $(".dialogMessageBorder").fadeTo(0, 1).fadeTo(600, 0);
    	                ML.setFocus(form);
    	            }
    	            if (data.status == 'ok') {
    	            	$(".dialogMessage").html(data.message);
    	            	form.attr("action", data.action);
    	            	form.html(data.content);
    	            	FieldCheck.initAll();
    	            	ML.setFocus(form);
    	            }
    	            if (data.status == 'redirect') {
    	                document.location = data.url;
    	            }
    	        }, "json");
    	});
    };
}

var ML = new MainLayout();

