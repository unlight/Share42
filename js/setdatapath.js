jQuery(function(){
	var share42Path = gdn.definition('Share42Path');
	$('div.share42init').each(function(){
		var $this = $(this);
		if (!$this.attr('data-path')) {
			$this.attr('data-path', share42Path);
		}
	});
});