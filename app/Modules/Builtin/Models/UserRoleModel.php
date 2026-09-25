<?php

/**
 * ==========================================================================
 * FILE GUIDE — UserRoleModel.php
 * ==========================================================================
 *
 * Model User Role
 *
 * Purpose:
 *     Mengelola assignment role kepada user. Satu user bisa memiliki
 *     multiple roles untuk akses yang lebih fleksibel.
 *
 * Responsibilities:
 *     - CRUD relasi core_user_role.
 *     - Menyediakan data DataTable (server-side) untuk halaman user-role.
 *
 * Important:
 *     - getListData() HANYA menerima kolom yang benar-benar ada di tabel
 *       core_user (whitelist $allowedColumns) — jangan menambah kolom yang
 *       tidak ada di skema (lihat app/Database/newsoft_base.sql).
 *
 * Related:
 *     - app/Modules/Builtin/Controllers/User_role.php
 *     - app/Modules/Common/Assets/builtin/js/user-role.js
 *
 * @package App\Models\Builtin
 * @author  Newsoft Developer
 * @copyright 2020-2023
 * ==========================================================================
 */

namespace App\Modules\Builtin\Models;

class UserRoleModel extends \App\Modules\Common\Models\BaseModel
{

	// ======================================================================
	// ROLE & USER LOOKUP
	// ======================================================================

	/**
	 * Mendapatkan semua role.
	 *
	 * @return array Daftar semua role
	 */
	public function getAllRole() {
		return $this->db->table('core_role')->get()->getResultArray();
	}

	/**
	 * Mendapatkan semua user role beserta detailnya (join core_role).
	 *
	 * @return array Daftar user role
	 */
	public function getUserRole() {
		return $this->db->table('core_user_role')
			->select('core_user_role.*, core_role.*')
			->join('core_role', 'core_role.id_role = core_user_role.id_role', 'left')
			->get()
			->getResultArray();
	}

	/**
	 * Ambil relasi user-role hanya untuk user pada halaman aktif agar
	 * DataTable tidak memuat seluruh assignment role setiap request.
	 *
	 * @param array $userIds
	 * @return array
	 */
	public function getUserRoleByUserIds(array $userIds) {
		$userIds = array_filter(array_map('intval', $userIds));
		if (!$userIds) {
			return [];
		}

		return $this->db->table('core_user_role')
			->select('core_user_role.id_user, core_user_role.id_role, core_role.judul_role')
			->join('core_role', 'core_role.id_role = core_user_role.id_role', 'left')
			->whereIn('core_user_role.id_user', $userIds)
			->orderBy('core_role.judul_role', 'ASC')
			->get()
			->getResultArray();
	}
	
	/**
	 * Mendapatkan role yang dimiliki user berdasarkan ID.
	 *
	 * @param int $id ID user
	 * @return array Daftar role user
	 */
	public function getUserRoleByID($id) {
		return $this->db->table('core_user_role')
			->where('id_user', $id)
			->get()
			->getResultArray();
	}

	/**
	 * Mendapatkan semua user.
	 *
	 * @return array Daftar semua user
	 */
	public function getAllUser() {
		return $this->db->table('core_user')->get()->getResultArray();
	}


	// ======================================================================
	// CRUD
	// ======================================================================

	/**
	 * Hapus satu role dari user.
	 *
	 * @return int Jumlah baris yang terpengaruh
	 */
	public function deleteData() {
		$idUser = $this->request->getPost('id_user');
		$idRole = $this->request->getPost('id_role');
		
		$this->db->table('core_user_role')
			->where('id_user', $idUser)
			->where('id_role', $idRole)
			->delete();
			
		return $this->db->affectedRows();
	}
	
	/**
	 * Simpan role untuk user (replace semua role lama dengan role baru)
	 * 
	 * @return bool Status transaksi
	 */
	public function saveData() 
	{
		$idUser = $this->request->getPost('id_user');
		$idRoles = $this->request->getPost('id_role') ?? [];
		
		$this->db->transStart();
		
		// Hapus semua role user yang lama
		$this->db->table('core_user_role')->where('id_user', $idUser)->delete();
		
		// Insert role baru
		if (!empty($idRoles)) {
			$insert = [];
			foreach ($idRoles as $key => $idRole) {
				$insert[] = ['id_user' => $idUser, 'id_role' => $idRole];
			}
			$this->db->table('core_user_role')->insertBatch($insert);
		}
		
		$this->db->transComplete();
		return $this->db->transStatus();
	}
	

	// ======================================================================
	// DATATABLE (server-side)
	// ======================================================================

	/**
	 * Hitung total user aktif (belum dihapus).
	 *
	 * @return int Jumlah user aktif
	 */
	public function countAllData() {
		return $this->db->table('core_user')
			->where('isDeleted', 0)
			->countAllResults();
	}

	/**
	 * Data user untuk DataTables dengan filter, search, sorting, pagination.
	 *
	 * @return array ['data' => rows, 'total_filtered' => int]
	 */
	public function getListData() {
		$columns = $this->request->getPost('columns') ?: [];
		// HANYA kolom yang benar-benar ada di tabel core_user (lihat
		// app/Database/newsoft_base.sql). 'created_at' pernah dipakai di sini
		// padahal skema memakai 'created' — memicu SQL error "Unknown column
		// 'created_at' in 'field list'" pada endpoint builtin/user-role/getDataDT.
		$allowedColumns = ['id_user', 'username', 'email', 'nama', 'created'];
		$builder = $this->db->table('core_user')
			// Ambil field inti saja agar pagination server-side tetap efisien.
			->select('id_user, username, email, nama, created')
			->where('isDeleted', 0);
		
		$searchAll = $this->request->getPost('search')['value'] ?? '';
		if ($searchAll) {
			// Kumpulkan dulu kolom whitelisted, baru bangun grup LIKE. Tanpa
			// pengaman ini, search tanpa kolom valid menghasilkan "AND ()"
			// yang invalid di MySQL/MariaDB.
			$likeColumns = [];
			foreach ($columns as $val) {
				$columnName = $val['data'] ?? '';
				if (strpos($columnName, 'ignore') !== false || !in_array($columnName, $allowedColumns, true)) {
					continue;
				}
				$likeColumns[] = $columnName;
			}

			if ($likeColumns) {
				$builder->groupStart();
				foreach ($likeColumns as $index => $columnName) {
					if ($index === 0) {
						$builder->like($columnName, $searchAll);
					} else {
						$builder->orLike($columnName, $searchAll);
					}
				}
				$builder->groupEnd();
			}
		}
		
		$totalFiltered = $builder->countAllResults(false);
		
		$orderData = $this->request->getPost('order');
		$columnsPost = $this->request->getPost('columns');
		
		if (!empty($orderData) && !empty($columnsPost[$orderData[0]['column']]['data'])) {
			$columnName = $columnsPost[$orderData[0]['column']]['data'];
			
			if (strpos($columnName, 'ignore') === false) {
				if (in_array($columnName, $allowedColumns, true)) {
					$direction = strtoupper($orderData[0]['dir']) === 'DESC' ? 'DESC' : 'ASC';
					$builder->orderBy($columnName, $direction);
				}
			}
		}

		$start = (int) ($this->request->getPost('start') ?: 0);
		$length = (int) ($this->request->getPost('length') ?: 10);
		$data = $builder->limit($length, $start)->get()->getResultArray();

		return ['data' => $data, 'total_filtered' => $totalFiltered];
	}
}
?>
