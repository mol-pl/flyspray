/*
	Large tables collapser.
	
	ES6 code (classes etc).
*/

/**
	Helper for collapsing rows.
*/
var TableCollapser = class {
    /**
  	 * Create new section.
     * Note! Add child rows to be able to collapse.
     */
    constructor(table, trHead) {
        this.table = table;
        this.headerRow = trHead;
        this.childRows = [];
        this.isCollapsed = false;
    }
    size() {
        return this.childRows.length;
    }
    /**
     * Add child row.
     */
    add(tr) {
        if (this.headerRow === tr) {
            console.warn('tried to add self, skipped', tr);
            return false;
        }
        this.childRows.push(tr);
        if (tr.style.display === 'none') {
            this.isCollapsed = true;
        }
        return true;
    }

    /**
     * Hide/show/toggle children.
     */
    hide() {
        this.childRows.forEach((row) => {
            row.style.display = 'none';
        });
        this.isCollapsed = true;
    }
    show() {
        this.childRows.forEach((row) => {
            row.style.display = '';
        });
        this.isCollapsed = false;
    }
    toggle() {
        if (this.isCollapsed) {
            this.show();
        } else {
            this.hide();
        }
    }
}
