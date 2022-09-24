<?php
	header('Content-Type: application/javascript');
?>

toastr.options = {
	"closeButton": false,
	"debug": false,
	"newestOnTop": true,
	"progressBar": true,
	"positionClass": "toast-bottom-full-width",
	"preventDuplicates": true,
	"onclick": null,
	"showDuration": "300",
	"hideDuration": "1000",
	"timeOut": "5000",
	"extendedTimeOut": "10000",
	"showEasing": "swing",
	"hideEasing": "linear",
	"showMethod": "fadeIn",
	"hideMethod": "fadeOut"
};
function success (title, msg) {
	if(!title) {
		title = msg;
	}
	if(!title) {
		log("Empty msg and title");
	}
	toastr["success"](msg, title);
}



function error (title, msg) {
	if(!title) {
		title = msg;
	}
	if(!title) {
		log("Empty msg and title");
	}
	toastr["error"](msg, title);
}
