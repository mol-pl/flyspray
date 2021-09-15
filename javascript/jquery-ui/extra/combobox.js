/**
 * Combobx widget.
 * 
 * Source:
 * https://jqueryui.com/autocomplete/#combobox
 * 
 * Note! Requires extra styles.
 */
(function ($) {
	$.widget("custom.combobox", {
		options: {
			/**
			 * Formatter for values in the autocomplete menu.
			 * 
			 * The function will take `item` parameter (note that the item object is built in `_source`).
			 * Must return html.
			 */
			formatter: null,

			/**
			 * Extra classes to add to the menu.
			 * 
			 * E.g. property: 'ui-autocomplete' : 'my-class-blah', to `my-class-blah` to the menu.
			 * See: https://api.jqueryui.com/autocomplete/#option-classes
			 */
			classes: {},
		},

		/**
		 * Refresh value from select.
		 */
		refresh: function () {
			var selected = this.element.children(":selected"),
				value = selected.val() ? selected.text() : "";

			this.input.val(value);
		},

		_create: function () {
			this.wrapper = $("<span>")
				.addClass("custom-combobox")
				.insertAfter(this.element);

			this.element.hide();
			this._createAutocomplete();
			this._createShowAllButton();
		},

		/**
		 * Render single menu item.
		 */
		_renderItem: function( ul, item ) {
			if (typeof this.options.formatter === 'function') {
				var html = this.options.formatter(item);
				var $li = $( "<li>" )
					.append( "<div>"+html+"</div>" )
			} else {
				var $li = $( "<li>" )
					.append( $("<div>").text(item.label) )
			}
			if (item.selected) {
				$li.addClass('ui-selected');
			}
			return $li.appendTo( ul );
		},

		_createAutocomplete: function () {
			var selected = this.element.children(":selected"),
				value = selected.val() ? selected.text() : "";

			if (typeof this.options.classes['ui-autocomplete'] == 'string') {
				this.options.classes['ui-autocomplete'] = 'custom-combobox-menu ' + this.options.classes['ui-autocomplete'];
			} else {
				this.options.classes['ui-autocomplete'] = 'custom-combobox-menu';
			}

			this.input = $("<input>")
				.appendTo(this.wrapper)
				.val(value)
				.attr("title", "")
				.prop("required", true)
				.addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
				.autocomplete({
					delay: 0,
					minLength: 0,
					classes: this.options.classes,
					source: $.proxy(this, "_source")
				})
				.tooltip({
					classes: {
						"ui-tooltip": "ui-state-highlight"
					}
				})
			;

			this.input.autocomplete( "instance" )._renderItem = $.proxy(this, "_renderItem");

			this._on(this.input, {
				autocompleteselect: function (event, ui) {
					ui.item.option.selected = true;
					this._trigger("select", event, {
						item: ui.item.option
					});
				},

				autocompletechange: "_removeIfInvalid"
			});
		},

		_createShowAllButton: function () {
			var input = this.input,
				wasOpen = false;

			$("<a>")
				.attr("tabIndex", -1)
				.attr("title", "Show All Items")
				.tooltip()
				.appendTo(this.wrapper)
				.button({
					icons: {
						primary: "ui-icon-triangle-1-s"
					},
					text: false
				})
				.removeClass("ui-corner-all")
				.addClass("custom-combobox-toggle ui-corner-right")
				.on("mousedown", function () {
					wasOpen = input.autocomplete("widget").is(":visible");
				})
				.on("click", function () {
					input.trigger("focus");

					// Close if already visible
					if (wasOpen) {
						return;
					}

					// Pass empty string as value to search for, displaying all results
					input.autocomplete("search", "");
				});
		},

		_source: function (request, response) {
			var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
			response(this.element.children("option").map(function () {
				var text = $(this).text();
				if (this.value && (!request.term || matcher.test(text)))
					return {
						label: text,
						value: text,
						selected: this.selected ? true : false,
						option: this
					};
			}));
		},

		_removeIfInvalid: function (event, ui) {

			// Selected an item, nothing to do
			if (ui.item) {
				return;
			}

			// Search for a match (case-insensitive)
			var value = this.input.val(),
				valueLowerCase = value.toLowerCase(),
				valid = false;
			this.element.children("option").each(function () {
				if ($(this).text().toLowerCase() === valueLowerCase) {
					this.selected = valid = true;
					return false;
				}
			});

			// Found a match, nothing to do
			if (valid) {
				return;
			}

			// Remove invalid value
			this.input
				.val("")
				.attr("title", value + " didn't match any item")
				.tooltip("open");
			this.element.val("");
			this._delay(function () {
				this.input.tooltip("close").attr("title", "");
			}, 2500);
			this.input.autocomplete("instance").term = "";
		},

		_destroy: function () {
			this.wrapper.remove();
			this.element.show();
		}
	});
})(jQuery);