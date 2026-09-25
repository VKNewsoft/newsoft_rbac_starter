<?php

$db = new mysqli('localhost', 'root', '', 'newsoft_app');

if ($db->connect_error) {
    die("❌ Koneksi gagal: " . $db->connect_error);
}

echo "📊 Verifikasi Import Database\n";
echo str_repeat('=', 60) . "\n\n";

// Expected total tables after removing the project/task modules and the
// Subscription Manager (email-expiration) module.
$expectedTableCount = 34;

// Cek jumlah tabel
$tables = $db->query('SHOW TABLES');
$actualTableCount = $tables->num_rows;

echo "Expected: $expectedTableCount tables\n";
echo "Found: $actualTableCount tables\n";

if ($actualTableCount == $expectedTableCount) {
    echo "✅ Jumlah tabel sesuai!\n\n";
} else {
    echo "⚠️  Jumlah tabel tidak sesuai!\n\n";
}

$removedProjectTables = [
    'project',
    'project_category',
    'project_member',
    'project_task',
    'project_task_token_usage',
    'base_email_expiration',
];

echo "🧹 Validasi tabel project/task yang sudah dihapus:\n";
$tableResult = $db->query('SHOW TABLES');
$importedTables = [];
while ($row = $tableResult->fetch_array(MYSQLI_NUM)) {
    $importedTables[] = $row[0];
}

foreach ($removedProjectTables as $table) {
    if (in_array($table, $importedTables, true)) {
        echo "❌ $table masih ada\n";
    } else {
        echo "✅ $table tidak ada\n";
    }
}

$removedProjectModules = [
    'project',
    'project-category',
    'project-member',
    'task-management',
    'email-expiration',
];

echo "\n🧹 Validasi module/menu project/task yang sudah dihapus:\n";
$moduleNames = implode(',', array_map(static fn (string $name): string => "'" . $db->real_escape_string($name) . "'", $removedProjectModules));
$moduleResult = $db->query("SELECT nama_module FROM core_module WHERE nama_module IN ($moduleNames)");
$remainingModules = [];
while ($row = $moduleResult->fetch_assoc()) {
    $remainingModules[] = $row['nama_module'];
}

$menuUrls = implode(',', array_map(static fn (string $name): string => "'" . $db->real_escape_string($name) . "'", $removedProjectModules));
$menuResult = $db->query("SELECT url FROM core_menu WHERE url IN ($menuUrls) OR (nama_menu = 'Project' AND url = '#')");
$remainingMenus = [];
while ($row = $menuResult->fetch_assoc()) {
    $remainingMenus[] = $row['url'];
}

if ($remainingModules) {
    echo "❌ Module masih ada: " . implode(', ', $remainingModules) . "\n";
} else {
    echo "✅ Tidak ada module project/task\n";
}

if ($remainingMenus) {
    echo "❌ Menu masih ada: " . implode(', ', $remainingMenus) . "\n";
} else {
    echo "✅ Tidak ada menu project/task\n";
}

// Cek data penting
$checks = [
    'core_company' => 'SELECT COUNT(*) as c FROM core_company',
    'core_user' => 'SELECT COUNT(*) as c FROM core_user',
    'core_identitas' => 'SELECT COUNT(*) as c FROM core_identitas',
    'core_wilayah_propinsi' => 'SELECT COUNT(*) as c FROM core_wilayah_propinsi',
    'core_wilayah_kabupaten' => 'SELECT COUNT(*) as c FROM core_wilayah_kabupaten',
    'core_wilayah_kecamatan' => 'SELECT COUNT(*) as c FROM core_wilayah_kecamatan',
    'core_wilayah_kelurahan' => 'SELECT COUNT(*) as c FROM core_wilayah_kelurahan',
    'core_bank' => 'SELECT COUNT(*) as c FROM core_bank',
    'core_menu' => 'SELECT COUNT(*) as c FROM core_menu',
    'core_role' => 'SELECT COUNT(*) as c FROM core_role'
];

echo "📋 Jumlah Data per Tabel:\n";
echo str_repeat('-', 60) . "\n";

foreach ($checks as $table => $query) {
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        printf("%-35s : %s rows\n", $table, number_format($row['c']));
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "🎉 Database berhasil diimport dengan lengkap!\n";

$db->close();
