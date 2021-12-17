(function ui_decklist_search(ui, $) {


    ui.handle_checkbox_change = function handle_checkbox_change() {
    	$('#packs-on').text($('#allowed_packs').find('input[type="checkbox"]:checked').size());
    	$('#packs-off').text($('#allowed_packs').find('input[type="checkbox"]:not(:checked)').size());
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
					' style="padding: 3px 5px;border-radius: 3px;border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button>'+
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
	};

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
