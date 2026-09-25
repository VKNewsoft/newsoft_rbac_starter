/**
 * ============================================================
 * FILE GUIDE — identitas.js
 * Identitas JS
 *
 * Purpose:
 *     Interaksi form identitas perusahaan (dependent dropdown wilayah).
 *
 * Important:
 *     - Dimuat via addJs() oleh controller module; bergantung global
 *       base_url/current_url dari layout dan fungsi shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

$(document).ready(function() {
	const $selects = $('.select2');
	if (!$selects.length) {
		return;
	}

	if (window.NSModulePerformance && typeof window.NSModulePerformance.initSelect2 === 'function') {
		window.NSModulePerformance.initSelect2($(document));
		return;
	}

	if ($.fn.select2) {
		$selects.select2({ theme: 'bootstrap-5' });
	}
});
