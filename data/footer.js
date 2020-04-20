function colorHashMe () {
	$(".colorhashme").each(function () {
		var input = this;
		var colorHash = new ColorHash();
		var str = input.innerHTML;
		var hex = colorHash.hex(str);
		input.innerHTML = '<span class="hexcolored" style="color: ' + hex.toUpperCase() + ' !important;">' + str + '</span>';
	});
}

function initialize_toggle () {
	$('a[id^="toggle_details_"]').each(function (index) {
		document.addEventListener('DOMContentLoaded', function () {
			this.addEventListener('click',
				function () {
					console.log(this);
					$(this).toggle
				}
			);
		});
	});
	return 1;
}

$(document).ready(function() {
	colorHashMe();
	initialize_toggle();
});
