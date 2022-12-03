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
					$.ajax({
						url : submitfile,
						type: "POST",
						data: data,
						success: function (response) {
							success("OK", response);
							if($(".auto_reload_stylesheets").length != 0) {
								reloadStylesheets();
							}
						},
						error: function (response, textStatus, errorThrown) {
							log(response);
							error("FEHLER", response.responseText);
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
});
