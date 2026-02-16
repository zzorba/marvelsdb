(function app_deck(deck, $) {

var date_creation,
	date_update,
	description_md,
	id,
	name,
	tags,
	meta,
	choices,
	xp,
	xp_spent = 0,
	exile_string = "",
	exiles = [],
	hero_code,
	hero_name,
	hero,
	hero_special_cards = [],
	deck_options,
	unsaved,
	user_id,
	sort_type = "default",
	sort_dir = 1,
	problem_list = [],
	no_collection = true,
	collection = {},
	problem_labels = {
		too_few_cards: "Contains too few cards",
		too_many_cards: "Contains too many cards",
		invalid_cards: "Contains invalid cards",
		hero: "Doesn't comply with the hero requirements"
	},
	header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'),
	card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> icon-<%= card.faction_code %>"></span><% if (typeof(card.faction2_code) !== "undefined") { %><span class="icon icon-<%= card.faction2_code %>"></span> <% } %> <a href="<%= card.url %>" class="card card-tip <% if (typeof(card.faction2_code) !== "undefined") { %> fg-dual <% } %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>'),
	layouts = {},
	layout_data = {};


/*
 * Templates for the different deck layouts, see deck.get_layout_data
 */
// one column view
layouts[1] = _.template(`
	<div class="deck-block" style="background-image: linear-gradient(100deg, <%= hero_color_1 %> 49.5%, <%= hero_color_3 %> 50%, <%= hero_color_3 %> 51%, <%= hero_color_2 %> 51.5%, <%= hero_color_2 %> 100%);">
		<div class="deck-header">
			<div class="deck-meta"><%= meta %></div>
			<div class="deck-hero-image"><%= image1 %><%= image2 %></div>
		</div>
		<div class="deck-content">
			<div class="col-sm-10 col-print-10"><%= cards %></div>
		</div>
		<div class="special-hero-sets"><%= special_sets %></div>
	</div>
`);
// two column view (default for most)
layouts[2] = _.template(`
	<div class="deck-block" style="background-image: linear-gradient(100deg, <%= hero_color_1 %> 49.5%, <%= hero_color_3 %> 50%, <%= hero_color_3 %> 51%, <%= hero_color_2 %> 51.5%, <%= hero_color_2 %> 100%);">
		<div class="deck-header">
			<div class="deck-meta"><%= meta %></div>
			<div class="deck-hero-image"><%= image1 %><%= image2 %></div>
		</div>
		<div class="deck-content">
			<div><%= allies %><%= events %><%= player_side_schemes %><%= resources %></div>
			<div><%= supports %><%= upgrades %> <%= permanent %></div>
		</div>
		<div class="special-hero-sets"><%= special_sets %></div>
	</div>
`);

/**
 * @memberOf deck
 */
deck.init = function init(data) {
	date_creation = data.date_creation;
	date_update = data.date_update;
	description_md = data.description_md;
	id = data.id;
	name = data.name;
	tags = data.tags;
	meta = data.meta;
	choices = [];
	hero_code = data.hero_code;
	hero_name = data.hero_name;
	hero = false;
	hero_special_cards = [];
	unsaved = data.unsaved;
	user_id = data.user_id;
	exile_string = data.exile_string;
	if (exile_string){
		exiles = exile_string.split(",");
	}
	xp = data.xp;
	xp_adjustment = data.xp_adjustment;
	next_deck = data.next_deck;
	previous_deck = data.previous_deck;
	if (localStorage && localStorage.getItem('ui.deck.sort')) {
		deck.sort_type = localStorage.getItem('ui.deck.sort');
	}
	deck.choices = [];
	// parse pack owner string
	collection = {};
	no_collection = true;

	if(app.data.isLoaded) {
		deck.onloaded(data);
	} else {
		$(document).on('data.app', function () {
			deck.onloaded(data);
		});
	}
}

deck.onloaded = function(data){
	deck.set_slots(data.slots, data.ignoreDeckLimitSlots);
	deck.hero = app.data.cards.findById(hero_code);

	deck.requirements = {};
	if (deck.hero) {
		// if the hero has deck requirements, load them into the requirements here
		if (deck.hero.deck_requirements) {
			deck.hero.deck_requirements.forEach(function (req) {
				if (req.aspects) {
					// the user must pick X aspects, only applied if this is not just set to 1
					deck.requirements.aspects = req.aspects;
				}
				if (req.limit) {
					// the user must pick X aspects, only applied if this is not just set to 1
					deck.requirements.limit = req.limit;
				}
			})
		}
		if (deck.hero.deck_options && deck.hero.deck_options.length) {
			deck.deck_options = deck.hero.deck_options;
		}

		// Search for hero_special sets where the parent code belongs to the hero's set.
		// Each of the cards in those sets are special hero cards that aren't in the traditional deck
		// but should be displayed in a separate section to help aid with deckbuilding decisions.
		var special_cards_query = { card_set_parent_code: deck.hero.card_set_code, card_set_type_name_code: 'hero_special' };
		var special_cards_options = { '$orderBy': { code: 1 } };
		hero_special_cards =  app.data.cards.find(special_cards_query, special_cards_options);
	}

	if (data.meta){
		deck.meta = JSON.parse(data.meta);
	}
	if (!deck.meta){
		deck.meta = {};
	}

	if (app.user.data && app.user.data.owned_packs) {
		var packs = app.user.data.owned_packs.split(',');
		_.forEach(packs, function(str) {
			collection[str] = 1;
			no_collection = false;
		});
	}
}

/**
 * Sets the slots of the deck
 * @memberOf deck
 */
deck.set_slots = function set_slots(slots, ignoreSlots) {
	app.data.cards.update({}, {
		indeck: 0,
		ignore: 0
	});

	for(code in slots) {
		if(slots.hasOwnProperty(code)) {
			app.data.cards.updateById(code, {indeck: slots[code]});
		}
	}
	for(code in ignoreSlots) {
		if(ignoreSlots.hasOwnProperty(code)) {
			app.data.cards.updateById(code, {ignore: ignoreSlots[code]});
		}
	}
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_id = function get_id() {
	return id;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_name = function get_name() {
	return name;
}


/**
 * @memberOf deck
 * @returns string
 */
deck.get_tags = function get_tags() {
	return tags;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_next_deck = function get_next_deck() {
	return next_deck;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_previous_deck = function get_previous_deck() {
	return previous_deck;
}


/**
 * @memberOf deck
 * @returns integer
 */
deck.get_xp = function get_xp() {
	if (xp_adjustment) {
		return xp + xp_adjustment;

	} else {
		return xp;
	}
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_xp_spent = function get_xp_spent() {
	return xp_spent;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.set_xp_spent = function set_xp_spent(spent_xp) {
	xp_spent = spent_xp;
}


/**
 * @memberOf deck
 * @returns integer
 */
deck.get_xp_adjustment = function get_xp_adjustment() {
	if (!xp_adjustment) {
		xp_adjustment = 0;
	}
	return xp_adjustment;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.set_xp_adjustment = function set_xp_adjustment(xp_adj) {
	if (!xp_adjustment) {
		xp_adjustment = 0;
	}

	xp_adjustment = xp_adj;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_hero_code = function get_hero_code() {
	return hero_code;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_exiles = function get_exiles() {
	return exiles;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_exile_string = function get_exile_string() {
	return exile_string;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_description_md = function get_description_md() {
	return description_md;
}

/**
 * @memberOf deck
 */
deck.get_cards = function get_cards(sort, query, group) {
	sort = sort || {};
	sort['code'] = 1;

	query = query || {};
	query.indeck = {
		'$gt': 0
	};

	var options = {
		'$orderBy': sort
	};
	if (group){
		options.$groupBy = group;
	}

	return app.data.cards.find(query, options);
}

/**
 * @memberOf deck
 */
deck.get_draw_deck = function get_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : []
		},
		permanent: false
	});
}

/**
 * @memberOf deck
 */
deck.get_real_draw_deck = function get_real_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : []
		}
	});
}

/**
 * @memberOf deck
 * get the actual deck used in the game, which excludes permanents
 */
deck.get_physical_draw_deck = function get_physical_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : []
		},
		permanent: false
	});
}

/**
 * @memberOf deck
 */
deck.get_draw_deck_size = function get_draw_deck_size(sort) {
	var draw_deck = deck.get_draw_deck();
	return deck.get_nb_cards(draw_deck);
}

/**
 * @memberOf deck
 */
deck.get_real_draw_deck_size = function get_real_draw_deck_size(sort) {
	var draw_deck = deck.get_real_draw_deck();
	return deck.get_nb_cards(draw_deck);
}

/**
 * @memberOf deck
 */
deck.get_xp_usage = function get_xp_usage(sort) {
	var xp = 0;
	deck.get_real_draw_deck().forEach(function (card) {
		if (card && (card.xp) && card.ignore < card.indeck) {
			xp += (card.xp) * (card.indeck - card.ignore) * (card.exceptional ? 2: 1);
		}
	});
	return xp;

}


deck.get_nb_cards = function get_nb_cards(cards) {
	if(!cards) cards = deck.get_cards();
	var quantities = _.pluck(cards, 'indeck');
	var ignores = _.pluck(cards, 'ignore');
	var total = _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
	total -= _.reduce(ignores, function(memo, num) { return memo + num; }, 0);
	return total;
}

deck.get_aspect_count = function get_aspect_count(aspect) {
	var cards = deck.get_cards({}, {
		faction_code: aspect
	});
	return deck.get_nb_cards(cards);
}

deck.check_limit = function check_limit(limit) {
	var cards = deck.get_cards();
	var invalid = 0;
	cards.forEach(function (card) {
		if (card.faction_code != 'hero' && card.indeck != limit) {
			invalid++;
		}
	})
	if (invalid > 0) {
		return false
	}
	return true
}



/**
 * @memberOf deck
 */
deck.get_included_packs = function get_included_packs() {
	var cards = deck.get_cards()

	// Set up a list of all of the packs that are the only pack where a card is available
	// and a list of arrays where there are a choice of pack to get a given card
	// for example Chase Them Down is in multiple packs, so each of those packs would appear together in an array in "packs_with_options"
	// and Angel is only in cyclops (as of feb 2023) so that would put 'cyclops' in packs_required
	var packs_required = []
	var packs_with_options = []
	cards.forEach(function(card){
		if(card.duplicated_by) {
			packs_with_options.push(
				[card.pack_code].concat(_.uniq(_.pluck(app.data.cards.find({'code': { '$in': card.duplicated_by}}), 'pack_code')))
			)
		} else if(card.duplicate_of_code) {
			packs_with_options.push(
				[card.pack_code].concat(_.uniq(_.pluck(app.data.cards.find({'code': { '$in': card.duplicated_of_code}}), 'pack_code')))
			)
		} else if(!packs_required.includes(card.pack_code)) {
			packs_required.push(card.pack_code)
		}
	})
	var pack_names = _.pluck(
		app.data.packs.find(
			{
				'code': {'$in': packs_required}
			},
			{
				'$orderby': {'available': 1}
			}
		),
		'name'
	)
	packs_with_options.forEach(function(pack_code_list) {
		//first, check if one of the options is already in the required list. If it is, we don't need to add this
		if(_.isEmpty(_.intersection(packs_required, pack_code_list))) {
			// make a list of all of the possible pack choices, separated by '/'
			option_string = _.pluck(
				app.data.packs.find(
					{
						'code': {'$in': pack_code_list}
					},
					{
						'$orderby': {'available': 1}
					}
				),
				'name'
			).join(' / ')

			// add it to our master list
			pack_names.push(option_string)
		}
	})
	return pack_names
}


deck.change_sort = function(sort_type){
	if (localStorage) {
		localStorage.setItem('ui.deck.sort', sort_type);
	}
	deck.sort_type = sort_type;
	if ($("#deck")){
		deck.display('#deck');
	}

	if ($("#deck-content")){
		deck.display('#deck-content');
	}

	if ($("#decklist")){
		deck.display('#decklist');
	}

}

deck.change_aspect = function(aspect){
	if (!deck.meta){
		deck.meta = {};
	}
	if (deck.meta && deck.meta.aspect2 && deck.meta.aspect2 == aspect) {
		return;
	}
	deck.meta.aspect = aspect;
	if ($("#deck")){
		deck.display('#deck');
	}

	if ($("#deck-content")){
		deck.display('#deck-content');
	}

	if ($("#decklist")){
		deck.display('#decklist');
	}
}
deck.change_aspect2 = function(aspect){
	if (!deck.meta){
		deck.meta = {};
	}
	if (deck.meta && deck.meta.aspect && deck.meta.aspect == aspect) {
		return;
	}
	deck.meta.aspect2 = aspect;
	if ($("#deck")){
		deck.display('#deck');
	}

	if ($("#deck-content")){
		deck.display('#deck-content');
	}

	if ($("#decklist")){
		deck.display('#decklist');
	}
}

/**
 * @memberOf deck
 */
deck.display = function display(container, options) {
	// XXX fetch the selected sort here
	// default is 2 it seems
	// before displaying a deck, apply the currently active taboo list

	options = _.extend({sort: 'type', cols: 2}, options);

	var deck_content = deck.get_layout_data(options);

	$(container)
		.removeClass('deck-loading')
		.empty();

	$(container).append(deck_content);
	if (app.deck_history){
		app.deck_history.setup('#history');
	}

}

deck.get_layout_data = function get_layout_data(options) {

	var data = {
		image1: '',
		image2: '',
		meta: '',
		upgrades: '',
		events: '',
		allies: '',
		permanent: '',
		player_side_schemes: '',
		supports: '',
		resources: '',
		cards: '',
		hero_color_1: '',
		hero_color_2: '',
		hero_color_3: '',
		special_sets: ''
	};

	var problem = deck.get_problem();
	$("input[name=problem]").val(problem);

	var hero = app.data.cards.findById(hero_code);
	var minDeckSize = 40;

	if (hero.meta && hero.meta.colors) {
		data['hero_color_1'] = hero.meta.colors[0];
		data['hero_color_2'] = hero.meta.colors[1];
		data['hero_color_3'] = hero.meta.colors[2];
	}
	var offset = "";
	if (hero.meta && hero.meta.offset) {
		offset = "background-position: "+hero.meta.offset+";";
	}
	deck.update_layout_section(data, 'image1', $('<div class="card-thumbnail-wide card-thumbnail-hero" style="'+offset+'background-image:url(/bundles/cards/'+hero.code+'.jpg)"></div>'));
	deck.update_layout_section(data, 'image2', $('<div class="card-thumbnail-wide card-thumbnail-hero" style="background-image:url(/bundles/cards/'+hero.linked_card.code+'.jpg)"></div>'));
	if (hero_name == hero.linked_card.name) {
		deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold"><a class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+hero_code+'">'+hero_name+'</a></h4>'));
	} else {
		deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold"><a class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+hero_code+'">'+hero_name+' ('+hero.linked_card.name+')</a></h4>'));
	}
	if (deck.requirements && deck.requirements.aspects && deck.requirements.aspects == 4){
		deck.update_layout_section(data, 'meta', $('<div>4 Aspects</div>'));
	} else {
		if (deck.meta && deck.meta.aspect) {
			deck.update_layout_section(data, 'meta', $('<div><span class="fa fa-circle fg-'+deck.meta.aspect+'" title="'+deck.meta.aspect+'"></span> '+deck.meta.aspect.charAt(0).toUpperCase() + deck.meta.aspect.slice(1)+' ('+deck.get_aspect_count(deck.meta.aspect)+')</div>'));
		}
		if (deck.meta && deck.meta.aspect2) {
			deck.update_layout_section(data, 'meta', $('<div><span class="fa fa-circle fg-'+deck.meta.aspect2+'" title="'+deck.meta.aspect2+'"></span> '+deck.meta.aspect2.charAt(0).toUpperCase() + deck.meta.aspect2.slice(1)+' ('+deck.get_aspect_count(deck.meta.aspect2)+')</div>'));
		}
		if (!deck.meta && !deck.meta.aspect && !deck.meta.aspect2) {
			deck.update_layout_section(data, 'meta', $('<div>No Aspect</div>'));
		}
	}

	deck.update_layout_section(data, 'meta', $('<div>'+deck.get_draw_deck_size()+' cards </div>').addClass(deck.get_draw_deck_size() < minDeckSize ? 'text-danger': ''));
	var packs = deck.get_included_packs();
	var pack_string = packs.join(', ');
	var pack_count = packs.length;
	deck.update_layout_section(data, 'meta', $('<div><span onclick="$(\'#packs_required\').toggle()" style="border-bottom: 1px dashed #cfcfcf;" title="' + pack_string + '">' + pack_count + ' packs required </span>' + ' <div style="display:none;" id="packs_required">'+pack_string+'</div> </div>'));
	if(deck.get_tags && deck.get_tags() ) {
		deck.update_layout_section(data, 'meta', $('<div>'+deck.get_tags().replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();})+'</div>'));
	}

	if(problem) {
		if (deck.problem_list && deck.problem_list.length > 0){
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+deck.problem_list.join(', ')+'</div>'));
		} else {
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
		}
	}

	var layout_template = 2;
	if (deck.sort_type == "name"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'name': 1}, null, null));
		layout_template = 1;
	} else if (deck.sort_type == "set"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'pack_code': 1, "name": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "settype"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'pack_code': 1, "type_code": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "setnumber"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'pack_code': 1, "position": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "faction"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "name":1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factionnumber"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "pack_code":1, "position": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factiontype"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "type_code":1, "position": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factioncost"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "cost":1, "position": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factionxp"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "xp":1, "name": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "number"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'code': 1}, null, null));
		layout_template = 1;
	} else if (deck.sort_type == "xp"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'xp': -1, 'name': 1}, {xp: 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "cost"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'cost': 1, 'name': 1}, {'cost':1}, null));
		layout_template = 1;
	} else {
		layout_template = 2;
		deck.update_layout_section(data, 'events', deck.get_layout_data_one_section({'type_code':'event'}, 'type_name'));
		deck.update_layout_section(data, 'upgrades', deck.get_layout_data_one_section({'type_code': 'upgrade', permanent: false}, 'type_name'));
		deck.update_layout_section(data, 'resources', deck.get_layout_data_one_section({'type_code': 'resource'}, 'type_name'));
		deck.update_layout_section(data, 'allies', deck.get_layout_data_one_section({'type_code': 'ally'}, 'type_name'));
		deck.update_layout_section(data, 'supports', deck.get_layout_data_one_section({'type_code': 'support', permanent: false}, 'type_name'));
		deck.update_layout_section(data, 'player_side_schemes', deck.get_layout_data_one_section({'type_code': 'player_side_scheme', permanent: false}, 'type_name'));

		deck.update_layout_section(data, 'permanent', deck.get_layout_data_one_section({permanent: true}, 'type_name'));
	}
	if (hero_special_cards.length) {
		deck.update_layout_section(data, 'special_sets', deck.get_layout_hero_special_sets());
	}
	if (options && options.layout) {
		layout_template = options.layout;
	}

	return layouts[layout_template](data);
}

deck.get_layout_section = function(sort, group, filter){
	var section = $('<div>');
	var query = {};
	var groups = {};
	var context = "";
	if (sort && sort.code){
		context = "number";
	}
	if (sort && sort.position){
		context = "number";
	}
	// if we have a group, then send the group by to the query
	if (group){
		var cards = deck.get_cards(sort, query, group);
	} else {
		var cards = deck.get_cards(sort, query);
	}

	if(cards.length) {

		//$(header_tpl({code: "Cards", name: "Cards", quantity: deck.get_nb_cards(cards)})).appendTo(section);
		//'<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'
		// run through each card and display display it
		deck.create_card_group(cards, context).appendTo(section);

	} else if (cards.constructor !== Array){
		$.each(cards, function (index, group_cards) {
		//cards.forEach(function (group_cards) {
			if (group_cards.constructor === Array){
				$(header_tpl({code: index, name: index == "undefined" ? "Null" : index, quantity: group_cards.reduce(function(a,b){ return a + b.indeck}, 0) })).appendTo(section);
				deck.create_card_group(group_cards, context).appendTo(section);
			}
		});
	}
	return section;
}


deck.update_layout_section = function update_layout_section(data, section, element) {
	data[section] = data[section] + element[0].outerHTML;
}

deck.get_layout_data_one_section = function get_layout_data_one_section(query, displayLabel) {
	var section = $('<div>');

	var cards = deck.get_cards({ name: 1 }, query);
	if(cards.length) {
		var name = "";
		name = cards[0][displayLabel];
		if (query.permanent) {
			$(header_tpl({code: "Permanent", name: "Permanent", quantity: deck.get_nb_cards(cards)})).appendTo(section);
		} else {
			$(header_tpl({code: name, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
		}
		cards.forEach(function (card) {
			var div = deck.create_card(card, false);
			div.appendTo(section);
		});

	}
	return section;
}

/**
 * Write out the special sets for cards associated with the hero but are not in the traditional
 * hero deck (Dr. Strange, Storm, Iceman, etc).
 */
deck.get_layout_hero_special_sets = function get_layout_hero_special_sets() {
	var section = $('<div>');

	var special_sets = hero_special_cards.reduce(function (acc, card) {
		if (!acc.includes(card.card_set_code)) {
			acc.push(card.card_set_code);
		}
		return acc;
	}, []);

	special_sets.forEach(function (specialSet) {
		var cardsInSet = hero_special_cards.filter(function (card) {
			return card.card_set_code === specialSet;
		});
		if (cardsInSet.length) {
			var quantity = cardsInSet.reduce(function (acc, card) {
				return acc + card.quantity;
			}, 0);
			$(header_tpl({ code: cardsInSet[0].card_set_code, name: cardsInSet[0].card_set_name, quantity })).appendTo(section);
			cardsInSet.forEach(function (card) {
				var div = deck.create_card(card, true);
				div.appendTo(section);
			});
		}
	});

	return section;
}


deck.create_card_group = function(cards, context){
	var section = $('<div>');
	cards.forEach(function (card) {
		var $div = deck.create_card(card, false);
		$div.appendTo(section);
	});
	return section;
}

deck.create_card = function create_card(card, isSpecialHeroCard) {
	var $div = $('<div>').addClass(deck.can_include_card(card) || isSpecialHeroCard ? '' : 'invalid-card');

	$div.append($(card_line_tpl({card:card})));

	if(card.is_unique == true) {
		$div.prepend(' •');
	}
	if(card.faction_code == "hero") {
		$div.prepend('<span class="fa fa-user" style="color:grey;" title="Hero specific cards. Cannot be removed"></span>');
	} else {
		if (card.card_set_code) {
			$div.prepend(' <span class="fa fa-user fg-'+card.faction_code+'" title="Hero specific cards. Cannot be removed"></span>');
		} else {
			$div.prepend(' <span class="fa fa-circle fg-'+card.faction_code+'" title="'+card.faction_code+'"></span>');
		}
	}

	if (isSpecialHeroCard) {
		$div.prepend(card.quantity+'x ');
	} else {
		$div.prepend(card.indeck+'x ');
	}

	var $span = $('<span style="float: right"></span>');

	if(card.resource_physical && card.resource_physical > 0) {
		$span.append(app.format.resource(card.resource_physical, 'physical'));
	}
	if(card.resource_mental && card.resource_mental > 0) {
		$span.append(app.format.resource(card.resource_mental, 'mental'));
	}
	if(card.resource_energy && card.resource_energy > 0) {
		$span.append(app.format.resource(card.resource_energy, 'energy'));
	}
	if(card.resource_wild && card.resource_wild > 0) {
		$span.append(app.format.resource(card.resource_wild, 'wild'));
	}


	if (!no_collection){
		var pack = app.data.packs.findById(card.pack_code);
		var in_collection = false;
		if (collection[pack.id]) {
			in_collection = true;
		} else {
			if (card.duplicated_by) {
				card.duplicated_by.forEach(function (dupe_code) {
					var dupe_card = app.data.cards.findById(dupe_code);
					if (dupe_card) {
						pack = app.data.packs.findById(dupe_card.pack_code);
						if (collection[pack.id]) {
							in_collection = true;
						}
					}
				});
			}
		}
		if (!in_collection) {
			$div.append(' <span class="fa fa-question" title="This card is not part of your collection"></span>');
		}
	}

	$div.append($span);

	return $div;
}

/**
 * @memberOf deck
 * @return boolean true if at least one other card quantity was updated
 */
deck.set_card_copies = function set_card_copies(card_code, nb_copies) {
	var card = app.data.cards.findById(card_code);
	if(!card) return false;

	var updated_other_card = false;

	app.data.cards.updateById(card_code, {
		indeck: nb_copies
	});
	app.deck_history && app.deck_history.notify_change();

	return updated_other_card;
}

/**
 * @memberOf deck
 * @return boolean true if at least one other card quantity was updated
 */
deck.set_card_ignores = function set_card_ignores(card_code, nb_copies) {
	var card = app.data.cards.findById(card_code);
	if(!card) return false;

	var updated_other_card = false;

	app.data.cards.updateById(card_code, {
		ignore: nb_copies
	});

	return updated_other_card;
}

/**
 * @memberOf deck
 */
deck.get_content = function get_content() {
	var cards = deck.get_cards();
	var content = {};
	cards.forEach(function (card) {
		content[card.code] = card.indeck;
	});
	return content;
}

/**
 * @memberOf deck
 */
deck.get_ignored_cards = function get_ignored_cards() {
	var cards = deck.get_cards();
	var ignored = {};
	cards.forEach(function (card) {
		if (card.ignore > 0){
			ignored[card.code] = card.ignore;
		}
	});
	return ignored;
}

/**
 * @memberOf deck
 */
deck.get_json = function get_json() {
	return JSON.stringify(deck.get_content());
}
/**
 * @memberOf deck
 */
deck.get_ignored_json = function get_ignored_json() {
	return JSON.stringify(deck.get_ignored_cards());
}
/**
 * @memberOf deck
 */
deck.get_meta_json = function get_meta_json() {
	return JSON.stringify(deck.meta);
}

/**
 * @memberOf deck
 */
deck.get_export = function get_export(format) {

}

/**
 * @memberOf deck
 */
deck.get_copies_and_deck_limit = function get_copies_and_deck_limit() {
	var copies_and_deck_limit = {};
	deck.get_draw_deck().forEach(function (card) {
		var value = copies_and_deck_limit[card.real_name];
		if(!value) {
			copies_and_deck_limit[card.real_name] = {
					nb_copies: card.indeck,
					deck_limit: card.deck_limit
			};
		} else {
			value.nb_copies += card.indeck;
			value.deck_limit = Math.min(card.deck_limit, value.deck_limit);
		}
	})
	return copies_and_deck_limit;
}

/**
 * @memberOf deck
 */
deck.get_problem = function get_problem() {
	var hero = app.data.cards.findById(hero_code);

	// store list of all problems
	deck.problem_list = [];
	if (hero && hero.deck_requirements){
		// must have the required cards
		if (hero.deck_requirements.card){
			var req_count = 0;
			var req_met_count = 0;
			$.each(hero.deck_requirements.card, function (key, possible){
				req_count++;
				var found_match = false;
				$.each(possible, function (code, code2){
					var req = app.data.cards.findById(code);
					if (req && req.indeck){
						found_match = true;
					}
				});
				if (found_match){
					req_met_count++;
				}
			});
			if (req_met_count < req_count){
				return 'hero';
			}
		}
	}

	// Deck contains invalid cards
	if (deck.get_invalid_cards().length > 0) {
		return 'invalid_cards';
	}

	// Deck has less than the required 40 cards
	if (deck.get_draw_deck_size() < 40) {
		return 'too_few_cards';
	}

	// Deck has more than the allowed 50 cards
	if (deck.get_draw_deck_size() > 50) {
		return 'too_many_cards';
	}

	if (deck.requirements) {
		if (deck.requirements.aspects) {
			// For now, assume a deck aspects requirements means equal cards from each aspect.
			if (deck.requirements.aspects === 2) {
				if (deck.meta && deck.meta.aspect && deck.meta.aspect2) {
					if (deck.get_aspect_count(deck.meta.aspect) != deck.get_aspect_count(deck.meta.aspect2) ) {
						return 'hero';
					}
				} else {
					return 'hero';
				}
			} else {
				var aspectCounts = [
					deck.get_aspect_count('aggression'),
					deck.get_aspect_count('justice'),
					deck.get_aspect_count('leadership'),
					deck.get_aspect_count('pool'),
					deck.get_aspect_count('protection')
				];

				var differentAspectCounts = aspectCounts.filter((count) => count > 0);

				// Allow an all basic deck.
				if (differentAspectCounts.length > 0) {
					var areAspectCountsEqual = differentAspectCounts.every((count) => count === differentAspectCounts[0]);

					if (differentAspectCounts.length !== deck.requirements.aspects || !areAspectCountsEqual) {
						return 'hero';
					}
				}
			}
		}
		if (deck.requirements.limit) {
			if (!deck.check_limit(deck.requirements.limit)) {
				return 'hero';
			}
		}
	}
}

deck.reset_limit_count = function (){
	if (deck.hero){
		deck.deck_options = deck.hero.deck_options;

		if (deck.deck_options) {
			for (var i = deck.deck_options.length - 1; i >= 0 ; i--) {
				if (deck.deck_options[i] && deck.deck_options[i].dynamic) {
					deck.deck_options.splice(i, 1);
				} else {
					deck.deck_options[i].limit_count = 0;
					deck.deck_options[i].atleast_count = {};
				}
			}
		}
	}
}

deck.get_invalid_cards = function get_invalid_cards() {
	deck.reset_limit_count();
	return _.filter(deck.get_cards(), function (card) {
		return !deck.can_include_card(card, true);
	});
}

/**
 * returns true if the deck can include the card as parameter
 * @memberOf deck
 */
deck.can_include_card = function can_include_card(card, limit_count, hard_count) {
	var hero = app.data.cards.findById(hero_code);

	// hide heroes
	if (card.type_code === "hero") {
		return false;
	}
	if (card.faction_code === "encounter") {
		return false;
	}

	// always allow their own set into the deck
	if (card.card_set_code == hero.card_set_code) {
		return true;
	}
	// Don't let other cards from other sets into the deck.
	// Spider-Women has faction cards in her hero deck that are not allowed in other hero decks.
	if (card.card_set_code && card.card_set_code !== hero.card_set_code) {
		return false;
	}

	// for now always allow basic cards!
	if (card.faction_code == "basic") {
		return true;
	}

	// if an aspect is set, allow that, otherwise allow all cards because
	if (deck.meta.aspect) {
		if (deck.meta.aspect == card.faction_code) {
			return true;
		}
		if (deck.requirements && deck.requirements.aspects) {
			if (deck.requirements.aspects == 4) {
				// allowed all aspects basically
				return true;
			}
			if ((!deck.meta.aspect2) || (deck.meta.aspect2 && deck.meta.aspect2 == card.faction_code)) {
				return true;
			}
		}
	} else {
		// There isn't an aspect yet so allow all
		return true;
	}

	var overflow = 0;

	if (deck.deck_options && deck.deck_options.length) {

		for (var i = 0; i < deck.deck_options.length; i++){
			var option = deck.deck_options[i];

			if (option.type) {
				// Needs to match at least one type
				if (!option.type.includes(card.type_code)) {
					continue;
				}
			}

			if (option.trait){
				// needs to match at least one trait
				var trait_valid = false;

				for(var j = 0; j < option.trait.length; j++){
					var trait = option.trait[j];

					if (card.real_traits && card.real_traits.toUpperCase().indexOf(trait.toUpperCase()) !== -1){
						trait_valid = true;
					}
				}

				if (!trait_valid){
					continue;
				}
			}

			if (limit_count && option.use_deck_limit) {
				// Need to include the deck limit amount for each card
				if (card.indeck > 0 && card.indeck !== card.deck_limit) {
					continue;
				}

				if (option.name_limit) {
					if (option.limit_count >= option.name_limit) {
						continue;
					}

					// If we have overflow from a previous option, use that instead of the quantity
					if (overflow) {
						option.limit_count += overflow;
					} else {
						option.limit_count += 1;
					}

					if (option.limit_count > option.name_limit) {
						overflow = option.limit_count - option.name_limit;
						option.limit_count = option.name_limit;
						continue;
					}
				}
			} else if (limit_count && option.limit) {
				if (option.limit_count >= option.limit) {
					continue;
				}
				if (hard_count) {
					option.limit_count += 1;
				} else {
					// If we have overflow from a previous option, use that instead of the quantity
					if (overflow) {
						option.limit_count += overflow;
					} else {
						option.limit_count += card.indeck;
					}
				}
				if (option.limit_count > option.limit) {
					overflow = option.limit_count - option.limit;
					option.limit_count = option.limit;
					continue;
				}
			}

			if (option.resource) {
				// needs to match at least one resource type
				var resource_valid = false;

				for (var j = 0; j < option.resource.length; j++) {
					var resource = option.resource[j].toLowerCase();

					if (
						(resource === 'energy' && card.resource_energy && card.resource_energy > 0) ||
						(resource === 'mental' && card.resource_mental && card.resource_mental > 0) ||
						(resource === 'physical' && card.resource_physical && card.resource_physical > 0) ||
						(resource === 'wild' && card.resource_wild && card.resource_wild > 0)
					) {
						resource_valid = true;
					}
				}

				if (!resource_valid) {
					continue;
				}
			}

			// If we got to this point, the card matches all of our options so include it.
			return true;
		}
	}

	return false;
}

})(app.deck = {}, jQuery);
