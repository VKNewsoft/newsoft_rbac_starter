<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveProjectManagementModule extends Migration
{
    private array $moduleNames = [
        'project',
        'project-category',
        'project-member',
        'task-management',
    ];

    public function up()
    {
        $moduleRows = $this->db->table('core_module')
            ->select('id_module')
            ->whereIn('nama_module', $this->moduleNames)
            ->get()
            ->getResultArray();
        $moduleIds = array_map(static fn (array $row): int => (int) $row['id_module'], $moduleRows);

        $menuRows = $this->db->table('core_menu')
            ->select('id_menu')
            ->groupStart()
                ->whereIn('url', ['project', 'project-category', 'project-member', 'task-management'])
                ->orGroupStart()
                    ->where('nama_menu', 'Project')
                    ->where('url', '#')
                ->groupEnd()
            ->groupEnd()
            ->get()
            ->getResultArray();
        $menuIds = array_map(static fn (array $row): int => (int) $row['id_menu'], $menuRows);

        if ($menuIds) {
            $this->db->table('core_menu_role')->whereIn('id_menu', $menuIds)->delete();
            $this->db->table('core_menu')->whereIn('id_menu', $menuIds)->delete();
        }

        if ($moduleIds) {
            $permissionRows = $this->db->table('core_module_permission')
                ->select('id_module_permission')
                ->whereIn('id_module', $moduleIds)
                ->get()
                ->getResultArray();
            $permissionIds = array_map(static fn (array $row): int => (int) $row['id_module_permission'], $permissionRows);

            if ($permissionIds) {
                $this->db->table('core_role_module_permission')
                    ->whereIn('id_module_permission', $permissionIds)
                    ->delete();
                $this->db->table('core_module_permission')
                    ->whereIn('id_module_permission', $permissionIds)
                    ->delete();
            }

            $this->db->table('core_module')->whereIn('id_module', $moduleIds)->delete();
        }

        // Drop dependent tables first so existing foreign keys cannot block synchronization.
        $this->forge->dropTable('project_task_token_usage', true);
        $this->forge->dropTable('project_task', true);
        $this->forge->dropTable('project_member', true);
        $this->forge->dropTable('project', true);
        $this->forge->dropTable('project_category', true);
    }

    public function down()
    {
        // The removed feature is intentionally not recreated on rollback.
    }
}
