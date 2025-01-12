let cf7s_modal = ( show = true ) => {
	if(show) {
		jQuery('#cf7-submissions-modal').show();
	}
	else {
		jQuery('#cf7-submissions-modal').hide();
	}
}

jQuery(function($){

	$('.chosen').chosen();
	
	$('.cf7s-delete').click(function(e){
		if(!confirm(CF7S.confirm)) {
			e.preventDefault();
		}
	});
	
	$('#cf7s-list-form').submit(function(e){
		if( $('#bulk-action-selector-bottom').val() == 'delete' && !confirm(CF7S.confirm_all)) {
			e.preventDefault();
		}
	});

	$('#wpcf7-contact-form-list-table table.widefat tr > th > input[type=checkbox]').each(function() {
		var input = $(this);
		var id = input.val();
		input.closest('th').siblings('.column-submissions').html(CF7S.submissions[id]);
	});

	$('#cf7s-contact-form').submit(function(e){
		e.preventDefault();

		let $form = $(this);
		cf7s_modal();

		$.ajax({
			url: CF7S.ajaxurl,
			data: $form.serialize(),
			type: 'POST',
			dataType: 'JSON',
			success: function(resp) {
				$('#cf7s-contact-msg').text(resp.data.message);
				cf7s_modal(false);
			},
			error: function(err) {
				$('#cf7s-contact-msg').text(err.data.message);
				cf7s_modal(false);
			}
		});
	});
})