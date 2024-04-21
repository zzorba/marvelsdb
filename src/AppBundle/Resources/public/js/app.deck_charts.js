(function app_deck_charts(deck_charts, $) {

	Highcharts.setOptions({
		lang: {
			drillUpText: 'X'
		}
	});

	var faction_colors = {
		leadership: '#2b80c5',
		aggression: '#cc3038',
		protection: '#107116',
		basic: '#808080',
		justice: '#c0c000',
		pool: '#d074ac',
		hero: '#AB006A'
	};

deck_charts.chart_faction = function chart_faction() {
	var factions = {};
	var draw_deck = app.deck.get_physical_draw_deck();
	draw_deck.forEach(function (card) {
		if(!factions[card.faction_code]) factions[card.faction_code] = { code: card.faction_code, name: card.faction_name, count: 0};
		factions[card.faction_code].count += card.indeck;
	})

	var data = [];
	_.each(_.values(factions), function (faction) {
		data.push({
			name: faction.name,
			label: '<span class="icon icon-'+faction.code+'"></span>',
			color: faction_colors[faction.code],
			y: faction.count
		});
	})

	$("#deck-chart-faction").highcharts({
		chart: {
            type: 'column'
        },
		title: {
            text: "Card Factions"
        },
		subtitle: {
            text: "Draw deck only"
        },
		xAxis: {
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
            title: {
                text: null
            }
        },
		yAxis: {
            min: 0,
			allowDecimals: false,
			tickInterval: 3,
            title: null,
            labels: {
                overflow: 'justify'
            }
        },
        series: [{
			type: "column",
			animation: false,
            name: '# cards',
			showInLegend: false,
            data: data
        }],
		plotOptions: {
			column: {
			    borderWidth: 0,
			    groupPadding: 0,
			    shadow: false
			}
		}
    });
}


deck_charts.chart_cost = function chart_cost() {

	var data = [];

	var draw_deck = app.deck.get_physical_draw_deck();
	draw_deck.forEach(function (card) {
		if(typeof card.cost === 'number') {
			data[card.cost] = data[card.cost] || 0;
			data[card.cost] += card.indeck;
		}
	})
	data = _.flatten(data).map(function (value) { return value || 0; });

	$("#deck-chart-cost").highcharts({
		chart: {
			type: 'line'
		},
		title: {
			text: "Card Cost"
		},
		subtitle: {
			text: "Cost X ignored"
		},
		xAxis: {
			allowDecimals: false,
			tickInterval: 1,
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 1,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		tooltip: {
			headerFormat: '<span style="font-size: 10px">Cost {point.key}</span><br/>'
		},
		series: [{
			animation: false,
			name: '# cards',
			showInLegend: false,
			data: data
		}]
	});
}


//------------------Resource chart-------------------//
deck_charts.chart_resource = function chart_resource() {

	//setting properties of all the four resource icons in game

	var icons = {};
	icons['physical'] = {
		code: "physical",
		"name": "Physical",
		color: "#661e09",
		count: 0,
		HeroCardsCounter: 0,
		IconCount: {
			single: 0,
			double: 0,
			triple: 0
		}
	};
	icons['mental'] = {
		code: "mental",
		"name": "Mental",
		color: "#003961",
		count: 0,
		HeroCardsCounter: 0,
		IconCount: {
			single: 0,
			double: 0,
			triple: 0
		}
	};
	icons['energy'] = {
		code: "energy",
		"name": "Energy",
		color: "#ff8f3f",
		count: 0,
		HeroCardsCounter: 0,
		IconCount: {
			single: 0,
			double: 0,
			triple: 0
		}
	};
	icons['wild'] = {
		code: "wild",
		"name": "Wild",
		color: "#00543a",
		count: 0,
		HeroCardsCounter: 0,
		IconCount: {
			single: 0,
			double: 0,
			triple: 0
		}
	};
	
	//checking every card in the deck (without identity cards and etc.) for number of resource icons
	//checking also if these icons are on hero cards or not 
	var draw_deck = app.deck.get_physical_draw_deck();
	draw_deck.forEach(function(card) {
		if (card.resource_physical && card.resource_physical > 0) {
			switch (card.resource_physical) {
				case 1:
					icons['physical'].count += card.indeck * card.resource_physical;
					icons['physical'].IconCount.single += card.indeck;
					break;
				case 2:
					icons['physical'].count += card.indeck * card.resource_physical;
					icons['physical'].IconCount.double += card.indeck;
					break;
				case 3:
					icons['physical'].count += card.indeck * card.resource_physical;
					icons['physical'].IconCount.triple += card.indeck;
					break;
			}
			if (card.faction_code === "hero") {
				icons['physical'].HeroCardsCounter += card.indeck * card.resource_physical;
				icons['physical'].count -= card.indeck * card.resource_physical;
			}
		}
		if (card.resource_mental && card.resource_mental > 0) {
			switch (card.resource_mental) {
				case 1:
					icons['mental'].count += card.indeck * card.resource_mental;
					icons['mental'].IconCount.single += card.indeck;
					break;
				case 2:
					icons['mental'].count += card.indeck * card.resource_mental;
					icons['mental'].IconCount.double += card.indeck;
					break;
				case 3:
					icons['mental'].count += card.indeck * card.resource_mental;
					icons['mental'].IconCount.triple += card.indeck;
					break;
			}
			if (card.faction_code === "hero") {
				icons['mental'].HeroCardsCounter += card.indeck * card.resource_mental;
				icons['mental'].count -= card.indeck * card.resource_mental;
			}
		}
		if (card.resource_energy && card.resource_energy > 0) {
			switch (card.resource_energy) {
				case 1:
					icons['energy'].count += card.indeck * card.resource_energy;
					icons['energy'].IconCount.single += card.indeck;
					break;
				case 2:
					icons['energy'].count += card.indeck * card.resource_energy;
					icons['energy'].IconCount.double += card.indeck;
					break;
				case 3:
					icons['energy'].count += card.indeck * card.resource_energy;
					icons['energy'].IconCount.triple += card.indeck;
					break;
			}
			if (card.faction_code === "hero") {
				icons['energy'].HeroCardsCounter += card.indeck * card.resource_energy;
				icons['energy'].count -= card.indeck * card.resource_energy;
			}
		}
		if (card.resource_wild && card.resource_wild > 0) {
			switch (card.resource_wild) {
				case 1:
					icons['wild'].count += card.indeck * card.resource_wild;
					icons['wild'].IconCount.single += card.indeck;
					break;
				case 2:
					icons['wild'].count += card.indeck * card.resource_wild;
					icons['wild'].IconCount.double += card.indeck;
					break;
				case 3:
					icons['wild'].count += card.indeck * card.resource_wild;
					icons['wild'].IconCount.triple += card.indeck;
					break;
			}
			if (card.faction_code === "hero") {
				icons['wild'].HeroCardsCounter += card.indeck * card.resource_wild;
				icons['wild'].count -= card.indeck * card.resource_wild;
			}
		}
	})
	
	//creating three datasets for X axis in the chart
	//HeroData is used in stacked columns
	//data is used in normal columns
	//DrillData is used in drilldown columns
	var HeroData = [];
	var data = [];
	var DrillData = [];
	_.each(_.values(icons), function(icon) {
		data.push({
			name: icon.name,
			label: '<span class="icon icon-' + icon.code + ' color-' + icon.code + '"></span>',
			color: icon.color,
			y: icon.count,
			code: icon.code,
			drilldown: "MultipleIconsChart",
		});
		HeroData.push({
			name: icon.name,
			label: '<span class="icon icon-' + icon.code + ' color-' + icon.code + '"></span>',
			y: icon.HeroCardsCounter,
		})
		for (var key in icon.IconCount) {
			let counter = icon.IconCount[key];
			switch (key) {
				case "single":
					RepeatIcon = 1
					break;
				case "double":
					RepeatIcon = 2
					break;
				case "triple":
					RepeatIcon = 3
					break;
			}
			if (counter > 0) {
				let IconKey = key + " " + icon.code;
				let label = '<span class="icon icon-' + icon.code + ' color-' + icon.code + '"></span>'
				DrillData.push({
					name: IconKey,
					color: icon.color,
					y: counter,
					label: label.repeat(RepeatIcon)
				})
			}
		}
	})
	data = _.flatten(data).map(function(value) {
		return value || 0;
	});
	
	//setting all the parameters for the chart
	$("#deck-chart-resource").highcharts({
		chart: {
			type: 'column'
		},
		title: {
			text: "Card Skill Icons"
		},
		subtitle: {
			text: ""
		},
		xAxis: [{
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
			title: {
				text: null
			}
		}, {
			categories: _.pluck(DrillData, 'label'),
			labels: {
				useHTML: true
			},
			title: {
				text: null
			}
		}],
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 3,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		series: [{
			type: "column",
			animation: false,
			name: '# of resources',
			showInLegend: false,
			data: data,
			xAxis: 0
		}, {
			type: "column",
			animation: false,
			name: '# of resources from hero cards',
			showInLegend: false,
			data: HeroData,
			xAxis: 0
		}],
		drilldown: {
			drillUpButton: {
				position: {
					y: 0,
					x: 0
				},
				theme:{
					width: 7,
					height: 12,
				},
				relativeTo: 'spacingBox',
			},
			series: [{
				showInLegend: false,
				name: '# of cards',
				xAxis: 1,
				id: "MultipleIconsChart",
				data: DrillData

			}]
		},
		plotOptions: {
			column: {
				stacking: 'normal',
				borderWidth: 0,
				groupPadding: 0,
				shadow: false
			}
		}
	});
}


deck_charts.chart_slot = function chart_slot() {

	var slots = {};
	var draw_deck = app.deck.get_physical_draw_deck();
	draw_deck.forEach(function (card) {
		if (card.type_code != "asset"){
			return;
		}
		var card_slot = "Other";
		if (card.slot){
			card_slot = card.slot;
		}
		if(!slots[card_slot]) slots[card_slot] = { name: card_slot, count: 0};
		slots[card_slot].count += card.indeck;
	})

	var data = [];
	_.each(_.values(slots), function (slot) {
		data.push({
			name: slot.name,
			label: slot.name,
			//color: faction_colors[faction.code],
			y: slot.count
		});
	})
	data = _.flatten(data).map(function (value) { return value || 0; });
		
	$("#deck-chart-slot").highcharts({
		chart: {
			type: 'column'
		},
		title: {
			text: "Asset Slots"
		},
		subtitle: {
			text: ""
		},
		xAxis: {
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 3,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		series: [{
			type: "column",
			animation: false,
			name: '# of cards',
			showInLegend: false,
			data: data
		}],
		plotOptions: {
			column: {
				borderWidth: 0,
				groupPadding: 0,
				shadow: false
			}
		}
	});
}

deck_charts.setup = function setup(options) {
	deck_charts.chart_resource();
	deck_charts.chart_cost();
	deck_charts.chart_faction();
	//deck_charts.chart_slot();
}

$(document).on('shown.bs.tab', 'a[data-toggle=tab]', function (e) {
	deck_charts.setup();
});

})(app.deck_charts = {}, jQuery);
