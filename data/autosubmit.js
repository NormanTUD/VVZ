function autosubmit_extract_feedback (html) {
	if(!html) return { title: 'Gespeichert', message: '' };
	var tmp = document.createElement('div');
	tmp.innerHTML = html;
	var texts = [];
	tmp.querySelectorAll('span').forEach(function(s){
		var t = (s.textContent || '').trim();
		if(t) texts.push(t);
	});
	var cleaned = texts.join(' \u2022 ');
	var fallback = (tmp.textContent || '').replace(/\s+/g, ' ').trim();
	if(!cleaned && fallback.length > 200) fallback = fallback.substring(0, 200) + '\u2026';
	return { title: 'Gespeichert', message: cleaned || fallback || '' };
}

function autosubmit_handle_change (item) {
	var $changedField = $(item);

	if($changedField.attr('noautosubmit')) {
		return;
	}

	var loc = window.location.pathname;
	var base = loc.slice(0, loc.lastIndexOf('/') + 1);
	var submitfile = window.location.protocol  + "//" + window.location.host + base + 'submit.php';

	var $form = $changedField.closest('form');
	var data = $form.length ? $form.serialize() : $changedField.serialize();
	if(!data) {
		return;
	}

	// Guardrail: Speichern darf nie stillschweigend ausfallen. Wenn die Validierung ein
	// Problem meldet, wird der POST blockiert und die betroffenen Felder sind rot markiert.
	var blocked = false;
	if(typeof window.validate_autosubmit === 'function') {
		try {
			if(window.validate_autosubmit($form.length ? $form : $changedField) === false) {
				blocked = true;
			}
		} catch(e) {
			log("autosubmit.js: validate_autosubmit Fehler: ", e);
		}
	}
	if(blocked) {
		return;
	}

	$.ajax({
		url : submitfile,
		type: "POST",
		data: data,
		success: function (response) {
			var fb = autosubmit_extract_feedback(response);
			success(fb.message || fb.title, fb.message ? fb.title : '');
			if($(".auto_reload_stylesheets").length != 0) {
				reloadStylesheets();
			}
		},
		error: function (response, textStatus, errorThrown) {
			log(response);
			error("FEHLER", "Das automatische Speichern ist fehlgeschlagen. Bitte pr\u00fcfen Sie Ihre Eingaben.");
		}
	});
}

function autosubmit (identifier=".form_autosubmit, :input") {
	// Guardrail: EIN einziger, delegierter Change-Handler auf document.
	// Er greift für alle aktuellen UND dynamisch hinzugefügten Elemente automatisch,
	// verhindert doppelte Bindings und ist robust gegen einzelne Fehler während des Bindens.
	if(window.__autosubmit_delegated) {
		return;
	}
	window.__autosubmit_delegated = true;

	$(document).on("change.autosubmit", identifier, function () {
		autosubmit_handle_change(this);
	});

	// Guardrail: Unvollständige Felder schon beim Tippen sichtbar rot markieren (ohne POST).
	// So ist sofort klar, warum noch nicht automatisch gespeichert wird.
	if(typeof window.validate_autosubmit === 'function') {
		$(document).on("input.autosubmit", identifier, function () {
			var $form = $(this).closest('form');
			if($form.length) {
				try { window.validate_autosubmit($form); } catch(e) {}
			}
		});
	}

}

$(document).ready(function(){
	autosubmit();

	// Feedback f\u00fcr klassische (nicht-autosubmit) Formulare: kurzer Info-Toast beim Absenden,
	// weil diese einen vollen Seiten-Reload ausl\u00f6sen und ohne Hinweis unklar ist, dass etwas passiert.
	$(document).on('submit', 'form:not(.form_autosubmit)', function() {
		var $form = $(this);
		if($form.attr('noautosubmit') !== undefined) return;
		// Nur Formulare, die echte Schreib-Aktionen sind (POST oder POST-Marker)
		var method = ($form.attr('method') || '').toLowerCase();
		if(method && method !== 'post') return;
		if(window.toastr && typeof window.toastr.info === 'function') {
			window.toastr.info('Wird gespeichert\u2026', '', { timeOut: 1500, positionClass: 'toast-top-right' });
		}
	});
});