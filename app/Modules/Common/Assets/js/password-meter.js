/**
 * ============================================================
 * FILE GUIDE — password-meter.js
 * Password Meter JS
 *
 * Purpose:
 *     Wrapper pwstrength untuk indikator kekuatan password form login/register.
 *
 * Important:
 *     - Dimuat via addJs() oleh controller module; bergantung global
 *       base_url/current_url dari layout dan fungsi shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

jQuery(document).ready(function () {
	"use strict";
	var options = {};
	options.ui = {
		container: "#pwd-container",
		viewports: {
			progress: ".pwstrength_viewport_progress"
		},
		showVerdictsInsideProgressBar: true
	};
	options.common = {
		debug: true,
		onLoad: function () {
			$('#messages').text('Start typing password');
		}
	};
	$(':password').pwstrength(options);
});