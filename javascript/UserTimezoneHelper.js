/**
	Timezone checker.
*/
function UserTimezoneHelper() {

	/**
		Validate server timezone with browser.
		
		Assumes browser is correct.
		
		@return Boolean|Number
			`true` -- jeśli czas jest zgodny.
			else -- strefę czasową przeglądarki (GMT+2 -> 2).
	*/
	this.isValid = function () {
		var serverTimezone = parseInt(document.body.getAttribute('data-userTimezone'));
		if (isNaN(serverTimezone)) {
			return;
		}
		var browserTimezone = -(new Date()).getTimezoneOffset() / 60;
		
		if (browserTimezone != serverTimezone) {
			return browserTimezone;
		}
		return true;
	}
	
	/**
		Check and add user info.
	*/
	this.check = function() {
		var actualTimezone = this.isValid()
		if (actualTimezone === true) {
			return;
		}
		var container = document.querySelector('#menu-list');
		if (!container) {
			return;
		}
		var nel = document.createElement('li');
		var profileUrl = document.querySelector('#profilelink').href;
		nel.innerHTML = `<a href="${profileUrl}" class="profilelink" title="Strefa w przeglądarce: ${actualTimezone}">Popraw strefę czasową</a>`;
		container.appendChild(nel);
		
		var userRole = document.body.getAttribute('data-userRole');
		var container = document.querySelector('#intromessage');
		if (userRole === 'admin' && container) {
			var nel = document.createElement('div');
			nel.innerHTML = `
			<strong>Poprawa strefę dla wszystkich użytkowników</strong>
<pre>
UPDATE flyspray_users set time_zone = ${actualTimezone}
--WHERE time_zone IN (1,2)
</pre>
			`;
			container.insertBefore(nel, container.firstChild);
		}
	}
}

userTimezoneHelper = new UserTimezoneHelper();

jQuery(function(){
	userTimezoneHelper.check();
});
