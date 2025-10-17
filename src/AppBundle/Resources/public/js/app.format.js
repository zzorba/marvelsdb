(function app_format(format, $) {

/**
 * @memberOf format
 */
format.traits = function traits(card) {
	return card.traits || '';
};



format.resource = function resource(value, type, css) {
	var string = '';
	if (value && value > 0) {
		for (var i = 0; i < value; i++) {
			string += ' <span class="icon-'+type+' color-'+type+'" title="'+type+'"></span>';
		}
	}
	return string;
};

/**
 * @memberof format
 */
format.fancy_int = function fancy_int(num, star, per_hero, per_group) {
	if (num != null) {
		let string = num < 0 ? 'X' : num;
		if (per_hero) {
			string += '<span class="icon icon-per_hero" />';
		} else if (per_group) {
			string += '<span class="icon icon-per_group" />';
		}
		if (star) {
			string += '<span class="icon icon-star" />';
		}
		return string;
	}
	return star ? '<span class="icon icon-star" />' : '—';
};

/**
 * @memberOf format
 */
format.stage = function stage(card) {
}

/**
 * @memberOf format
 */
format.name = function name(card) {
	var name = (card.is_unique ? '<span class="icon-unique"></span> ' : "") + card.name;
	if ((card.type_code == 'villain' || card.type_code == 'leader') && card.stage) {
		var stages = ['0', 'I', 'II', 'III', 'IV', 'V'];
		name += ' (' + (stages[card.stage] || card.stage) + ')';
	}
	if (card.subname){
		name += '<div class="card-subname small">'+card.subname+'</div>';
	}
	return name;
}

format.faction = function faction(card) {
	if (card.type_code == 'hero' || card.type_code == 'alter_ego') {
		return '';
	}
	var text = '<span class="fg-'+card.faction_code+' icon-'+card.faction_code+'"></span> '+ card.faction_name + '. ';
	if (card.faction2_code) {
		text += '<span class="fg-'+card.faction2_code+' icon-'+card.faction2_code+'"></span> '+ card.faction2_name + '. ';
	}
	return text;
}

/**
 * @memberOf format
 */
format.pack = function pack(card) {
	if (card.type_code == 'hero') {
		return '';
	}

	var text = '';
	if (card.boost || card.boost_star) {
		text += '<div>Boost:' +
			(card.boost_star ? '<span class="icon icon-star color-boost" />' : '') +
			(card.boost ? Array(card.boost+1).join('<span class="icon icon-boost color-boost" />') : '') +
			'</div>';
	}
	text += card.pack_name + ' #' + card.position + '. ';
	if (card.card_set_name){
		text += card.card_set_name;
		if (card.set_position){
			text += " #"+card.set_position;
			if (card.quantity > 1){
				text += "-";
				text += (card.set_position+card.quantity-1);
			}
		}
	}
	return text;
}

/**
 * @memberOf format
 */
format.info = function info(card) {
	var text = '';
	switch(card.type_code) {
		case 'side_scheme':
		case 'main_scheme':
			text += '<div>Starting Threat: ' + format.fancy_int(card.base_threat, null, !card.base_threat_fixed && !card.base_threat_per_group, card.base_threat_per_group) + '.';
			if (card.type_code == 'main_scheme') {
				if (card.escalation_threat || card.escalation_threat_star) {
					text += ' Escalation Threat: ' + format.fancy_int(card.escalation_threat, card.escalation_threat_star, !card.escalation_threat_fixed) + '.</div>';
				} else {
					text += '</div>';
				}
				text += '<div>Threat: ' + format.fancy_int(card.threat, card.threat_star, !card.threat_fixed && !card.threat_per_group, card.threat_per_group) + '.</div>';
			} else {
				text += '</div>';
			}
			break;
		case 'attachment':
			if (card.attack || card.attack_star) {
				text += '<div>Attack: ' + (card.attack > 0 ? '+' : '') + format.fancy_int(card.attack, card.attack_star) + '</div>';
			}
			if (card.scheme || card.scheme_star) {
				text += '<div>Scheme: ' + (card.scheme > 0 ? '+' : '') + format.fancy_int(card.scheme, card.scheme_star) + '</div>';
			}
			break;
		case 'leader':
		case 'villain':
		case 'minion':
				text += '<div>Attack: ' + format.fancy_int(card.attack, card.attack_star);
				text += ' Scheme: ' + format.fancy_int(card.scheme, card.scheme_star);
				text += ' Health: ' + format.fancy_int(card.health, card.health_star, card.health_per_hero, card.health_per_group);
				text += '.</div>';
			break;
		case 'treachery':
		case 'obligation':
			break;
		case 'hero':
			text += '<div>Thwart: ' + format.fancy_int(card.thwart, card.thwart_star) + '. Attack: ' + format.fancy_int(card.attack, card.attack_star) + '. Defense: ' + format.fancy_int(card.defense, card.defense_star) + '.</div>';
			text += '<div>Hit Points: ' + card.health + '. Hand Size: ' + card.hand_size + '.</div>'
			break;
		case 'alter_ego':
			text += '<div>Recover: ' + format.fancy_int(card.recover, card.recover_star) + '.</div>';
			text += '<div>Hit Points: ' + card.health + '. Hand Size: ' + card.hand_size + '.</div>'
			break;
		case 'support':
		case 'ally':
		case 'upgrade':
		case 'resource':
		case 'event':
		case 'player_side_scheme':
			if (card.type_code != 'resource') {
				text += '<div>Cost: ' + format.fancy_int(card.cost, null, card.cost_per_hero) + '.</div>';
			}
			if (card.type_code == 'player_side_scheme') {
				text += '<div>Threat: ' + format.fancy_int(card.base_threat, null, !card.base_threat_fixed && !card.base_threat_per_group, card.base_threat_per_group) + '.</div>';
			}
			if (card.resource_physical || card.resource_mental || card.resource_energy || card.resource_wild){
				text += '<div>Resource: ';
				if (card.resource_physical){
					text += Array(card.resource_physical+1).join('<span class="icon icon-physical color-physical" />');
				}
				if (card.resource_mental){
					text += Array(card.resource_mental+1).join('<span class="icon icon-mental color-mental" />');
				}
				if (card.resource_energy){
					text += Array(card.resource_energy+1).join('<span class="icon icon-energy color-energy" />');
				}
				if (card.resource_wild){
					text += Array(card.resource_wild+1).join('<span class="icon icon-wild color-wild" />');
				}
				text += '</div>';
			}
			if (card.type_code == 'ally') {
				text += '<div>Attack: ' + format.fancy_int(card.attack, card.attack_star);
				if (card.attack_cost) {
					text += Array(card.attack_cost+1).join('<span class="icon icon-cost color-cost" />');
				}
				text += ' Thwart: ' + format.fancy_int(card.thwart, card.thwart_star);
				if (card.thwart_cost) {
					text += Array(card.thwart_cost+1).join('<span class="icon icon-cost color-cost" />');
				}
				text += '.</div>';
			}
			if (card.health){
				text += '<div>Health: ' + format.fancy_int(card.health, card.health_star) + '.</div>';
			}
			break;
	}
	return text;
};

/**
 * @memberOf format
 */
format.text = function text(card, alternate) {
	var text = card.text || '';
	if (alternate) {
		text = card[alternate];
	}
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b class="card-traits"><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1" />');
	text = text.split("\n").join('</p><p>');
	if (card.scheme_acceleration || card.scheme_crisis || card.scheme_amplify || card.scheme_hazard) {
		text += '<p>';
		for (i = 0; i < (card.scheme_acceleration || 0); i++) {
			text += '<span name="Acceleration" class="icon icon-acceleration" />';
		}
		for (i = 0; i < (card.scheme_amplify || 0); i++) {
			text += '<span name="Amplify" class="icon icon-amplify" />';
		}
		for (i = 0; i < (card.scheme_crisis || 0); i++) {
			text += '<span name="Crisis" class="icon icon-crisis" />';
		}
		for (i = 0; i < (card.scheme_hazard || 0); i++) {
			text += '<span name="Hazard" class="icon icon-hazard" />';
		}
		text += '</p>';
	}
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.back_text = function back_text(card) {
	var text = card.back_text || '';
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b class="card-traits"><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.html_page = function back_text(element) {
	var curInnerHTML = element.innerHTML;
	curInnerHTML = curInnerHTML.replace(/\[\[([^\]]+)\]\]/g, '<b class="card-traits"><i>$1</i></b>');
	curInnerHTML = curInnerHTML.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	element.innerHTML = curInnerHTML;
};


})(app.format = {}, jQuery);
