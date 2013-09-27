window.efPWHLShow = function(link)
{
	var closedText = link.innerHTML;
	var openText;
	sajax_do_call(
		'efAjaxWLHList',
		[wgPageName],
		function(request)
		{
			if (request.status != 200)
				return;
			var s = document.getElementById('popup_whatlinkshere_ajax');
			s.className = 'like-cl-outer';
			s.innerHTML = request.responseText;
			s = $(s);
			var d = s.find('div.inner')[0];
			var c = s.find('a')[0].innerHTML;
			s.find('a').click(function() {
				openText = openText || this.innerHTML;
				var open = d.style.display != 'none';
				this.innerHTML = open ? closedText : openText;
				d.style.display = open ? 'none' : 'block';
			});
		}
	);
};
