function core52_compositeform_init(theForm, submit_callback) {
	
	$(theForm).bind('submit', function(e) {
		
		var target = e.target;
		var ajax = false;
		
		if(target.is_validated) {
			return;
		}
		
		e.stopPropagation();
		e.preventDefault();
		/*
		// if each field has already been validated, don't validate again
		var fields_not_validated = $('input:not(.noscript),select:not(.noscript),textarea:not(.noscript)', $(target))
			.filter(':not(:submit)')
			.filter(':not([is_validated="true"])')
			.length;
		
		console.log(fields_not_validated);
		if(fields_not_validated == 0) {
			
			core52_compositeform_doSubmit(target);
			
		} else {
		*/
		
		
		
			// validate the entire form, recieves JSON data back
			$.post(
				target.action,
				$(target).serialize() + '&__validate=all', 
				function(data) {
					
					ajax = true;
					
					// clear all errors 
                	$(target).find('span.error').remove();
                	$(target).find('input.error').removeClass('error').attr('is_validated', 'true');
										
					// don't proceed if errors found
					if(core52_compositeform_getProperties(data).length > 0) {
						
						var first_row = false;
						for(var cform in data) {
							for(var row in data[cform]) {
								var r = cform+'['+row+']';
								if(target[r] == undefined && target[r+'[]'] != undefined) {
									r += '[]';
								}
								var current_row = target[r];
								if(!first_row) first_row = current_row;
								core52_compositeform_field_callback(current_row, data[cform][row]);
							}
						}
						$(first_row).focus().select();
					
						//alert('The form is not complete, please fix any errors and try again.');
						return false;
						
					} else {
						
						// Submit the form, or fire our submit callback 
						if (submit_callback) { 
							submit_callback(target);
						} else { 	
							core52_compositeform_doSubmit(target);
						}
					}
					
				},
				'json'
			);
			
		/*}*/

	});
	
	
	// if a form field value changes, cause the validator to refocus on
	// the field if a validation error occurs
	$('input:not(.noscript),select:not(.noscript),textarea:not(.noscript)', theForm).filter(':not(:submit)').bind('change', function(e) {
		var target = e.target;
		target.is_focused = false;
	});
	
}


function core52_compositeform_init_autosubmit(theForm, submitBtn) {
	var form_state = $(theForm).serialize();
	var saved_form_state = form_state;
	var formSubmitTimeout = null;
	
	core52_compositeform_init(theForm, function() {
		
		clearTimeout(formSubmitTimeout);
		
		form_state = $(theForm).serialize();
		var target = theForm[0];
		
		$(submitBtn).val('Saving...');
		
		// save
		$.post(
			$(theForm).attr('action'),
			form_state,
			function(data) {
				if(data.successful) {
					saved_form_state = form_state;
					$(submitBtn).val('Saved');
					setTimeout(function() { $(submitBtn).val('Save'); }, 3000);
				} else {
					var first_row = false;
					for(var cform in data) {
						for(var row in data.errors[cform]) {
							var r = cform+'['+row+']';
							if(!first_row) first_row = target[r];
							core52_compositeform_field_callback(target[r], data.errors[cform][row]);
						}
					}
					$(first_row).focus().select();
				}
			},
			'json'
		);
	});
	
	// alert if browsing away without having saved
	window.onbeforeunload = function (e) {
		if(form_state != saved_form_state) {
			var e = e || window.event;
			var str = 'You have unsaved data, are you sure you want to browse away from this page?';
			
			// For IE and Firefox prior to version 4
			if (e) {
				e.returnValue = str;
			}
		
			// For Safari
			return str;
		}
	};
	
	// note when the form changes
	$('input:not(.noscript),select:not(.noscript),textarea:not(.noscript)', theForm).filter(':not(:submit)').bind('change keyup', function(e) {
		// cancel any current timeout because the form may have changed again
		clearTimeout(formSubmitTimeout);
		$(submitBtn).val('Save');
		form_state = $(theForm).serialize();
		// if form is changed, submit it
		if(form_state != saved_form_state) {
			formSubmitTimeout = setTimeout(function() {
				$(theForm).submit();
			}, 1500);
		}
	});
	
}


function core52_compositeform_getProperties(obj) {
	var i, v;
	var count = 0;
	var props = [];
	if (typeof(obj) === 'object') {
		for (i in obj) {
			v = obj[i];
			if (v !== undefined && typeof(v) !== 'function') {
				props[count] = i;
				count++;
			}
		}
	}
	return props;
};


function core52_compositeform_doSubmit(target) {
	$(target).unbind();
	if($('input:submit', $(target)).length == 0) {
		$(target).append('<input type="submit" name="submit" value="submit" style="display:none;" />');
	}
	$('input:submit', $(target)).eq(0).attr('value', 'Please wait...').click();
}


function core52_compositeform_field_callback(target, err) {
	
	if(typeof(target.name) == 'undefined') {
		target = $.makeArray(target).pop();
	}
	
	// don't process fields with the 'noscript' class set
	if($(target).hasClass('noscript')) return;
	
	// got an error?
	var target_name_normalized = target.name.replace(/\[|\]/g, '_');
	if(typeof err !== 'undefined' && err.length > 0) {
		
		if($(target).siblings('.error.'+target_name_normalized).length == 0) {
			// prepend error element if none exists
			$(target).parent().append($(err).addClass(target_name_normalized));
		} else {
			// show the error
			$(err).addClass(target_name_normalized);
			$(target).siblings('.error.'+target_name_normalized).replaceWith($(err).addClass(target_name_normalized));
		}
		
		$(target).addClass('error');
		
		// focus the field without being evil :P
		if(!target.is_focused) {
			$(target).focus().select();
			target.is_focused = true;
		} else {
			target.is_focused = false;
		}
		
		$(target).attr('is_validated', 'false');
		
	} else {
		
		// clear error message and class
		$(target).siblings('.error.'+target_name_normalized).remove();
		$(target).removeClass('error');
		target.is_focused = false;
		$(target).attr('is_validated', 'true');
		
	}
}

