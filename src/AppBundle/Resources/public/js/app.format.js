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
 * @memberOf format
 */
format.fancy_int = function traits(num) {
	var string = (num != null ? (num < 0 ? "X" : num) : '&ndash;')
	return string;
};

/**
 * @memberOf format
 */
format.name = function name(card) {
	var name = (card.is_unique ? '<span class="icon-unique"></span> ' : "") + card.name;
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
	var text = card.pack_name + ' #' + card.position + '. ';
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
		case 'agenda':
			text += '<div>Doom: '+format.fancy_int(card.doom)+'.</div>';
			break;
		case 'act':
			text += '<div>Clues: '+format.fancy_int(card.clues)+'.</div>';
			break;
		case 'location':
			if (card.clues_fixed || card.clues == 0){
				text += '<div>Shroud: '+format.fancy_int(card.shroud)+'. Clues: '+format.fancy_int(card.clues)+'.</div>';
			} else {
				text += '<div>Shroud: '+format.fancy_int(card.shroud)+'. Clues: '+format.fancy_int(card.clues)+'<span class="icon icon-per_investigator"></span>.</div>';
			}
			break;
		case 'enemy':
			text += '<div>Fight: '+format.fancy_int(card.enemy_fight)+'. Health: '+format.fancy_int(card.health)+'';
			if (card.health_per_investigator){
				text += '<span class="icon icon-per_investigator"></span>';
			}
			text += '. Evade: '+format.fancy_int(card.enemy_evade)+'.</div>';
			text += '<div>Damage: '+format.fancy_int(card.enemy_damage)+'. Horror: '+format.fancy_int(card.enemy_horror)+'.</div>';
			break;
		case 'hero':
			text += '<div>Thwart: '+card.thwart+'. Attack: '+card.attack+'. Defense: '+card.defense+'.</div>';
			text += '<div>Hit Points: '+card.health+'. Hand Size: '+card.hand_size+'.</div>'
			break;	
		case 'alter_ego':
			text += '<div>Recover: '+card.recover+'.</div>';
			text += '<div>Hit Points: '+card.health+'. Hand Size: '+card.hand_size+'.</div>'
			break;	
		case 'support':
		case 'ally':
		case 'upgrade':
		case 'resource':
		case 'event':
			if (card.type_code != 'resource') {
				text += '<div>Cost: '+format.fancy_int(card.cost)+'. '+'</div>';
			}
			if (card.resource_physical || card.resource_mental || card.resource_energy || card.resource_wild){
				text += '<div>Resource: ';
				if (card.resource_physical){
					text += Array(card.resource_physical+1).join('<span class="icon icon-physical color-physical"></span>');
				}
				if (card.resource_mental){
					text += Array(card.resource_mental+1).join('<span class="icon icon-mental color-mental"></span>');
				}
				if (card.resource_energy){
					text += Array(card.resource_energy+1).join('<span class="icon icon-energy color-energy"></span>');
				}
				if (card.resource_wild){
					text += Array(card.resource_wild+1).join('<span class="icon icon-wild color-wild"></span>');
				}
				text += '</div>';
			}
			if (card.type_code == 'ally') {
				text += '<div>Attack: '+format.fancy_int(card.attack);
				if (card.attack_cost) {
					text += Array(card.attack_cost+1).join('<span class="icon icon-cost color-cost"></span>');
				}
				text += ' Thwart: '+format.fancy_int(card.thwart);
				if (card.thwart_cost) {
					text += Array(card.thwart_cost+1).join('<span class="icon icon-cost color-cost"></span>');
				}
				text += '.</div>';
			}
			if (card.health){
				text += '<div>Health: '+format.fancy_int(card.health)+'.</div>';
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
	if (alternate){
		text = card[alternate];
	}
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.back_text = function back_text(card) {
	var text = card.back_text || '';
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.html_page = function back_text(element) {
	var curInnerHTML = element.innerHTML;
	curInnerHTML = curInnerHTML.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	curInnerHTML = curInnerHTML.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	element.innerHTML = curInnerHTML;
};


})(app.format = {}, jQuery);
