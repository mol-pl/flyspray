(function($)
{
	/**
		Move related out of tab and below task details
	*/
	$(function()
	{
		$('#relatedtab').hide();
		$('#related').insertAfter( "#taskdetails" ).removeClass('tab').addClass('box').attr('style', 'border-color:#000; padding:1em; margin: 1em 0; width: 99.8%; box-sizing:border-box;');
		$('<strong />').prependTo('#related').prepend(jQuery('#relatedtab').text());
	});

	/**
		Toggle low level headers (show/hide).
	*/
	$(function()
	{
		// details parent
		let parentSelector = '#taskdetailsfull,.commenttext';
		let parents = document.querySelectorAll(parentSelector);
		if (!parents.length) {
			return;
		}
		document.querySelectorAll(`:is(${parentSelector}) h4`).forEach((toggle) => {
			toggle.classList.add('closed');
		});

		parents.forEach((parent) => {
			parent.addEventListener('click', function(event) {
				// Check if the clicked element is an <h4> inside #taskdetailsfull
				if (event.target.tagName === 'H4') {
					let nextDiv = event.target.nextElementSibling;
					// Toggle the next sibling div with class 'level4'
					if (nextDiv && nextDiv.classList.contains('level4')) {
						if (nextDiv.style.display === "none" || nextDiv.style.display === "") {
							nextDiv.style.display = "block";
							event.target.classList.add('open');
							event.target.classList.remove('closed');
						} else {
							nextDiv.style.display = "none";
							event.target.classList.add('closed');
							event.target.classList.remove('open');
						}
					}
				}
			});
		});
	});
	
	/**
		Hide task cells
	*/
	$(function()
	{
		var el = document.getElementById('taskfieldscell');
		if (el)
		{
			var nel = document.createElement('span');
			nel.innerHTML = '«';
			//nel.style.cssText = 'font-weight:bold; color: #369; float:left; position:relative; left:-.5em';
			nel.style.cssText = 'font-weight:bold; color: #369; float:left;';
			nel.onclick = function()
			{
				$('#taskfieldscell').toggle('fast', function()
				{
					nel.innerHTML = (this.style.display == 'none') ? '»' : '«';
				});
			}
			// add before description div
			var top = $(el).next('td').children("div")[0]
			$(top).before(nel);
		}
	});

	/**
		Remove extra chars from header
	*
	$(function()
	{
		try
		{
			// add button
			var top = $('#taskdetails #navigation').first();
			var header = $('#taskdetails h2.summary').first();
			var nel = document.createElement('span');
			nel.style = 'float:left;';
			nel.innerHTML = '<input style="padding:2px .5em 0" type="image" src="themes/Bluey/Broom_icon.svg.png" alt="(clean)" title="Usuń specjalne znaki z tytułu" />';
			top.after(nel);
			// button action
			nel.onclick = function()
			{
				// remove special chars
				var str = header.text()
				str = str.replace(/([\\\/:]|\.\.)/g, '');
				header.text(str);
				// hide button
				this.style = 'display:none;'
			}
		} catch (e) {};
	});
	/**/
	
	/**
		Auto-submit project change
	*/
	$(function()
	{
		// this seem to be needed for switch to work when in do=admin
		$('#projectselectorform').append('<input type="hidden" name="switch" value="1" />');
		
		// submit on change
		$('#projectselectorform *[name="project"]').change(function()
		{
			$('#projectselectorform').submit();
		});
		$('#projectselectorform *[name="switch"]').hide();
	});

	/**
		Check comment before sending.
	*/
	$(function()
	{
		var reRemover = /<notka_serwisowa>[\s\S]+?<\/notka_serwisowa>/g;
		var minLength = 2;
		$('#comments form').submit(function()
		{
			var value = $('#comment_text').val();
			if (value.search(reRemover) >= 0) {
				value = value.replace(reRemover, '').replace('\s+', '');
				if (value.length < minLength) {
					alert ("Notatki serwisowe dodawaj do ISTNIEJĄCYCH komentarzy.\nSzersze dyskusje możesz prowadzić w Bugz.");
					return false;
				}
			} else {
				value = value.replace('\s+', '');
				if (value.length < minLength) {
					alert ("Nie możesz dodać pustego komentarza.");
					return false;
				}
			}
		});
	});
	
})(jQuery)