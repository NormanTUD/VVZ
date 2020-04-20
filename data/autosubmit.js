$(document).ready(function(){
	function show_info (message, time) {
		if($('.info').is(':visible')) {
			$("<div class='info'>" + message + "</div>").appendTo("body").delay(time).hide(0);;
		} else {
			$("<div class='info'>" + message + "</div>").appendTo("body").delay(time).hide(0);;
		}

	}

	$(".form_autosubmit, :input").each(function (index) {
		if(!$(this).attr('noautosubmit')) {
			$(this).change(function (index) {
				var loc = window.location.pathname;
				var dir = loc.substring(0, loc.lastIndexOf('/'));
				var submitfile = dir + '/submit.php';
				$.ajax({
					url : submitfile,
					type: "POST",
					data: $(this.form).serialize(),
					success: function (data) {
						show_info(data, 2000);
					},
					error: function (jXHR, textStatus, errorThrown) {
						show_info(errorThrown, 10000);
					}
				});
			});
		}
	});
});
