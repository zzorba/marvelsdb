
(function app_deck_init(deck_init, $) {
	var deck_init_config = null;

	deck_init.update_build_init = function(){

		if ($("#deck_init_all").val() == "your"){
			deck_init_config.all = false;
			$("#all_inv").addClass("hidden");
			$("#my_inv").removeClass("hidden");
		} else {
			deck_init_config.all = true;
			$("#all_inv").removeClass("hidden");
			$("#my_inv").addClass("hidden");
		}

		if ($("#deck_init_format").val() == "current") {
			deck_init_config.format = "current";
			$("div[data-legacy='1']").addClass("hidden");
		} else {
			deck_init_config.format = "legacy";
			$("div[data-legacy='1']").removeClass("hidden");
		}

		if (localStorage) {
			localStorage.setItem('ui.deck.init', JSON.stringify(deck_init_config));
		}
	}

	deck_init.on_all_loaded = function() {
		window.alert("rah?");
	}

	$(document).ready(function(){
		if (localStorage) {
			var stored = localStorage.getItem('ui.deck.init');
			if(stored) {
				deck_init_config = JSON.parse(stored);
			}
		}
		deck_init_config = _.extend({
			'all': false,
		}, deck_init_config || {});

		if (deck_init_config.all){
			$("#deck_init_all").val("all");
		} else {
			$("#deck_init_all").val("your");
		}

		if (deck_init_config.format == "current"){
			$("#deck_init_format").val("current");
		} else {
			$("#deck_init_format").val("legacy");
		}

		deck_init.update_build_init();

	});
})(app.deck_init = {}, jQuery);