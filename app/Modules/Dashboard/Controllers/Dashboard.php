<?php

/**
 * ============================================================
 * FILE GUIDE — Dashboard.php
 * Dashboard Controller
 *
 * Purpose:
 *     Halaman dashboard utama dengan metrik performa (cache-backed).
 * ============================================================
 */

/**
 * Dashboard Controller
 * Menampilkan halaman dashboard dengan system metrics dan performance monitoring
 * 
 * @author VKNewsoft - Newsoft Developer
 * @year 2025
 */

namespace App\Modules\Dashboard\Controllers;

class Dashboard extends \App\Modules\Common\Controllers\BaseController
{
	public function __construct()
	{
		parent::__construct();
		// HMVC asset load: dashboard stylesheet tetap lokal module agar tidak bergantung ke path legacy.
		$this->addStyle($this->moduleAsset('css/dashboard.css') . '?v=' . @filemtime(APPPATH . 'Modules/Dashboard/Assets/css/dashboard.css'));
	}

	public function index()
	{
		$this->data['title'] = 'Dashboard';
		
		// Get system performance metrics
		// Keep the initial HTML fast. Expensive OS metrics are fetched after paint
		// through the metrics endpoint instead of delaying the LCP response.
		$this->data['performance'] = $this->getPerformanceMetrics(false);
		
		// System info
		$total_space = disk_total_space(ROOTPATH);
		$free_space = disk_free_space(ROOTPATH);
		$used_space = $total_space - $free_space;
		
		$this->data['system_info'] = [
			'os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
			'php_version' => phpversion(),
			'server_software' => $this->request->getServer('SERVER_SOFTWARE') ?? 'Apache/2.4',
			'current_time' => date('Y-m-d H:i:s'),
			'timezone' => date_default_timezone_get(),
			'total_storage' => $this->formatBytes($total_space),
			'used_storage' => $this->formatBytes($used_space),
			'free_storage' => $this->formatBytes($free_space),
			'storage_percent' => round(($used_space / $total_space) * 100, 2)
		];
		
		$this->view('dashboard.php', $this->data);
	}
	
	private function getPerformanceMetrics(bool $collect = true)
	{
		$cacheKey = 'dashboard_performance_metrics';
		$cachedMetrics = cache()->get($cacheKey);
		if (is_array($cachedMetrics)) {
			return $cachedMetrics;
		}
		if (!$collect) {
			return [
				'cpu_usage' => 0,
				'memory_percent' => 0,
				'memory_used' => 'N/A',
				'memory_total' => 'N/A',
				'active_connections' => 0,
				'disk_read' => 'N/A',
				'disk_write' => 'N/A',
				'is_real' => false,
			];
		}

		$metrics = [];
		
		// CPU Usage (Windows)
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$wmi = @exec('wmic cpu get loadpercentage');
			$cpu = (int) preg_replace('/[^0-9]/', '', $wmi);
			$metrics['cpu_usage'] = $cpu;
			$metrics['cpu_available'] = $cpu > 0;
		} else {
			// Linux
			$load = sys_getloadavg();
			$metrics['cpu_usage'] = round($load[0] * 100 / 4, 2); // Assuming 4 cores
		}
		
		// Memory Usage
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$output = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
			if ($output) {
				preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
				preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);
				if (isset($free[1]) && isset($total[1])) {
					$free_mem = (int)$free[1] * 1024;
					$total_mem = (int)$total[1] * 1024;
					$used_mem = $total_mem - $free_mem;
					$metrics['memory_total'] = $this->formatBytes($total_mem);
					$metrics['memory_used'] = $this->formatBytes($used_mem);
					$metrics['memory_free'] = $this->formatBytes($free_mem);
					$metrics['memory_percent'] = round(($used_mem / $total_mem) * 100, 2);
					$metrics['memory_available'] = true;
				}
			}
		} else {
			// Linux
			$free = @shell_exec('free -b');
			if ($free) {
				$free = (string)trim($free);
				$free_arr = explode("\n", $free);
				$mem = explode(" ", preg_replace('/\s+/', ' ', $free_arr[1]));
				$total_mem = (int)$mem[1];
				$used_mem = (int)$mem[2];
				$free_mem = (int)$mem[3];
				$metrics['memory_total'] = $this->formatBytes($total_mem);
				$metrics['memory_used'] = $this->formatBytes($used_mem);
				$metrics['memory_free'] = $this->formatBytes($free_mem);
				$metrics['memory_percent'] = round(($used_mem / $total_mem) * 100, 2);
				$metrics['memory_available'] = true;
			}
		}
		
		// Do not show random values as real measurements.
		if (!isset($metrics['cpu_usage'])) {
			$metrics['cpu_usage'] = 0;
			$metrics['cpu_available'] = false;
		}
		if (!isset($metrics['memory_percent'])) {
			$metrics['memory_total'] = 'N/A';
			$metrics['memory_used'] = 'N/A';
			$metrics['memory_free'] = 'N/A';
			$metrics['memory_percent'] = 0;
			$metrics['memory_available'] = false;
		}
		
		// Network connections (active connections)
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$netstat = @exec('netstat -an | find /c "ESTABLISHED"');
			$metrics['active_connections'] = max(0, (int)$netstat);
		} else {
			$netstat = @exec('netstat -an | grep ESTABLISHED | wc -l');
			$metrics['active_connections'] = max(0, (int)$netstat);
		}
		
		// Disk I/O is not available reliably on all supported hosts.
		$metrics['disk_read'] = 'N/A';
		$metrics['disk_write'] = 'N/A';
		
		// Server uptime
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$uptime = @shell_exec('net statistics workstation | find "Statistics since"');
			$metrics['uptime'] = $uptime ? trim(str_replace('Statistics since', '', $uptime)) : 'N/A';
		} else {
			$uptime = @shell_exec('uptime -p');
			$metrics['uptime'] = $uptime ? trim($uptime) : 'N/A';
		}
		
		$metrics['is_real'] = ($metrics['cpu_available'] ?? false) || ($metrics['memory_available'] ?? false);
		cache()->save($cacheKey, $metrics, 5);

		return $metrics;
	}
	
	private function formatBytes($bytes, $precision = 2) {
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return round($bytes, $precision) . ' ' . $units[$pow];
	}
	
	public function metrics()
	{
		// Return JSON for realtime updates
		$performance = $this->getPerformanceMetrics(true);
		
		// Return only the values needed for charts
		return $this->response->setJSON([
			'cpu_usage' => $performance['cpu_usage'],
			'memory_percent' => $performance['memory_percent'],
			'memory_used' => $performance['memory_used'],
			'memory_total' => $performance['memory_total'],
			'active_connections' => $performance['active_connections'],
			'disk_read' => str_replace(' MB/s', '', $performance['disk_read']),
			'disk_write' => str_replace(' MB/s', '', $performance['disk_write']),
			'is_real' => (bool) ($performance['is_real'] ?? false)
		]);
	}
}
