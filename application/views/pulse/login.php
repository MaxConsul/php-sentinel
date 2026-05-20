<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/pulse/pulse.css') ?>">
    <style>

        /* ── Login page layout ── */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0f172a;
        }

        .login-wrap {
            width: 100%;
            max-width: 400px;
            padding: 24px;
        }

        /* ── Logo / Brand ── */
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-brand h1 {
            font-size: 1.6rem;
            color: #38bdf8;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .login-brand p {
            font-size: .82rem;
            color: #475569;
        }

        /* ── Card ── */
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 32px;
        }

        /* ── Error message ── */
        .login-error {
            background: #7f1d1d22;
            border: 1px solid #7f1d1d44;
            color: #ef4444;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: .82rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Form fields ── */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: .78rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #e2e8f0;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: .9rem;
            outline: none;
            transition: border-color .2s;
        }

        .form-input:focus {
            border-color: #38bdf8;
        }

        .form-input::placeholder {
            color: #334155;
        }

        /* ── Submit button ── */
        .login-btn {
            width: 100%;
            background: #38bdf8;
            color: #0f172a;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            margin-top: 8px;
            letter-spacing: .3px;
        }

        .login-btn:hover {
            background: #7dd3fc;
        }

        .login-btn:active {
            transform: scale(.98);
        }

        /* ── Footer ── */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: .75rem;
            color: #334155;
        }

        /* ── Show/hide password ── */
        .input-wrap {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            font-size: .85rem;
            padding: 0;
        }

        .toggle-password:hover {
            color: #94a3b8;
        }

    </style>
</head>
<body>

    <div class="login-wrap">

        <!-- Brand -->
        <div class="login-brand">
            <h1>⚡ PHP Sentinel</h1>
            <p>Performance Monitoring Dashboard</p>
        </div>

        <!-- Login Card -->
        <div class="login-card">

            <h2 style="font-size:1rem; color:#e2e8f0; margin-bottom:24px;">
                Sign in to continue
            </h2>

            <!-- Error message -->
            <?php if ( ! empty($error)): ?>
                <div class="login-error">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login-form">

                <!-- Username -->
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Enter username"
                        autocomplete="username"
                        value="<?= set_value('username') ?>"
                        required
                    />
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            required
                        />
                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            id="toggle-btn">
                            👁
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="button"
                    class="login-btn"
                    onclick="submitLogin()">
                    Sign In →
                </button>

            </div><!-- /login-form -->

        </div><!-- /login-card -->

        <!-- Footer -->
        <div class="login-footer">
            PHP Sentinel &nbsp;·&nbsp; CodeIgniter 3 Performance Monitor
        </div>

    </div><!-- /login-wrap -->

    <script>
        // ── Submit login via fetch (no page reload on error) ──
        function submitLogin() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const btn      = document.querySelector('.login-btn');

            if ( ! username || ! password) {
                showError('Please enter both username and password.');
                return;
            }

            // Loading state
            btn.textContent = 'Signing in...';
            btn.disabled    = true;

            // Build form data
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            fetch('<?= site_url('pulse/login') ?>', {
                method: 'POST',
                body:   formData,
            })
            .then(r => {
                // If redirected to dashboard — follow it
                if (r.redirected) {
                    window.location.href = r.url;
                    return;
                }
                return r.text();
            })
            .then(html => {
                if ( ! html) return;

                // Check if response contains error
                const parser = new DOMParser();
                const doc    = parser.parseFromString(html, 'text/html');
                const error  = doc.querySelector('.login-error');

                if (error) {
                    showError(error.textContent.replace('⚠️', '').trim());
                } else {
                    // No error — must have redirected
                    window.location.href = '<?= site_url('pulse') ?>';
                }

                btn.textContent = 'Sign In →';
                btn.disabled    = false;
            })
            .catch(() => {
                showError('Connection error. Please try again.');
                btn.textContent = 'Sign In →';
                btn.disabled    = false;
            });
        }

        // ── Show error message ──
        function showError(message) {
            // Remove existing error
            const existing = document.querySelector('.login-error');
            if (existing) existing.remove();

            const error       = document.createElement('div');
            error.className   = 'login-error';
            error.innerHTML   = '⚠️ ' + message;

            const form = document.getElementById('login-form');
            form.insertBefore(error, form.firstChild);
        }

        // ── Toggle password visibility ──
        function togglePassword() {
            const input = document.getElementById('password');
            const btn   = document.getElementById('toggle-btn');

            if (input.type === 'password') {
                input.type      = 'text';
                btn.textContent = '🙈';
            } else {
                input.type      = 'password';
                btn.textContent = '👁';
            }
        }

        // ── Submit on Enter key ──
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') submitLogin();
        });

        // ── Auto focus username ──
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>

</body>
</html>