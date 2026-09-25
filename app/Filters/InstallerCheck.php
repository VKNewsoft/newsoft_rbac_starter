<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Installer Check Filter
 * Redirect ke installer jika database belum terkonfigurasi
 * 
 * @author VKNewsoft - Newsoft Developer, 2025
 */
class InstallerCheck implements FilterInterface
{
	public function before(RequestInterface $request, $arguments = null)
	{
		// Skip jika sedang akses installer atau asset files.
		$uri = $request->getUri()->getPath();
		
		if (strpos($uri, 'installer') !== false || 
			strpos($uri, 'module-assets/') !== false ||
			strpos($uri, 'public/') !== false ||
			strpos($uri, '.css') !== false ||
			strpos($uri, '.js') !== false ||
			strpos($uri, '.png') !== false ||
			strpos($uri, '.jpg') !== false) {
			return;
		}

		$configFile = APPPATH . 'Config/Database.php';
		if (!is_file($configFile)) {
			$request->installerMode = true;
			return redirect()->to(base_url('installer'));
		}

		helper('app_runtime');

		if (!app_database_runtime_status()) {
			$request->installerMode = true;
			return redirect()->to(base_url('installer'));
		}
	}

	public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
	{
		// Nothing to do here
	}
}
