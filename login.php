<?php
require_once 'config.php';
require_once 'helpers/csrf.php';

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

initializeCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $error = 'Session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Regenerate CSRF token after successful login
                regenerateCsrfToken();
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
}

// Initialize CSRF token for form
initializeCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Smart Document AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
            position: relative;
            overflow: hidden;
        }

        /* Subtle grid overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            z-index: 0;
            pointer-events: none;
        }

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(40px);
            z-index: 0;
            pointer-events: none;
        }
        .orb-1 {
            width: 200px; height: 200px;
            background: rgba(79,70,229,0.15);
            top: 10%; left: 15%;
            animation: floating 8s ease-in-out infinite;
        }
        .orb-2 {
            width: 120px; height: 120px;
            background: rgba(6,182,212,0.18);
            top: 60%; right: 10%;
            animation: floating 12s ease-in-out infinite reverse;
        }
        .orb-3 {
            width: 80px; height: 80px;
            background: rgba(79,70,229,0.15);
            bottom: 15%; left: 10%;
            animation: floating 10s ease-in-out infinite 2s;
        }
        .orb-4 {
            width: 150px; height: 150px;
            background: rgba(6,182,212,0.18);
            top: 20%; right: 25%;
            animation: floating 15s ease-in-out infinite 1s;
        }
        .orb-5 {
            width: 60px; height: 60px;
            background: rgba(79,70,229,0.15);
            bottom: 30%; right: 30%;
            animation: floating 6s ease-in-out infinite 3s;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.7; }
            50% { transform: translateY(-30px) scale(1.1); opacity: 1; }
        }

        /* Login card */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 0 60px rgba(79, 70, 229, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Brand area */
        .brand-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(79,70,229,0.2), rgba(79,70,229,0.05));
            border: 2px solid rgba(79,70,229,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 0 30px rgba(79,70,229,0.3);
        }
        .brand-icon i {
            font-size: 28px;
            color: #818CF8;
        }
        .brand-title {
            text-align: center;
            color: #fff;
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        .brand-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            font-weight: 400;
            margin-bottom: 32px;
        }

        /* Form styles */
        .form-label-custom {
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 6px;
            display: block;
        }
        .form-input-custom {
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fff;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-input-custom::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        .form-input-custom:focus {
            border-color: #818CF8;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.15), 0 0 20px rgba(79,70,229,0.1);
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        /* Password toggle */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-input-custom {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }
        .password-toggle:hover {
            color: #818CF8;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            background: #4F46E5;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.3);
            margin-top: 8px;
        }
        .btn-submit:hover {
            background: #7C3AED;
            color: #fff;
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4);
            transform: translateY(-1px);
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        /* Error alert */
        .error-alert {
            background: rgba(255, 0, 80, 0.2);
            border: 1px solid rgba(255, 0, 80, 0.4);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.4s ease-out;
        }
        .error-alert i {
            color: #ff4d6d;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .error-alert span {
            color: #fff;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Footer link */
        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
        }
        .footer-text a {
            color: #818CF8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .footer-text a:hover {
            color: #C084FC;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                margin: 16px;
                padding: 28px 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
    <div class="orb orb-5"></div>

    <div class="login-card">
        <!-- Brand -->
        <div class="brand-icon">
            <i class="bi bi-file-earmark-richtext"></i>
        </div>
        <h1 class="brand-title">Smart Document AI</h1>
        <p class="brand-subtitle">Masuk ke akun Anda</p>

        <!-- Error alert -->
        <?php if ($error): ?>
            <div class="error-alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

         <!-- Login form -->
         <form method="POST" action="" autocomplete="off">
             <?= getCsrfTokenInput() ?>
             <div class="form-group">
                 <label class="form-label-custom" for="username">Username</label>
                 <input type="text" class="form-input-custom" id="username" name="username" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
             </div>

            <div class="form-group">
                <label class="form-label-custom" for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" class="form-input-custom" id="password" name="password" placeholder="Masukkan password" required>
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <p class="footer-text">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>

    <script>
        // Password toggle
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
