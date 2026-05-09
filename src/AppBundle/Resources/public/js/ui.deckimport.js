(function ui_deckimport(ui, $) {

var name_regexp;

ui.on_content_change = function on_content_change(event) {
	var text = $(content).val(),
		slots = {},
		hero_code,
		hero_name;

	text.match(name_regexp).forEach(function (token) {
		var qty = 1, name = token.trim(), card, pack_name = '', query;
		if(token[0] === '(') {
			return;
		}
		if(name.match(/^(\d+)x\s+(.+)$/)) {
			qty = parseInt(RegExp.$1, 10);
			name = RegExp.$2.trim();
		}

		// Separate check for pack_name in parentheses
		if(name.match(/^(.+?)\s*\(([^)]+)\)$/)) {
			name = RegExp.$1.trim();
			pack_name = RegExp.$2.trim();
		}

		console.log(name, pack_name);

		// assume the first thing we find is gonna be a hero
		if (!hero_code) {
			query = { name: name, type_code: 'hero' };
			if(pack_name) {
				query.pack_name = pack_name;
			}
			if(card = app.data.cards.findOne(query)) {
				hero_code = card.code;
				hero_name = card.name;
			}
		} else {
			// Parse title and subtitle from format "Title: Subtitle"
			var title = name, subtitle = '';
			if(name.match(/^(.+?)\s*:\s*(.+)$/)) {
				title = RegExp.$1.trim();
				subtitle = RegExp.$2.trim();
			}

			// first check with title and subtitle if subtitle exists
			if(subtitle) {
				query = { name: title, subtitle: subtitle, type_code: { $ne: 'hero' } };
				if(pack_name) {
					query.pack_name = pack_name;
				}
				card = app.data.cards.findOne(query);
			}

			// if not found, check just the name
			if(!card) {
				query = { name: name, type_code: { $ne: 'hero' } };
				if(pack_name) {
					query.pack_name = pack_name;
				}
				card = app.data.cards.findOne(query);
			}

			if(card) {
				slots[card.code] = qty;
			}
		}
	})

	if (!hero_code){
		window.alert("Unable to locate hero");
		return;
	}

	app.deck.init({
		hero_code: hero_code,
		hero_name: hero_name,
		slots: slots
	});
	app.deck.display('#deck');
	$('input[name=content]').val(app.deck.get_json());
	$('input[name=faction_code]').val(hero_code);
}

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	$('#content').change(ui.on_content_change);
};

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {
	var characters = _.unique(_.pluck(app.data.cards.find(), 'name').join('').split('').sort()).join('');
	name_regexp = new RegExp('\\(?[\\d' + characters.replace(/[[\](){}?*+^$\\.|]/g, '\\$&') + ']+\\)?', 'g');
};

/**
 * called when both the DOM and the data app have finished loading
 * @memberOf ui
 */
ui.on_all_loaded = function on_all_loaded() {
};


})(app.ui, jQuery);
