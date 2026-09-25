<?php

/**
 * ============================================================
 * FILE GUIDE — Installer.php
 * Installer Controller
 *
 * Purpose:
 *     Web installer: validasi input DB, import newsoft_base.sql, update Config/Database.php.
 * ============================================================
 */

namespace App\Modules\Installer\Controllers;

use CodeIgniter\Controller;

/**
 * Database Installer Controller
 * 
 * @author VKNewsoft - Newsoft Developer, 2025
 */
class Installer extends Controller
{
    protected $helpers = ['form'];

    private function escapeConfigValue(string $value): string
    {
        return var_export($value, true);
    }

    private function getDatabaseConfigTemplate(): string
    {
        return <<<'PHP'
<?php namespace Config;

/**
 * Database Configuration
 *
 * @package Config
 */

class Database extends \CodeIgniter\Database\Config
{
	public $filesPath = APPPATH . 'Database/';

	public $migrationsNamespace = [
		'App',
	];

	public $defaultGroup = 'default';

	public $default = [
		'DSN'      => '',
		'hostname' => 'localhost',
		'username' => 'root',
		'password' => '',
		'database' => '',
		'DBDriver' => 'MySQLi',
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => false,
		'cacheDir' => '',
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port' => 3306,
	];

	public $tests = [
		'DSN'      => '',
		'hostname' => '127.0.0.1',
		'username' => '',
		'password' => '',
		'database' => ':memory:',
		'DBDriver' => 'SQLite3',
		'DBPrefix' => 'db_',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => false,
		'cacheDir' => '',
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	public function __construct()
	{
		parent::__construct();

		if (ENVIRONMENT === 'testing')
		{
			$this->defaultGroup = 'tests';

			if ($group = getenv('DB'))
			{
				if (is_file(TESTPATH . 'travis/Database.php'))
				{
					require TESTPATH . 'travis/Database.php';

					if (! empty($dbconfig) && array_key_exists($group, $dbconfig))
					{
						$this->tests = $dbconfig[$group];
					}
				}
			}
		}
	}
}
PHP;
    }

    private function ensureWritableDirectories(): bool
    {
        $directories = [
            WRITEPATH,
            WRITEPATH . 'session',
            WRITEPATH . 'cache',
            WRITEPATH . 'debugbar',
            WRITEPATH . 'logs',
            WRITEPATH . 'uploads',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                return false;
            }

            $indexFile = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'index.html';
            if (!file_exists($indexFile) && file_put_contents($indexFile, '') === false) {
                return false;
            }
        }

        return true;
    }

    private function ensureDatabaseConfigFile(): bool
    {
        $configFile = APPPATH . 'Config/Database.php';
        if (file_exists($configFile)) {
            return true;
        }

        return file_put_contents($configFile, $this->getDatabaseConfigTemplate()) !== false;
    }

    private function ensureInstallerPrerequisites(): bool
    {
        return $this->ensureWritableDirectories() && $this->ensureDatabaseConfigFile();
    }

    /**
     * Split a SQL dump without splitting semicolons inside quoted values.
     * mysqldump files can contain large INSERT statements, so they are sent
     * separately instead of as one multi_query packet.
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $character = $sql[$i];
            $statement .= $character;

            if ($quote !== null) {
                if ($character === '\\' && $i + 1 < $length) {
                    $statement .= $sql[++$i];
                } elseif ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
            } elseif ($character === ';') {
                $sqlStatement = trim($statement, " \t\r\n;");
                if ($sqlStatement !== '') {
                    $statements[] = $sqlStatement;
                }
                $statement = '';
            }
        }

        $sqlStatement = trim($statement);
        if ($sqlStatement !== '') {
            $statements[] = $sqlStatement;
        }

        return $statements;
    }

    /**
     * Break INSERT ... VALUES statements into smaller requests.
     */
    private function splitInsertStatement(string $statement, int $rowsPerBatch = 250): array
    {
        if (!preg_match('/\bINSERT\s+INTO\s+/i', $statement)) {
            return [$statement];
        }

        $valuesPosition = preg_match('/\bVALUES\b/i', $statement, $match, PREG_OFFSET_CAPTURE);
        if ($valuesPosition !== 1) {
            return [$statement];
        }

        $valuesStart = $match[0][1] + strlen($match[0][0]);
        $prefix = substr($statement, 0, $valuesStart);
        $values = trim(substr($statement, $valuesStart));
        $rows = [];
        $row = '';
        $depth = 0;
        $quote = null;
        $length = strlen($values);

        for ($i = 0; $i < $length; $i++) {
            $character = $values[$i];

            if ($quote !== null) {
                $row .= $character;
                if ($character === '\\' && $i + 1 < $length) {
                    $row .= $values[++$i];
                } elseif ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                $row .= $character;
            } elseif ($character === '(') {
                $depth++;
                $row .= $character;
            } elseif ($character === ')') {
                $depth--;
                $row .= $character;
            } elseif ($character === ',' && $depth === 0) {
                $rows[] = trim($row);
                $row = '';
            } else {
                $row .= $character;
            }
        }

        if (trim($row) !== '') {
            $rows[] = trim($row);
        }

        if (count($rows) <= $rowsPerBatch || count($rows) === 0) {
            return [$statement];
        }

        $batches = [];
        foreach (array_chunk($rows, $rowsPerBatch) as $batch) {
            $batches[] = $prefix . ' ' . implode(',', $batch);
        }

        return $batches;
    }

    public function index()
    {
        if (!$this->ensureInstallerPrerequisites()) {
            return redirect()->back()->with('error', 'Gagal menyiapkan file instalasi awal.');
        }

        // Cek apakah database sudah terkonfigurasi dan bisa diakses
        if ($this->isDatabaseConfigured()) {
            return redirect()->to('/');
        }

        // Load view installer
        return view('installer/index');
    }

    public function install()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/installer');
        }

        if (!$this->ensureInstallerPrerequisites()) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyiapkan file/folder instalasi.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'db_host' => 'required',
            'db_username' => 'required',
            'db_password' => 'permit_empty',
            'db_name' => 'required|regex_match[/^[A-Za-z0-9_]+$/]',
            'db_port' => 'required|numeric'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Stream progress as NDJSON so the browser can display the real import status.
        set_time_limit(0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        @ini_set('output_buffering', '0');
        @ini_set('zlib.output_compression', '0');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Content-Encoding: none');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $sendProgress = static function (int $percent, string $process, string $message = ''): void {
            $payload = json_encode([
                'type' => 'progress',
                'percent' => $percent,
                'process' => $process,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE) . "\n";
            // PHP/Apache may buffer small chunks; padding makes each event flush immediately.
            echo str_pad($payload, 4096, ' ');
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();
        };

        $sendError = static function (string $message) use ($sendProgress): void {
            $sendProgress(0, 'Instalasi gagal', $message);
            echo json_encode(['type' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE) . "\n";
            flush();
        };

        $sendProgress(5, 'Memvalidasi koneksi database', 'Menghubungkan ke server MySQL...');

        $data = [
            'hostname' => $this->request->getPost('db_host'),
            'username' => $this->request->getPost('db_username'),
            'password' => $this->request->getPost('db_password'),
            'database' => $this->request->getPost('db_name'),
            'port' => $this->request->getPost('db_port'),
            'driver' => 'MySQLi',
        ];

        // Test koneksi
        try {
            $db = new \mysqli(
                $data['hostname'], 
                $data['username'], 
                $data['password'],
                '',
                $data['port']
            );

            if ($db->connect_error) {
                $sendError('Koneksi database gagal: ' . $db->connect_error);
                return;
            }

            $sendProgress(12, 'Koneksi database berhasil', 'Koneksi ke server MySQL berhasil.');

            // Create database jika belum ada
            $dbName = $data['database'];
            if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
                $db->close();
                $sendError('Nama database tidak valid.');
                return;
            }

            $dbName = $db->real_escape_string($dbName);
            if (!$db->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") || !$db->select_db($dbName)) {
                $error = $db->error;
                $db->close();
                $sendError('Gagal membuat database: ' . $error);
                return;
            }
            $sendProgress(20, 'Mempersiapkan database', "Database {$data['database']} siap digunakan.");

            // Import SQL file
            $sqlFile = APPPATH . 'Database/newsoft_base.sql';
            if (!file_exists($sqlFile)) {
                $sendError('File newsoft_base.sql tidak ditemukan!');
                return;
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                $sendError('File newsoft_base.sql gagal dibaca!');
                return;
            }

            $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
            $statements = $this->splitSqlStatements($sql);
            $sendProgress(25, 'Membaca struktur dan data', 'File SQL berhasil dibaca.');
            
            // Execute statements separately so large dump data does not exceed
            // MySQL's max_allowed_packet limit.
            $db->query('SET FOREIGN_KEY_CHECKS=0');
            $db->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"');
            $db->query('SET AUTOCOMMIT=0');
            $db->query('START TRANSACTION');

            $totalBatches = 0;
            foreach ($statements as $statement) {
                $totalBatches += count($this->splitInsertStatement($statement));
            }
            $completedBatches = 0;

            foreach ($statements as $statement) {
                foreach ($this->splitInsertStatement($statement) as $batch) {
                    if (!$db->query($batch)) {
                        $error = $db->error;
                        $db->query('ROLLBACK');
                        $db->query('SET FOREIGN_KEY_CHECKS=1');
                        $db->close();
                        $sendError('Import database gagal: ' . $error);
                        return;
                    }

                    if ($result = $db->store_result()) {
                        $result->free();
                    }
                    $completedBatches++;
                    $percent = 25 + (int) floor(($completedBatches / max(1, $totalBatches)) * 65);
                    $sendProgress(min(90, $percent), 'Mengimpor database', "Memproses batch {$completedBatches} dari {$totalBatches}...");
                }
            }

            $sendProgress(93, 'Menyelesaikan import', 'Commit transaksi dan mengaktifkan pemeriksaan relasi.');
            $db->query('COMMIT');
            $db->query('SET FOREIGN_KEY_CHECKS=1');
            $db->close();

            // Update file Database.php
            if (!$this->updateDatabaseConfig($data)) {
                $sendError('Gagal menulis konfigurasi database!');
                return;
            }

            $sendProgress(100, 'Instalasi selesai', 'Database berhasil diimport dan konfigurasi telah disimpan.');
            echo json_encode(['type' => 'complete', 'redirect' => base_url('installer/success')], JSON_UNESCAPED_UNICODE) . "\n";
            flush();
            return;

        } catch (\Exception $e) {
            $sendError('Error: ' . $e->getMessage());
            return;
        }
    }

    public function success()
    {
        if (!$this->ensureInstallerPrerequisites()) {
            return redirect()->to('/installer')->with('error', 'Gagal menyiapkan file/folder instalasi.');
        }

        if ($this->isDatabaseConfigured()) {
            return view('installer/success');
        }
        return redirect()->to('/installer');
    }

    /**
     * Cek apakah database sudah terkonfigurasi dan bisa diakses
     */
    private function isDatabaseConfigured(): bool
    {
        try {
            if (!$this->ensureDatabaseConfigFile() || !class_exists('\Config\Database')) {
                return false;
            }

            // Get database config
            $dbConfig = new \Config\Database();
            $config = $dbConfig->default;
            
            // Try to connect using mysqli directly (bypass CI4 error handling)
            $db = @new \mysqli(
                $config['hostname'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );
            
            // Check connection
            if ($db->connect_error) {
                return false;
            }
            
            // Check if core_user table exists
            $result = @$db->query("SHOW TABLES LIKE 'core_user'");
            
            if ($result && $result->num_rows > 0) {
                $db->close();
                return true;
            }
            
            $db->close();
            return false;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update file app/Config/Database.php
     */
    private function updateDatabaseConfig(array $data): bool
    {
        $configFile = APPPATH . 'Config/Database.php';

        if (!$this->ensureDatabaseConfigFile() || !file_exists($configFile)) {
            return false;
        }

        $content = file_get_contents($configFile);

        // Update konfigurasi default group
        $patterns = [
            "/'hostname'\s*=>\s*'[^']*'/" => "'hostname' => " . $this->escapeConfigValue((string) $data['hostname']),
            "/'username'\s*=>\s*'[^']*'/" => "'username' => " . $this->escapeConfigValue((string) $data['username']),
            "/'password'\s*=>\s*'[^']*'/" => "'password' => " . $this->escapeConfigValue((string) $data['password']),
            "/'database'\s*=>\s*'[^']*'/" => "'database' => " . $this->escapeConfigValue((string) $data['database']),
            "/'port'\s*=>\s*\d+/" => "'port' => " . (int) $data['port'],
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        // Tulis kembali file
        if (file_put_contents($configFile, $content)) {
            // Clear opcode cache jika ada
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            return true;
        }

        return false;
    }
}
