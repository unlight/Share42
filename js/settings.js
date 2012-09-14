jQuery(function($){
	$('input[name=Panel]').change(function(){
		if ($(this).val() == 'floating' && this.checked) {
			$('#Form_Limit').removeAttr('disabled').parents('li').show();
			$('#Form_VerticalPlace').removeAttr('disabled').parents('li').show();
			$('#Form_HorizontalPlace').attr('disabled', 'disabled').parents('li').hide();
		} else {
			$('#Form_Limit').attr('disabled', 'disabled').parents('li').hide();
			$('#Form_VerticalPlace').attr('disabled', 'disabled').parents('li').hide();
			$('#Form_HorizontalPlace').removeAttr('disabled').parents('li').show();
		}
	}).change();

});