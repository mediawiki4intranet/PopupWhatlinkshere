window.efPWHLShow = function(link)
{
	var closedText = link.innerHTML;
	var openText;
	$.ajax({
		url: mw.util.wikiScript()+'?action=ajax',
		type: 'POST',
		data: {
			rs: 'efAjaxWLHList',
			rsargs: [ mw.config.get('wgPageName') ]
		},
		success: function(result)
		{
			var s = document.getElementById('popup_whatlinkshere_ajax');
			s.className = 'like-cl-outer';
			s.innerHTML = result;
			s = $(s);
			var d = s.find('div.inner')[0];
			s.find('a:first').click(function() {
				openText = openText || this.innerHTML;
				var open = d.style.display != 'none';
				this.innerHTML = open ? closedText : openText;
				d.style.display = open ? 'none' : 'block';
			});
		}
	});
};
