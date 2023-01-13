/*
	Large tables collapser for intro message.
	
	ES6 code (classes etc).
*/
/**
 * Init collapser section.
 *
 * @param tr Section header row.
 * @param tr_next Next section header.
 */
function initCollapser(tr, tr_next) {
    var table = tr.parentNode.parentNode;
    var rows = table.querySelectorAll('tr');
    var foundMyself = false;
    var tableCollapser = new TableCollapser(table, tr);

    // init collapser children
    for (var r = 0; r < rows.length; r++) {
        var row = rows[r];

        // find current section header
        if (!foundMyself) {
            if (row !== tr) {
                //console.log('skip: ', row);
            } else {
                //console.log('got section row: ', row);
                foundMyself = true;
            }
            continue;
        }

        // add rows until next section
        if (row === tr_next) {
            //console.log('done: ', row);
            break;
        } else {
            //console.log('to hide: ', row);
            tableCollapser.add(row);
        }
    }
    //break;

    return tableCollapser;
}

/**
  Init tableCollapseres in the container.
  
  Note! This assumes section headers are spanned 2 columns.
  See `ths` selector.
  
  @param selector Container selector (only 1st is used).
*/
function initCollapsers(selector) {
	const i18nToggleLabel = 'show/hide';
	
    var container = document.querySelector(selector);
    console.log('[initCollapsers] container:', selector, container);
    if (!container) {
        return;
    }

    // collecion of sections that can be collapsed
    var tableCollapsers = [];

    var ths = container.querySelectorAll('th[colspan="2"]');
    //console.log(ths);
    for (var i = 0; i < ths.length; i++) {
        var th = ths[i];
        var tr = th.parentNode;
        var tr_next = (i+1 < ths.length) ? ths[i+1].parentNode : null;

        // init collapser section
        let tableCollapser = initCollapser(tr, tr_next);
        tableCollapsers.push(tableCollapser);

        // default state
		// hide if not small nor important => hide (collapse)
        if (tableCollapser.size() > 2 && !isImportantProjectGroup(tableCollapser)) {
            tableCollapser.hide();
        }

        // trigger button
        var btn = document.createElement('button');
        btn.innerHTML = i18nToggleLabel;
        btn.style.cssText = `
        float:right;
        `;
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            tableCollapser.toggle();
        });
        th.appendChild(btn);
    }
}

/**
	Figure out if the section is a important group.
	
	Assumes there is an exclamation mark in the header (an icon or emoji).
	
	@param {TableCollapser} section
*/
function isImportantProjectGroup(section) {
	// icon
	let img = section.headerRow.querySelector('img');
	if (img) {
		let alt = img.getAttribute('alt');
		if (alt && alt.indexOf('!')>=0) {
			return true;
		}
	}
	// emoji or text
	let text = section.headerRow.textContent;
	if (text.search(/(\(!\)|❗)/) >= 0) {
		return true;
	}

	return false;
}

//
// Init (add after intro message or in onready event)
//
//initCollapsers('#intromessage')