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

	try {
		var base = window.location.pathname.slice(0, window.location.pathname.lastIndexOf('/') + 1);
		var submitfile = window.location.protocol  + "//" + window.location.host + base + 'submit.php';

		var $form = $changedField.closest('form');
		var data = $form.length ? $form.serialize() : $changedField.serialize();
		if(!data) {
			return;
		}

		// Guardrail: Eine fehlerhafte Validierung darf das Speichern nie dauerhaft verhindern.
		var blocked = false;
		if(typeof window.validate_autosubmit === 'function') {
			try {
				if(window.validate_autosubmit($form.length ? $form : $changedField) === false) {
					blocked = true;
				}
			} catch(e) {
				var log = window.log;
				if(typeof log === 'function') {
					log("autosubmit.js: validate_autosubmit Fehler: ", e);
				}
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
				try {
					var fb = autosubmit_extract_feedback(response);
					if(typeof window.success === 'function') {
						window.success(fb.message || fb.title, fb.message ? fb.title : '');
					}
					if($(".auto_reload_stylesheets").length != 0 && typeof window.reloadStylesheets === 'function') {
						reloadStylesheets();
					}
				} catch(e) {
					var log2 = window.log;
					if(typeof log2 === 'function') {
						log2("autosubmit.js: success-Behandlung Fehler: ", e);
					}
				}
			},
			error: function (response, textStatus, errorThrown) {
				var log3 = window.log;
				if(typeof log3 === 'function') {
					log3(response);
				}
				try {
					if(typeof window.error === 'function') {
						window.error("FEHLER", "Das automatische Speichern ist fehlgeschlagen. Bitte pr\u00fcfen Sie Ihre Eingaben.");
					}
				} catch(e) {}
			}
		});
	} catch(e) {
		var log4 = window.log;
		if(typeof log4 === 'function') {
			log4("autosubmit.js: Fehler beim Verarbeiten der Änderung: ", e);
		}
	}
}

function autosubmit (identifier=".form_autosubmit, :input") {
	// Guardrail: EIN delegierter Change-Handler. Wird sofort beim Laden aktiv,
	// NICHT erst bei DOM-ready — so kann ein Fehler in einem anderen ready-Handler
	// das automatische Speichern nie verhindern. Erfasst auch nachträglich
	// hinzugefügte Zeilen (neue Termine).
	if(window.__autosubmit_delegated) {
		return;
	}
	window.__autosubmit_delegated = true;

	$(document).on("change.autosubmit", identifier, function () {
		autosubmit_handle_change(this);
	});

	// Guardrail: Unvollständige Felder schon beim Tippen rot markieren (ohne POST).
	if(typeof window.validate_autosubmit === 'function') {
		$(document).on("input.autosubmit", identifier, function () {
			var $form = $(this).closest('form');
			if($form.length) {
				try { window.validate_autosubmit($form); } catch(e) {}
			}
		});
	}
}

// Sofort binden (nicht auf document.ready warten).
autosubmit();

$(document).ready(function(){
	autosubmit();

	// Feedback f\u00fcr klassische (nicht-autosubmit) Formulare: kurzer Info-Toast beim Absenden,
	// weil diese einen vollen Seiten-Reload ausl\u00f6sen und ohne Hinweis unklar ist, dass etwas passiert.
	$(document).on('submit', 'form:not(.form_autosubmit)', function() {
		var $form = $(this);
		if($form.attr('noautosubmit') !== undefined) return;
		var method = ($form.attr('method') || '').toLowerCase();
		if(method && method !== 'post') return;
		if(window.toastr && typeof window.toastr.info === 'function') {
			window.toastr.info('Wird gespeichert\u2026', '', { timeOut: 1500, positionClass: 'toast-top-right' });
		}
	});
});