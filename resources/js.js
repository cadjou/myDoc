(function () {
	'use strict';
	feather.replace();
	$('.doc').click(function(e){
		e.preventDefault();
		$('iframe[name="showContent"]').prop('src', this.href).show();
	});
	$('.navbar-brand').click(function(e){
		e.preventDefault();
		$('iframe[name="showContent"]').prop('src', '').show();
	});
})()