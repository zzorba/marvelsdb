(function ui_decklist_search(ui, $) {


    ui.handle_checkbox_change = function handle_checkbox_change() {
    	$('#packs-on').text($('#allowed_packs').find('input[type="checkbox"]:checked').size());
    	$('#packs-off').text($('#allowed_packs').find('input[type="checkbox"]:not(:checked)').size());
    }

		ui.handle_select_collection_packs = function handle_select_collection_packs() {
			var collection = {};
			var no_collection = true;
			if (app.user.data && app.user.data.owned_packs) {
					var packs = app.user.data.owned_packs.split(',');
					_.forEach(packs, function(str) {
							collection[str] = str;
							no_collection = false;
					});
					//console.log(app.user.data.owned_packs, collection);
			}

			if (!no_collection) {
				$('#allowed_packs input').prop('checked', false);
				_.forEach(collection, function(str) {
					$('#collection-' + str).prop('checked', true);
				})
				ui.handle_checkbox_change()
			}
		}

    /**
     * @memberOf ui
     */
    ui.setup_typeahead = function setup_typeahead() {

    	function findMatches(q, cb) {
    		if(q.match(/^\w:/)) return;
    		var regexp = new RegExp(q, 'i');
    		cb(app.data.cards.find({name: regexp}));
    	}

    	$('#card').typeahead({
    		hint: true,
    		highlight: true,
    		minLength: 2
    	},{
    		name : 'cardnames',
    		source: findMatches,
			displayKey: 'name',
			templates: {
				suggestion: _.template('<div><strong class="fg-<%= faction_code%>"><%= name %></strong> (<%= type_name %>, <%= pack_name %> #<%= position %>)</div>')
			}
    	});


			$('#card').on('typeahead:selected typeahead:autocompleted', function(event, data) {
					var card = app.data.cards.find({
							code : data.code
					})[0];
					var line = $('<p'+
				' style="padding: 6px 5px;border-radius: 3px;border: 1px solid silver; background-color: #fff;"><button type="button" class="close" aria-hidden="true">&times;</button>'+
				'<input type="hidden" name="cards[]" value="'+card.code+'">'+
									'<strong class="fg-'+card.faction_code+
				'">' + card.name + '</strong> (' + card.pack_name + ' #' + card.position + ') </p>');
					line.on({
							click: function(event) { line.remove(); }
					});
					line.insertBefore($('#card'));
					$(event.target).typeahead('val', '');
			});

    }

	/**
	 * called when the DOM is loaded
	 * @memberOf ui
	 */
	ui.on_dom_loaded = function on_dom_loaded() {
        ui.setup_typeahead();
    	$('#allowed_packs').on('change', ui.handle_checkbox_change);
			$('#allowed_collection_packs').on('click', ui.handle_select_collection_packs);

    	$('#select_all').on('click', function (event) {
    		$('#allowed_packs').find('input[type="checkbox"]:not(:checked)').prop('checked', true);
    		ui.handle_checkbox_change();
    		return false;
    	});

    	$('#select_none').on('click', function (event) {
    		$('#allowed_packs').find('input[type="checkbox"]:checked').prop('checked', false);
    		ui.handle_checkbox_change();
    		return false;
    	});

			$('#decklist-quick-aspect').on('change', function (event) {
    		ui.update_url('aspect', event.currentTarget.value);
    		return false;
    	});
			$('#decklist-quick-hero').on('change', function (event) {
    		ui.update_url('hero', event.currentTarget.value);
    		return false;
    	});
			$('#decklist-quick-tag').on('change', function (event) {
    		ui.update_url('tag', event.currentTarget.value);
    		return false;
    	});
			$('#decklist-quick-sort').on('change', function (event) {
    		ui.update_url('sort', event.currentTarget.value);
    		return false;
    	});
			$('#decklist-quick-category').on('change', function (event) {
    		ui.update_url('category', event.currentTarget.value);
    		return false;
    	});
			$('#decklist-quick-collection').on('change', function (event) {
    		ui.update_url('collection', event.currentTarget.value);
    		return false;
    	});
			$('#toggle-advanced-decklist-search').on('click', function (event) {
    		if ($('.decklists-advanced-search-expanded').length > 0) {
					$('.decklists-advanced-search-expanded').removeClass('decklists-advanced-search-expanded');
					$('.decklists-advanced-search-toggle span').removeClass('fa-caret-up');
					$('.decklists-advanced-search-toggle span').addClass('fa-caret-down');
				} else {
					$('.decklists-advanced-search').addClass('decklists-advanced-search-expanded');
					$('.decklists-advanced-search-toggle span').removeClass('fa-caret-down');
					$('.decklists-advanced-search-toggle span').addClass('fa-caret-up');
				}
    	});
			$('#decklist-search-form').on('submit', function (event) {
    		$('#decklist-search-form input').each(function(index) {
					if ($(this) && !$(this).val()) {
						$(this).prop("disabled", true);
					}
				})
				$('#decklist-search-form select').each(function(index) {
					if ($(this) && !$(this).val()) {
						$(this).prop("disabled", true);
					}
				})
    	});
	};

	ui.update_url = function update_url(param, value) {
		if ('URLSearchParams' in window) {
			var searchParams = new URLSearchParams(window.location.search);
			if (value) {
				searchParams.set(param, value);
			} else {
				searchParams.delete(param);
			}
			window.location.search = searchParams.toString();
		}
	}

	/**
	 * called when the app data is loaded
	 * @memberOf ui
	 */
	ui.on_data_loaded = function on_data_loaded() {
        function findMatches(q, cb) {
    		if(q.match(/^\w:/)) return;
    		var matches = app.data.cards({name: {likenocase: q}}).map(function (record) {
    			return { value: record.name };
    		});
    		cb(matches);
    	}

    	$('#card').typeahead({
    		  hint: true,
    		  highlight: true,
    		  minLength: 3
    		},{
    		name : 'cardnames',
    		displayKey: 'value',
    		source: findMatches,
			templates: {
				suggestion: _.template('<div><strong>{{value}}</strong></div>')
			}
    	});
	};

	/**
	 * called when both the DOM and the data app have finished loading
	 * @memberOf ui
	 */
	ui.on_all_loaded = function on_all_loaded() {

	};

})(app.ui, jQuery);
