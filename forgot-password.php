<?php
require_once 'core.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Обработка формы
$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $helper->sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!$helper->validateEmail($email)) {
        $errors['email'] = 'Некорректный email';
    } else {
        // Проверяем существование пользователя
        $stmt = $user->db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            // Генерируем токен для сброса пароля
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Токен на 1 час
            
            // Сохраняем токен в базу
            $stmt = $user->db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    token = VALUES(token),
                    expires_at = VALUES(expires_at)
            ");
            $stmt->execute([$user_data['id'], $token, $expires]);
            
            // Отправляем email с ссылкой (заглушка - в реальной системе нужно реализовать отправку)
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
            
            // В реальной системе здесь должна быть отправка email
            // mail($email, "Восстановление пароля", "Перейдите по ссылке: $reset_link");
            
            $success = true;
        } else {
            // Показываем общее сообщение, чтобы не раскрывать наличие email в системе
            $success = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля | Gamer Network</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Левая панель навигации -->
        <div class="nav-sidebar">
            <div class="app-header">
                <div class="app-logo">
                    <img src="https://via.placeholder.com/32" alt="Логотип">
                    <span class="app-title">Gamer Network</span>
                </div>
            </div>
            
            <div class="nav-menu">
                <a href="/" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
                <a href="/login.php" class="nav-item">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Вход</span>
                </a>
                <a href="/register.php" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Регистрация</span>
                </a>
                <div class="nav-divider"></div>
                <a href="/about.php" class="nav-item">
                    <i class="fas fa-info-circle"></i>
                    <span>О проекте</span>
                </a>
            </div>
            
            <div class="user-panel">
                <img src="https://via.placeholder.com/32" alt="Гость" class="user-avatar">
                <div class="user-info">
                    <div class="username">Гость</div>
                    <div class="user-level">
                        <span class="level-badge">0 lvl</span>
                    </div>
                    <div class="xp-bar-container">
                        <div class="xp-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Основное содержимое -->
        <div class="main-content">
            <!-- Верхняя панель -->
            <div class="top-bar">
                <div class="breadcrumbs">
                    <span>Восстановление пароля</span>
                </div>
            </div>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="auth-container">
                    <div class="auth-card">
                        <h1 class="auth-title">Восстановление пароля</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <p>Если email существует в нашей системе, мы отправили на него ссылку для восстановления пароля.</p>
                                <p>Проверьте вашу почту и следуйте инструкциям в письме.</p>
                                
                                <!-- Только для тестирования - в реальной системе удалить -->
                                <div class="dev-notice" style="margin-top: 15px; padding: 10px; background: var(--bg-tertiary); border-radius: 4px;">
                                    <small>Для тестирования: <a href="/reset-password.php?token=<?= isset($token) ? $token : '' ?>">ссылка для сброса</a></small>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="auth-subtitle">Введите email, указанный при регистрации</p>
                            
                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger">
                                    <?= $errors['general'] ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="auth-form">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                           value="<?= htmlspecialchars($email) ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="button button-primary w-100">Отправить ссылку</button>
                            </form>
                            
                            <div class="auth-footer">
                                Вспомнили пароль? <a href="/login.php">Войдите в аккаунт</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="auth-features">
                        <div class="feature-card">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h3>Безопасность</h3>
                            <p>Мы используем надежные методы восстановления доступа к вашему аккаунту</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-clock feature-icon"></i>
                            <h3>Быстрое восстановление</h3>
                            <p>Ссылка для сброса пароля придет вам в течение нескольких минут</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-question-circle feature-icon"></i>
                            <h3>Нужна помощь?</h3>
                            <p>Если у вас возникли проблемы, обратитесь в нашу поддержку</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Дополнительные стили для страницы восстановления пароля */
        .auth-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            gap: 40px;
        }
        
        .auth-card {
            flex: 1;
            max-width: 400px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 30px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .auth-title {
            font-size: 24px;
            margin-bottom: 8px;
            color: var(--header-primary);
        }
        
        .auth-subtitle {
            color: var(--text-muted);
            margin-bottom: 24px;
            font-size: 15px;
        }
        
        .auth-form {
            margin-top: 20px;
        }
        
        .auth-footer {
            margin-top: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .alert-success {
            background-color: rgba(67, 181, 129, 0.1);
            border-left: 4px solid var(--text-positive);
            color: var(--text-positive);
            padding: 15px;
            border-radius: 4px;
        }
        
        .feature-card {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 24px;
            color: var(--gamer-purple);
            margin-bottom: 12px;
        }
        
        .feature-card h3 {
            margin-bottom: 8px;
            color: var(--header-primary);
        }
        
        .feature-card p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }
        
        .dev-notice {
            font-size: 13px;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                padding: 16px;
            }
            
            .auth-card {
                max-width: 100%;
            }
        }
    </style>
</body>
</html>