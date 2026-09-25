/**
 * ============================================================
 * FILE GUIDE — admin-role.js
 * Admin Role JS
 *
 * Purpose:
 *     Interaksi list & form role pada area administrator.
 *
 * Important:
 *     - Dimuat via addJs() oleh controller Builtin terkait; bergantung
 *       global base_url/current_url dari layout dan shared functions.js.
 *     - Jangan mengubah selector/endpoint tanpa cek view & controller
 *       terkait (lihat FILE GUIDE controller module yang sama).
 * ============================================================
 */

$(document).ready(function() {
	$('.switch').change(function()
	{
		var role_id = $(this).data('role-id');
		var status_id = $(this).is(':checked') ? 1 : 0;
		var name = $(this).attr('name');
		$.ajax({
			type: "POST",
			url: module_url,
			data: 'role_id=' + role_id + '&status_id=' + status_id + '&name=' + name + '&change_status=1&ajax=true',
			dataType: 'text',
			success: function(data) {
				// console.log(data);
				if (data == 'error') {
					Swal.fire({
						title: 'Error !!!',
						text: "Gagal update data",
						icon: 'error',
						showCloseButton: true,
						confirmButtonText: 'OK'
					})
				} 
			},
			error: function(xhr) {
				// console.log(xhr);
			}
		});
	})
});