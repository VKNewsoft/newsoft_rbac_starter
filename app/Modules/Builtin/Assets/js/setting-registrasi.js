/**
 * ============================================================
 * FILE GUIDE — setting-registrasi.js
 * Setting Registrasi JS
 *
 * Purpose:
 *     Interaksi form setting registrasi (metode aktivasi, role/module default).
 *
 * Important:
 *     - Dimuat via addJs() oleh controller module; bergantung global
 *       base_url/current_url dari layout dan fungsi shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

jQuery(document).ready(function () {
	$('select[name="enable"]').change(function(){
		if (this.value == 'N') {
			$('.detail-container').hide();
		} else {
			$('.detail-container').show();
		}
	});
});
