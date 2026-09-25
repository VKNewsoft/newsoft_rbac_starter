<?php

/**
 * ==========================================================================
 * FILE GUIDE — User_role.php
 * ==========================================================================
 *
 * Controller halaman User Role (Builtin).
 *
 * Purpose:
 *     Menampilkan daftar user + role-nya, dan memproses perubahan
 *     assignment role (add/remove) per user.
 *
 * Responsibilities:
 *     - Render halaman list (index) dengan DataTable server-side.
 *     - Endpoint AJAX: getDataDT (DataTable), checkbox (popup form),
 *       edit (simpan assignment), delete (hapus satu assignment).
 *
 * Important:
 *     - getDataDT() harus selalu mengembalikan struktur DataTables:
 *       {draw, recordsTotal, recordsFiltered, data[]} — jangan ubah kontrak
 *       response tanpa menyesuaikan user-role.js.
 *
 * Related:
 *     - App\Modules\Builtin\Models\UserRoleModel
 *     - app/Modules/Common/Assets/builtin/js/user-role.js
 *     - app/Modules/Builtin/Views/builtin/user-role.php
 *
 * @author VKNewsoft - Newsoft Developer, 2025
 * ==========================================================================
 */

namespace App\Modules\Builtin\Controllers;

use App\Modules\Builtin\Models\UserRoleModel;

class User_role extends \App\Modules\Common\Controllers\BaseController
{
	protected $model;
	private $formValidation;

	public function __construct() {
		parent::__construct();

		$resultPageVersion = '?v=' . @filemtime(APPPATH . 'Modules/Common/Assets/css/result-page.css');
		$resultTableVersion = '?v=' . @filemtime(APPPATH . 'Modules/Common/Assets/js/result-table.js');

		// HMVC asset load: table wrapper shared dari Common, aksi user-role pakai script builtin shared.
		$this->addJs($this->commonAsset('js/result-table.js') . $resultTableVersion);
		$this->addJs($this->commonAsset('builtin/js/user-role.js') . '?v=' . @filemtime(APPPATH . 'Modules/Common/Assets/builtin/js/user-role.js'));
		$this->addStyle($this->commonAsset('css/result-page.css') . $resultPageVersion);
		$this->addStyle($this->config->baseURL . 'public/vendors/wdi/wdi-loader.css');

		$this->model = new UserRoleModel;
		$this->data['site_title'] = 'User Role';

		$roles = $this->model->getAllRole();
		foreach ($roles as $row) {
			$this->data['roles'][$row['id_role']] = $row;
		}
	}


	// ======================================================================
	// PAGE
	// ======================================================================

	/**
	 * Halaman list user-role dengan DataTable server-side.
	 */
	public function index()
	{
		$this->hasPermission('read_all');

		$data = $this->data;
		if ($this->request->getPost('delete')) {
			$result = $this->model->deleteData();

			if ($result) {
				$data['msg'] = ['status' => 'ok', 'message' => 'Data user-role berhasil dihapus'];
			} else {
				$data['msg'] = ['status' => 'error', 'message' => 'Data user-role gagal dihapus'];
			}
		}

		$data['users'] = $this->model->getAllUser();
		$this->view('builtin/user-role.php', $data);
	}


	// ======================================================================
	// AJAX / DATATABLE
	// ======================================================================

	/**
	 * Endpoint DataTable (server-side processing) untuk halaman user-role.
	 * Response wajib: {draw, recordsTotal, recordsFiltered, data[]}.
	 */
	public function getDataDT() {
		$this->hasPermission('read_all');

		$numData = $this->model->countAllData();
		$result['draw'] = $start = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $numData;

		$query = $this->model->getListData();
		$result['recordsFiltered'] = $query['total_filtered'];
				
		helper('html');
	
		$userRole = [];
		$userIds = array_column($query['data'], 'id_user');
		// Assignment role hanya diambil untuk user yang tampil pada page aktif
		// agar request awal tidak melakukan full scan relasi user-role.
		$userRoleAll = $this->model->getUserRoleByUserIds($userIds);
		foreach($userRoleAll as $row) {
			$userRole[$row['id_user']][] = $row;
		}
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) 
		{
			
			$listRole = '';
			if (key_exists($val['id_user'], $userRole)) {
				$roles = $userRole[$val['id_user']];
				foreach ($roles as $role) 
				{
					$listRole .= '<span class="badge badge-secondary badge-role px-3 py-2 me-1 mb-1 pe-4">' . $role['judul_role'] . '<a data-action="remove-role" data-id-user="'.$val['id_user'].'" data-role-id="'.$role['id_role'].'" href="javascript:void(0)" class="text-danger"><i class="fas fa-times"></i></a></span>';
				}
			}
			
			$val['ignore_role'] = $listRole;
			$val['ignore_no_urut'] = $no;
			$val['ignore_action'] = btn_dropdown_actions([
				['type' => 'button', 'icon' => 'fas fa-edit text-success', 'label' => 'Edit', 'attrs' => ['class' => 'btn-edit', 'data-id-user' => $val['id_user']]],
			]);
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}


	// ======================================================================
	// CRUD
	// ======================================================================

	/**
	 * Popup form edit role (dipanggil AJAX dari user-role.js).
	 */
	public function checkbox() {
		$userRole = $this->model->getUserRoleByID($this->request->getGet('id'));
		$this->data['user_role'] = $userRole;

		echo $this->fetchView('builtin/user-role-form.php', $this->data);
	}

	/**
	 * Hapus satu assignment role dari user (AJAX).
	 */
	public function delete() {
		if ($this->request->getPost('id_user')) 
		{
			$result = $this->model->deleteData();
			if ($result) {
				$message = ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
			} else {
				$message = ['status' => 'error', 'message' => 'Data gagal dihapus'];
			}
			echo json_encode($message);
		}
	}

	/**
	 * Simpan (replace) seluruh assignment role user (AJAX).
	 */
	public function edit() 
	{
		if ($this->request->getPost('id_user')) 
		{	
			$result = $this->model->saveData();
			
			if ($result) {
				$message = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
			} else {
				$message = ['status' => 'error', 'message' => 'Data gagal disimpan'];
			}
		
			echo json_encode($message);
		}
	}
	
}
