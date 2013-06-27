$(function(){
});

window.efPWHLShow = function(link)
{
	 sajax_do_call(
		'efAjaxWLHList',
		[wgPageName],
		function(request)
		{
			if (request.status != 200) return;
			var s = document.getElementById('popup_whatlinkshere_ajax');
			s.className = 'shown';
			s.innerHTML = request.responseText;
			$(s).find('a')
				.click(function() {
					if ($(s).hasClass('shown'))
					{
						$(s).find('div.inner').css({'display' : 'none'});
						$(s).removeClass('shown');
					}
					else
					{
						$(s).find('div.inner').css({'display' : 'block'});
						$(s).addClass('shown');
					}
				});
		}
	);
};
