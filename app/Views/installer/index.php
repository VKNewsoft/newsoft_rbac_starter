<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installer - Base App Admin - By Newsoft Developer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .installer-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 980px;
            width: 100%;
            overflow: hidden;
        }

        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        .installer-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .installer-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .installer-body {
            padding: 24px 30px 26px;
        }

        .alert {
            padding: 11px 15px;
            border-radius: 5px;
            margin-bottom: 14px;
        }

        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
        }

        .form-group {
            margin-bottom: 13px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group label .required {
            color: #e74c3c;
        }

        .form-group input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }

        .error-text {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .info-box {
            background: #f8f9fa;
            padding: 12px 15px;
            border-left: 4px solid #667eea;
            margin-bottom: 16px;
        }

        .info-box h3 {
            font-size: 14px;
            margin-bottom: 6px;
            color: #333;
        }

        .info-box ul {
            margin-left: 20px;
            font-size: 13px;
            color: #666;
        }

        .info-box ul li {
            margin-bottom: 3px;
        }

        .progress-panel {
            display: none;
            margin-bottom: 16px;
            padding: 13px 16px;
            border: 1px solid #dfe3f3;
            border-radius: 8px;
            background: #f8f9ff;
        }

        .progress-panel.is-active {
            display: block;
        }

        .progress-heading {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }

        .progress-percent {
            color: #667eea;
            font-size: 18px;
        }

        .progress-track {
            height: 12px;
            overflow: hidden;
            border-radius: 20px;
            background: #e6e8f2;
        }

        .progress-bar {
            width: 0;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width .25s ease;
        }

        .progress-message {
            min-height: 18px;
            margin-top: 7px;
            color: #666;
            font-size: 13px;
        }

        .progress-error {
            color: #c33;
        }

        .installer-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 24px;
        }

        .installer-form-grid .form-group:last-child {
            grid-column: 1 / -1;
            max-width: calc(50% - 12px);
        }

        @media (max-width: 700px) {
            body {
                align-items: flex-start;
                padding: 0;
            }

            .installer-container {
                min-height: 100vh;
                border-radius: 0;
            }

            .installer-header,
            .installer-body {
                padding-left: 20px;
                padding-right: 20px;
            }

            .installer-form-grid {
                display: block;
            }

            .installer-form-grid .form-group:last-child {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1>🗄️ Database Installer</h1>
            <p>Base App Admin - Newsoft Developer</p>
        </div>

        <div class="installer-body">
            <?php if (session()->has('error')): ?>
                <div class="alert alert-danger">
                    <strong>Error!</strong> <?= esc(session('error')) ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3>📋 Informasi Instalasi</h3>
                <ul>
                    <li>Proses instalasi akan membuat database baru</li>
                    <li>Import struktur dan data dari <strong>newsoft_base.sql</strong></li>
                    <li>Konfigurasi akan disimpan di <strong>app/Config/Database.php</strong></li>
                    <li>Pastikan MySQL server sudah berjalan</li>
                </ul>
            </div>

            <div class="progress-panel" id="progress-panel" aria-live="polite">
                <div class="progress-heading">
                    <span id="progress-process">Menyiapkan instalasi</span>
                    <span class="progress-percent" id="progress-percent">0%</span>
                </div>
                <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="progress-track">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
                <div class="progress-message" id="progress-message"></div>
            </div>

            <form method="post" action="<?= base_url('installer/install') ?>" id="installer-form">
                <?= csrf_field() ?>

                <div class="installer-form-grid">
                <div class="form-group">
                    <label>Database Host <span class="required">*</span></label>
                    <input type="text" name="db_host" value="<?= old('db_host', 'localhost') ?>" required>
                    <small>Biasanya: localhost atau 127.0.0.1</small>
                    <?php if (session('errors.db_host')): ?>
                        <div class="error-text"><?= session('errors.db_host') ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Database Port <span class="required">*</span></label>
                    <input type="number" name="db_port" value="<?= old('db_port', '3306') ?>" required>
                    <small>Port default MySQL: 3306</small>
                    <?php if (session('errors.db_port')): ?>
                        <div class="error-text"><?= session('errors.db_port') ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Database Username <span class="required">*</span></label>
                    <input type="text" name="db_username" value="<?= old('db_username', 'root') ?>" required>
                    <small>Username untuk akses MySQL</small>
                    <?php if (session('errors.db_username')): ?>
                        <div class="error-text"><?= session('errors.db_username') ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_password" value="<?= old('db_password', '') ?>">
                    <small>Kosongkan jika tidak ada password (default XAMPP)</small>
                    <?php if (session('errors.db_password')): ?>
                        <div class="error-text"><?= session('errors.db_password') ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Nama Database <span class="required">*</span></label>
                    <input type="text" name="db_name" value="<?= old('db_name', 'newsoft_app') ?>" required>
                    <small>Database akan dibuat otomatis jika belum ada</small>
                    <?php if (session('errors.db_name')): ?>
                        <div class="error-text"><?= session('errors.db_name') ?></div>
                    <?php endif; ?>
                </div>
                </div>

                <button type="submit" class="btn">
                    🚀 Install Database
                </button>
            </form>
        </div>
    </div>
    <script>
        (() => {
            const form = document.getElementById('installer-form');
            const button = form.querySelector('button[type="submit"]');
            const panel = document.getElementById('progress-panel');
            const process = document.getElementById('progress-process');
            const percent = document.getElementById('progress-percent');
            const message = document.getElementById('progress-message');
            const bar = document.getElementById('progress-bar');
            const track = document.getElementById('progress-track');

            const updateProgress = (data) => {
                const value = Math.max(0, Math.min(100, Number(data.percent) || 0));
                process.textContent = data.process || 'Memproses instalasi';
                percent.textContent = `${value}%`;
                message.textContent = data.message || '';
                bar.style.width = `${value}%`;
                track.setAttribute('aria-valuenow', String(value));
            };

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                panel.classList.add('is-active');
                message.classList.remove('progress-error');
                button.disabled = true;
                button.textContent = 'Memproses instalasi...';
                updateProgress({ percent: 1, process: 'Memulai instalasi', message: 'Mohon tunggu, jangan tutup halaman ini.' });

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { Accept: 'application/x-ndjson' }
                    });

                    if (!response.ok || !response.body) {
                        throw new Error('Server tidak dapat mengirim progres instalasi.');
                    }

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let completed = false;

                    while (true) {
                        const { value, done } = await reader.read();
                        buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
                        const lines = buffer.split('\n');
                        buffer = lines.pop() || '';

                        for (const line of lines) {
                            const jsonLine = line.trim();
                            if (!jsonLine) continue;

                            let data;
                            try {
                                data = JSON.parse(jsonLine);
                            } catch (parseError) {
                                throw new Error('Respons server tidak valid: ' + parseError.message);
                            }

                            if (data.type === 'progress') updateProgress(data);
                            if (data.type === 'error') throw new Error(data.message);
                            if (data.type === 'complete') {
                                completed = true;
                                await reader.cancel();
                                window.location.replace(data.redirect);
                                return;
                            }
                        }

                        if (done) break;
                    }

                    if (!completed && buffer.trim()) {
                        const data = JSON.parse(buffer);
                        if (data.type === 'error') throw new Error(data.message);
                    }
                } catch (error) {
                    process.textContent = 'Instalasi gagal';
                    message.textContent = error.message || 'Terjadi kesalahan saat instalasi.';
                    message.classList.add('progress-error');
                    button.disabled = false;
                    button.textContent = '🚀 Install Database';
                }
            });
        })();
    </script>
</body>
</html>
