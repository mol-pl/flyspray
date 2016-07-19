function showDiffOnHistory() {
	var result = document.querySelector("#history .history-diff")
	var tds = document.querySelectorAll("#history .history td")
	result.innerHTML = diffString(
	   tds[0].textContent,
	   tds[1].textContent
	)
		.replace(/\n\n+/g, '\n\n')	// replace multiple lines with "paragraph"
		.replace(/^\s+/g, '')		// remove whitespace
		.replace(/\s+$/g, '')		// remove trailing whitespace
	;
}