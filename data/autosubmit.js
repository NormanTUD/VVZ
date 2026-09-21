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

function autosubmit (identifier=".form_autosubmit, :input") {
	$(identifier).each(function (index) {
		if(!$(this).attr('noautosubmit')) {
			$(this).change(function (index) {
				var loc = window.location.pathname;
				var dir = window.location.protocol  + "//" + window.location.host + "/" + loc.substring(0, loc.lastIndexOf('/'));
				var submitfile = dir + '/submit.php';

				var data = $(this.form).serialize();
				if(!data) {
					data = $(this).serialize();
				}

				if(data) {
					var $changedField = $(this);
					var fieldName = $changedField.attr('name') || $changedField.attr('id') || 'Feld';

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
				} else {
					log("autosubmit.js: data was empty: ", this);
				}
			});
		}
	});

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
