<?php
require __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $hash     = (string)($config['admin_password_hash'] ?? '');

    if ($hash !== '' && hash_equals((string)$config['admin_user'], $username) && password_verify($password, $hash)) {
        session_regenerate_id(true);  // new session ID after login prevents session fixation
        $_SESSION['logged_in'] = true;
        $_SESSION['username']  = $username;
        header('Location: index.php');
        exit();
    }
    usleep(500000);  // slow down password guessing
    $error = $hash === '' ? 'Login is not set up: add admin_password_hash to app/config.php.' : 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FinCore – Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:       #0f1117;
  --bg2:      #171b24;
  --bg3:      #1e2330;
  --border:   rgba(255,255,255,0.08);
  --border2:  rgba(255,255,255,0.14);
  --text1:    #e8e6de;
  --text2:    #9a998f;
  --text3:    #6b6a63;
  --green:    #1eb478;
  --green-bg: rgba(30,180,120,0.12);
  --red:      #e05252;
  --red-bg:   rgba(224,82,82,0.12);
  --radius:   10px;
  --radius-lg:16px;
}

html { font-size: 15px; }

body {
  font-family: 'Sora', sans-serif;
  background: var(--bg);
  color: var(--text1);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1.6;
}

.login-wrapper {
  width: 100%;
  max-width: 400px;
  padding: 1.5rem;
}

.login-brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .6rem;
  margin-bottom: 2rem;
}
.brand-icon { font-size: 1.6rem; color: var(--green); }
.brand-name { font-size: 1.4rem; font-weight: 600; letter-spacing: -.03em; }

.login-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 2rem;
}

.login-card h2 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: .3rem;
}

.login-sub {
  font-size: .85rem;
  color: var(--text2);
  margin-bottom: 1.75rem;
}

.form-group { margin-bottom: 1.1rem; }

.form-label {
  display: block;
  font-size: .75rem;
  font-weight: 500;
  color: var(--text2);
  margin-bottom: .35rem;
  text-transform: uppercase;
  letter-spacing: .06em;
}

.form-control {
  width: 100%;
  background: var(--bg3);
  border: 1px solid var(--border);
  color: var(--text1);
  font-family: 'Sora', sans-serif;
  font-size: .9rem;
  padding: .6rem .85rem;
  border-radius: var(--radius);
  outline: none;
  transition: border-color .15s;
}
.form-control:focus { border-color: var(--green); }

.btn-primary {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .4rem;
  padding: .65rem 1rem;
  border-radius: var(--radius);
  font-family: 'Sora', sans-serif;
  font-size: .9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: var(--green);
  color: #fff;
  transition: background .15s;
  margin-top: .5rem;
}
.btn-primary:hover { background: #18a06a; }

.alert-error {
  background: var(--red-bg);
  color: var(--red);
  border: 1px solid rgba(224,82,82,.25);
  padding: .7rem 1rem;
  border-radius: var(--radius);
  font-size: .85rem;
  margin-bottom: 1.25rem;
}

.login-footer {
  text-align: center;
  margin-top: 1.5rem;
  font-size: .75rem;
  color: var(--text3);
}
</style>
</head>
<body>

<div class="login-wrapper">

  <div class="login-brand">
    <span class="brand-icon">◈</span>
    <span class="brand-name">FinCore</span>
  </div>

  <div class="login-card">
    <h2>Welcome back</h2>
    <p class="login-sub">Sign in to your dashboard</p>

    <?php if ($error): ?>
      <div class="alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST"><?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" type="text" id="username" name="username"
               autocomplete="username" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password"
               placeholder="••••••••" autocomplete="current-password" required>
      </div>

      <button class="btn-primary" type="submit">Sign in →</button>
    </form>
  </div>

  <div class="login-footer">FinCore Financial Management System</div>
</div>

</body>
</html>