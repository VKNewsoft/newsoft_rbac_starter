/**
 * ============================================================
 * FILE GUIDE — nsdfilepicker-defaults.js
 * NSD Filepicker Defaults JS
 *
 * Purpose:
 *     Konfigurasi default plugin nsdfilepicker (bahasa, ikon, limit).
 *
 * Important:
 *     - Dimuat via addJs() oleh controller module; bergantung global
 *       base_url/current_url dari layout dan fungsi shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

$(document).ready(function() {
	nsdfilepicker.setDefaults({
		server_url : filepicker_server_url, // filepicker_upload_path ada di header.php
		icon_url : filepicker_icon_url
	});
});