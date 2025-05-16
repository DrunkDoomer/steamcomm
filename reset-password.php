<?php
require_once 'core.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$token = $_GET['token'] ?? '';
$errors = [];
$success = false;

// Проверяем токен
if (!empty($token)) {
    $stmt = $user->db->prepare("
        SELECT user_id, expires_at 
        FROM password_resets 
        WHERE token = ? AND expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset_data = $stmt->fetch();
    
    if (!$reset_data) {
        $errors['general'] = 'Недействительная или просроченная ссылка';
    }
} else {
    $errors['general'] = 'Неверная ссылка для сброса пароля';
}

// Обработка формы сброса пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $errors['password'] = 'Введите новый пароль';
    } elseif (strlen($new_password) < 8) {
        $errors['password'] = 'Пароль должен быть не менее 8 символов';
    } elseif ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Пароли не совпадают';
    }
    
    if (empty($errors)) {
        // Обновляем пароль
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $user->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$password_hash, $reset_data['user_id']]);
        
        // Удаляем использованный токен
        $stmt = $user->db->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля | Gamer Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Левая панель навигации -->
        <div class="nav-sidebar">
            <!-- ... (аналогично forgot-password.php) ... -->
        </div>
        
        <!-- Основное содержимое -->
        <div class="main-content">
            <!-- Верхняя панель -->
            <div class="top-bar">
                <div class="breadcrumbs">
                    <span>Сброс пароля</span>
                </div>
            </div>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="auth-container">
                    <div class="auth-card">
                        <h1 class="auth-title">Сброс пароля</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <p>Ваш пароль был успешно изменен.</p>
                                <p>Теперь вы можете <a href="/login.php">войти в систему</a> с новым паролем.</p>
                            </div>
                        <?php elseif (!empty($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <?= $errors['general'] ?>
                            </div>
                        <?php else: ?>
                            <p class="auth-subtitle">Введите новый пароль для вашего аккаунта</p>
                            
                            <form method="POST" class="auth-form">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Новый пароль</label>
                                    <input type="password" id="password" name="password" 
                                           class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Подтвердите пароль</label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="button button-primary w-100">Сменить пароль</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>