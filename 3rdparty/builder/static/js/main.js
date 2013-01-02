$(function() {
	//jQuery.ajaxSetup({async:false});

   $('#projects, #functions').tooltip({
      selector: "span[rel=tooltip]"
    });

	// Projects 
	$('#projects').first().each(function() {

		$(this).find('tr').each(function() {
			var $tr = $(this);

			$.get('/projects/up_to_date:' + $(this).find('.name a').text(), function(data) {

				if (data.up_to_date) { 
					$tr.find('.revision span').addClass('badge-success').attr('data-original-title', 'up to date');
				} else {
					$tr.find('.revision span').addClass('badge-warning').attr('data-original-title', 'needs update');
				}

			}, 'json');
		});

	});

});
