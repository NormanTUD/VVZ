<?php
	header('Content-Type: application/javascript');
	include_once("../config.php");
?>

"use strict";
var tour;
function toggle_details (id) {
	$("#details_" + id).toggle();
}

function return_to_last_back () {
	history.go(-1);
	return true;
}

function update_selection_cookies () {
	var stdcookie = "additiver_stundenplan";
	var aktuell = getCookie(stdcookie);
	aktuell_array = aktuell.split(',');

	var ids = [];
	var removed_ids = [];

	$('input:checkbox').each(function () {
		if($(this).prop('checked')) {
			var tval = $(this).val();
			if($.isNumeric(tval)) {
				console.log("ADDED " + tval);
				ids.push(tval);
			} else {
				console.log("Invalid data: " + tval);
			}
		} else {
			var tval = $(this).val();
			if($.isNumeric(tval)) {
				if(aktuell_array.includes(tval)) {
					console.log("REMOVED " + tval);
					removed_ids.push(tval);
				}
			} else {
				console.log("Invalid data: " + tval);
			}
		}
	});

	aktuell_array.forEach(function(entry) {
		entry = entry.replace(/^=/, '');
		ids.push(entry);
	});

	var unique_ids = Array.from(new Set(ids));
	unique_ids = unique_ids.filter(
		function(n){
			if(
				n != undefined &&
				n != "" &&
				n != 'null' &&
				n !== null &&
				n != '=null'
				&& !removed_ids.includes(n)
			) {
				return n;
			}
		}
	);
	unique_ids = filter_array(unique_ids);
	console.log(unique_ids);

	var str_to_cookie = unique_ids.join(',');
	console.log(str_to_cookie);
	console.log("Setting cookie `" + stdcookie + "` = " + str_to_cookie);
	delete_cookie(stdcookie);
	setCookie(stdcookie, str_to_cookie, 99999999);
}

function flatten(arr) {
	return arr.reduce(function (flat, toFlatten) {
		return flat.concat(Array.isArray(toFlatten) ? flatten(toFlatten) : toFlatten);
	}, []);
}

function filter_array(test_array) {
	var index = -1,
		arr_length = test_array ? test_array.length : 0,
		resIndex = -1,
		result = [];

	while (++index < arr_length) {
		var value = test_array[index];

		if (value) {
			result[++resIndex] = value;
		}
	}

	return result;
}

$(document).ready(function() {
	safariIFrameWarning();
	if (history.length >= 2) {
		$("#backbutton").css('visibility', 'visible');
	}

});

function edit_veranstaltung (id) {
	var edit_text_name = "#edit_veranstaltung_" + id;
	var edit_text_div_name = "#edit_veranstaltung_" + id + "_div";
	var edit_text = $(edit_text_name);
	var submit_name = "#submit_button_aenderungen";
	var submit = $(submit_name);
	var original_text_name = "#original_veranstaltung_text_" + id;
	var original_text = $(original_text_name);
	var edit_text_div = $(edit_text_div_name);

	//$("#hidden_alternative_text_veranstaltung_" + id).remove();

	edit_text.attr('name', 'alternative_text_veranstaltung_' + id);

	console.log(edit_text_name);

	edit_text_div.show();
	original_text.hide();
	submit.show();
	edit_text.show();
}

function delete_cookie( name ) {
	document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function setCookie (cookiename, cookievalue, hours) {
	var date = new Date();
	date.setTime(date.getTime() + Number(hours) * 3600 * 1000);
	document.cookie = '';
	document.cookie = cookiename + "=" + escape(cookievalue) + "; path=/;expires = " + date.toGMTString();
}

function getCookie(name) {
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i <ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function get_modul_via_pruefungs_id (pn) {
	var elem = $("#pn_modul_" + pn);
	return elem.text();
}

function update_pruefungsleistung_cookies (reload) {
	get_absolvierte_pruefungsleistungen();
	get_geplante_pruefungsleistungen();

	var regExp = /pn_modul_(.*)/;

	$('[id^=pn_modul_]').each(function (e) {
		var name = this.id;
		var matches = regExp.exec(name);
		if(matches.length) {
			var pn = matches[1];
			var modul = $("#" + name).text();

			if($("#pruefung_already_done_" + pn).is(":checked")) {
				$("#pruefung_already_chosen_" + pn).prop("checked", false);
			} else if($("#pruefung_already_chosen_" + pn).is(":checked")) {
				$("#pruefung_already_done_" + pn).prop("checked", false);
			}

			var counter_done = 0;
			var counter_geplant = 0;
			var summe_benoetigte_pruefungen = $("#summe_benoetigte_pruefungen_modul_" + modul).text();

			$(".pruefung_already_done").each(function (l) {
				var this_pl = $(this).val();
				if($(this).is(':checked')) {
					var this_pruefungs_id = $(this).val();
					if(get_modul_via_pruefungs_id(this_pruefungs_id) == modul) {
						counter_done = counter_done + 1;
					}
				}
			});

			$(".pruefung_already_chosen").each(function (l) {
				var this_pl = $(this).val();
				if($(this).is(':checked')) {
					var this_pruefungs_id = $(this).val();
					if(get_modul_via_pruefungs_id(this_pruefungs_id) == modul) {
						counter_geplant = counter_geplant + 1;
					}
				}
			});

			var gesamtfortschritt_absolviert = Math.floor(((parseInt(counter_done)) / parseInt(summe_benoetigte_pruefungen)) * 100);
			$("#absolvierte_pruefungen_modul_" + modul).html(counter_done + " (" + gesamtfortschritt_absolviert + "%)");
			$("#geplante_pruefungen_modul_" + modul).html(counter_geplant);

			var sum_counter = parseInt(counter_done) + parseInt(counter_geplant);
			var gesamtfortschritt = Math.floor(((parseInt(counter_done) + parseInt(counter_geplant)) / parseInt(summe_benoetigte_pruefungen)) * 100);
			$("#geplante_absolvierte_pruefungen_modul_" + modul).html(sum_counter + " (" + gesamtfortschritt + "%)");
		}
	});

	if(reload) {
		location.reload();
	}
}

function get_absolvierte_pruefungsleistungen () {
	var cname = "absolviertepruefungsleistungen";
	var pruefungsnummern = new Array();
	setCookie(cname, "", 0);

	$(".pruefung_already_done").each(function (l) {
		if($(this).is(':checked')) {
			pruefungsnummern.push(this.value);
		}
	});

	var json_str = JSON.stringify(pruefungsnummern);
	setCookie(cname, json_str, 99999999);
}

function get_geplante_pruefungsleistungen () {
	var cname = "geplante_pruefungsleistungen";
	var pruefungsnummern = new Array();
	setCookie(cname, "", 0);

	$(".pruefung_already_chosen").each(function (l) {
		if($(this).is(':checked')) {
			pruefungsnummern.push(this.value);
		}
	});

	var json_str = JSON.stringify(pruefungsnummern);
	setCookie(cname, json_str, 99999999);
}

function toggle_filter () {
	$('#filter').toggle();
}

function toggle_all_details () {
	$("[id^='details_']").toggle();
}

function create_tinyurl () {
	var url = "http://tinyurl.com/api-create.php?url=" + window.location.href;
	var created = $.get(url);
	$('#created_tinyurl').html(created);

}

$(document).ready(function() {
	$("#query_analyzer").hide();
	$("input.pruefung_already_done:checkbox").on('click',function () {
		update_pruefungsleistung_cookies(1);
	});

	$("input.pruefung_already_chosen:checkbox").on('click',function () {
		update_pruefungsleistung_cookies(1);
	});

	$("div[id^='details_']").hide();

	$('a[id^="click_to_edit_veranstaltung_"]').each(function () {
		var id = $(this).attr('id');
		var this_number = id.match(/click_to_edit_veranstaltung_(.*)$/)[1];
		if(this_number) {
			$(this).bind('click', function() {
				edit_veranstaltung(this_number);
			});
		}
	});

	$("#toggle_query_analyzer").bind('click', function() {
		$("#query_analyzer").toggle();
	});

	$('a[id^="toggle_details_"]').each(function () {
		var id = $(this).attr('id');
		var this_number = id.match(/\d+$/)[0];
		if(this_number) {
			$(this).bind('click', function() {
				toggle_details(this_number);
			});
		}
	});

	$('#filter_toggle').bind('click', function() {
		toggle_filter();
	});

	$('#toggle_all_details').bind('click', function() {
		toggle_all_details();
	});

	$('#neinliebernicht').bind('click', function() {
		return_to_last_back();
	});


	$('#create_tinyurl').bind('click', function() {
		create_tinyurl();
	});

	$(document).on("click", "#stundenplan_addieren", function () {
		update_selection_cookies();
	});

	$(document).on("click", "#new_row_einzelner_termin", function () {
		add_new_row_to_einzelne_termine();
	});

	$(document).on("click", ".remove_this_tr", function () {
		remove_tr(this);
	});


	$(document).on ("click", "#hilfe", function () {
		start_tour();
	});

	$('textarea[id^="edit_veranstaltung_"]').on('keyup keypress focus click', function(e) {
		while($(this).outerHeight() < this.scrollHeight + parseFloat($(this).css("borderTopWidth")) + parseFloat($(this).css("borderBottomWidth"))) {
			$(this).height($(this).height()+1);
		};
	});

	/*
	$(document)
	    .one('focus.textarea', '.autoExpand', function(){
		            var savedValue = this.value;
		            this.value = '';
		            this.baseScrollHeight = this.scrollHeight;
		            this.value = savedValue;
		        })
	    .on('input.textarea', '.autoExpand', function(){
		            var minRows = this.getAttribute('data-min-rows')|0,
			                rows;
		            this.rows = minRows;
		            rows = Math.ceil((this.scrollHeight - this.baseScrollHeight) / 16);
		            this.rows = minRows + rows;
		        });
	*/

	tour = new Tour(
		{
			debug: true,
			orphan: true,
			steps: [
				/*
				 *	Startseite
				 */
				{
					element: "#alle_lehrveranstaltungen",
					title: "Alle Lehrveranstaltungen",
					content: "Zeigt die Veranstaltungen aus allen Studiengängen an."
				},
				{
					element: "#naechster_studiengang",
					title: "Weitere Studiengänge",
					content: "Zeigt die Veranstaltungen aus dem jeweiligen Studiengängen an. Dieses Muster gilt für alle weiteren dieser Knöpfe."
				},
				{
					element: "#alle_pruefungsleistungen_anzeigen",
					title: "Zeigt alle Prüfungsleistungen an",
					content: "Auf der Suche nach Prüfungsnummern, Modulen etc. ist diese Seite sehr hilfreich. Außerdem kann man dort von einer Prüfungsleistung mit einem Klick zu Veranstaltungen gehen, die diese Prüfungsleistung anbieten"
				},
,

				/*
				 *	Einzelner Studiengang
				 */
				{
					element: "#filter_toggle",
					title: "Zeigt oder versteckt Filter",
					content: "Mit den Filtern lassen sich Veranstaltungen suchen, die gewisse Kriterien erfüllen (z.B. einen bestimmten Dozenten haben oder in einer bestimmten Stunde in einem bestimmten Gebäude stattfinden)"
				},
				{
					element: "#toggle_all_details",
					title: "Zeigt oder versteckt alle Details",
					content: "Öffnet bzw. schließt alle Details zu allen Veranstaltungen auf dieser Seite"
				},
				{
					element: "#create_stundenplan_link",
					title: "Hilft, einen Stundenplan zu erstellen",
					content: "Hilft Ihnen dabei, einen studienordnungsgemäßen Stundenplan für ein Semester zu erstellen"
				},
				{
					element: "#studienordnung_link",
					title: "Link zur Studienordnung",
					content: "Link zu der Studienordnung des jeweiligen Studienganges"
				},
				{
					element: "#google_maps_icon",
					title: "Zeigt das Gebäude bei Google Maps",
					content: "Ein direkter Link zum Gebäude auf Google Maps. Dort können Sie z.B. mit StreetView das Gebäude ansehen, um es leichter zu finden"
				},
				{
					element: "#ical_item",
					title: "Lädt den ausgewähten Termine bzw. die ausgewählten Termine in einen Kalendar",
					content: "Diese Funktion erlaubt es, Termine automatisch in einen digitalen Kalendar einzutragen (im Smartphone, Outlook, Thunderbird usw.)"
				},
				{
					element: "#fuer_diesen_studiengang_pruefungen_anzeigen",
					title: "Für diesen Studiengang mögliche Prüfungen anzeigen",
					content: "Zeigt nur die Prüfungen an, die mit dem aktuell ausgewähltem Studiengang möglich sind"
				},

				/*
				 *	Links unten
				 */
				{
					element: "#startseite_link",
					title: "Link zur Startseite",
					content: "Mit einem Klick hierher gehts immer zurück zur Startseite"
				},
				{
					element: "#dokumente_link",
					title: "Dokumente",
					content: "Hier können Sie sich Dokumente halbautomatisch erstellen lassen"
				},
				{
					element: "#faq_link",
					title: "FAQ",
					content: "Hier finden Sie &raquo;frequently asked questions&laquo; zum Studium und dem Vorlesungsverzeichnis"
				},
				{
					element: "#kontakt_link",
					title: "Kontakt",
					content: "Mit wenigen Klicks können Sie uns hier Emails senden mit Fragen oder Verbesserungsvorschlägen."
				},
			]
		}
	);
});

function start_tour () {
	tour.init();
	tour.start();
	if (tour.ended()) {
		tour.restart();
	}
}

function remove_tr (item) {
	$($(item).closest("tr")).remove();
}

function add_new_row_to_einzelne_termine () {
	var gebaeude_selection = $('#gebaeude_selection').html();
	gebaeude_selection = gebaeude_selection.replace('TOREMOVE_', '');
	$('#einzelne_termine tr:last').after(
		'<tr>' + "\n" +
		'<td><input type="text" name="einzelner_termin_start[]" class="datetimepicker" /></td>' + "\n" +
		'<td><input type="text" name="einzelner_termin_ende[]" class="datetimepicker" /></td>' + "\n" +
		'<td>' + gebaeude_selection + '</td>' + "\n" +
		'<td><input type="text" name="einzelner_termin_raum[]" /></td>' + "\n" +
		'<td><span class="remove_this_tr"><img src="./i/remove.svg" alt="Zeile entfernen" width="30" /></span></td>' + "\n" +
		'</tr>'
);
}

$(document).on("focus", ".datetimepicker", function(){
	$(this).datetimepicker({
		prevText: '&#x3c;zurück', prevStatus: '',
		prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: '',
		nextText: 'Vor&#x3e;', nextStatus: '',
		nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: '',
		currentText: 'heute', currentStatus: '',
		todayText: 'heute', todayStatus: '',
		clearText: '-', clearStatus: '',
		closeText: 'schließen', closeStatus: '',
		monthNames: ['Januar','Februar','März','April','Mai','Juni',
			'Juli','August','September','Oktober','November','Dezember'],
		monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
			'Jul','Aug','Sep','Okt','Nov','Dez'],
		dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
		dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		showMonthAfterYear: false,
		showOn: 'both',
		buttonImageOnly: false,
		dateFormat:'yy-mm-dd',
		timeFormat: 'HH:mm:ss'
	});
});

function safariIFrameWarning () {
	var isInIframe = (window.location != window.parent.location) ? true : false;
	if (isInIframe && /^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
		$(".iframewarning").html("Achtung: öffnen Sie die Seite bitte in einem eigenen Fenster, sonst funktioniert sie eventuell nicht! <a href='http://<?php print $GLOBALS['vvz_base_url'];?>/' target='_blank'>Klicken Sie dazu hier.</a>");
		$(".iframewarning").addClass("red_giant");
	}
}

function toc () {
    var toc = "";
    var level = 0;

    document.getElementById("toc_contents").innerHTML =
        document.getElementById("toc_contents").innerHTML.replace(
            /<h([\d])>([^<]+)<\/h([\d])>/gi,
            function (str, openLevel, titleText, closeLevel) {
                if (openLevel != closeLevel) {
                    return str;
                }

                if (openLevel > level) {
                    toc += (new Array(openLevel - level + 1)).join("<ul>");
                } else if (openLevel < level) {
                    toc += (new Array(level - openLevel + 1)).join("</ul>");
                }

                level = parseInt(openLevel);

                var anchor = titleText.replace(/ /g, "_");
                toc += "<li><a href=\"#" + anchor + "\">" + titleText
                    + "</a></li>";

                return "<h" + openLevel + "><a name=\"" + anchor + "\">"
                    + titleText + "</a></h" + closeLevel + ">";
            }
        );

    if (level) {
        toc += (new Array(level + 1)).join("</ul>");
    }

    document.getElementById("toc").innerHTML += toc;
};

function reloadStylesheets() {
    var queryString = '?reload=' + new Date().getTime();
    $('link[rel="stylesheet"]').each(function () {
        this.href = this.href.replace(/\?.*|$/, queryString);
    });

	const iframe = document.getElementById("iframe_reloader");
	if(iframe) {
		iframe.contentWindow.reloadStylesheets();
	}
}

function reset_value (e) {
	try {
		var button = e.currentTarget;
		var gui_id = $(button).data("gui-id");
		var reset = $(button).data("reset");
		var is_color = $("#" + gui_id).hasClass("jscolor") ? 1 : 0;

		$("#" + gui_id).val(reset).trigger("change");
		if(is_color) {
			$("#" + gui_id).css("background-color", "#" + reset);
		}
	} catch (e) {
		log.warn(e);
	}
}

function scorePassword(pass) {
    var score = 0;
    if (!pass)
	return score;

    // award every unique letter until 5 repetitions
    var letters = new Object();
    for (var i=0; i<pass.length; i++) {
	letters[pass[i]] = (letters[pass[i]] || 0) + 1;
	score += 8.0 / letters[pass[i]];
    }

    // bonus points for mixing it up
    var variations = {
	digits: /\d/.test(pass),
	lower: /[a-z]/.test(pass),
	upper: /[A-Z]/.test(pass),
	nonWords: /\W/.test(pass),
    }

    var variationCount = 0;
    for (var check in variations) {
	variationCount += (variations[check] == true) ? 1 : 0;
    }
    score += (variationCount - 1) * 10;

    return parseInt(score);
}

function checkPassStrength(pass) {
    var score = scorePassword(pass);
    if (score > 80)
	return "Sehr gut";
    if (score > 60)
	return "Gut";
    if (score >= 30)
	return "Schwach";

    return "Sehr schwach";
}



$(document).ready(function() {
	$('.accordion-title').on('keydown', function(e) {
		if(e.keyCode === 13){
			$(this).click();
		}
	});

	$(".reset_value_button").on("click", reset_value);
	$("#toggle_ok").on("click", toggle_ok);


		$( "#globalsearch" ).autocomplete({
			source: function( request, response ) {
				$.ajax( {
					url: "search.php",
					dataType: "jsonp",
					data: {
						term: request.term
					},
					success: function( data ) {
						response( data );
					}
				} );
			},
			minLength: 1,
			select: function( event, ui ) {
				log( "Selected: " + ui.item.value + " aka " + ui.item.id );
			}
		} );
});


function log(...msgs) {
	for (var i = 0; i < msgs.length; i++) {
		console.log(msgs[i]);
	}
}

function toggle_ok () {
	$('.line_was_ok').toggle();
}
