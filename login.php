<?php
require_once 'core.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Обработка формы входа
$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $helper->sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors['username'] = 'Введите имя пользователя';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    }
    
    if (empty($errors)) {
        $user_data = $user->login($username, $password);
        
        if ($user_data) {
            // Успешный вход
            $_SESSION['user_id'] = $user_data['id'];
            header('Location: /');
            exit;
        } else {
            $errors['general'] = 'Неверное имя пользователя или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | Gamer Network</title>
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
                    <span>Вход в систему</span>
                </div>
            </div>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="auth-container">
                    <div class="auth-card">
                        <h1 class="auth-title">Вход в аккаунт</h1>
                        <p class="auth-subtitle">Введите свои данные для входа</p>
                        
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <?= $errors['general'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="auth-form">
                            <div class="form-group">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" id="username" name="username" 
                                       class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($username) ?>" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" id="password" name="password" 
                                       class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group form-checkbox">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Запомнить меня</label>
                            </div>
                            
                            <button type="submit" class="button button-primary w-100">Войти</button>
                            
                            <div class="auth-links">
                                <a href="/forgot-password.php">Забыли пароль?</a>
                            </div>
                        </form>
                        
                        <div class="auth-footer">
                            Ещё нет аккаунта? <a href="/register.php">Зарегистрируйтесь</a>
                        </div>
                    </div>
                    
                    <div class="auth-features">
                        <div class="feature-card">
                            <i class="fas fa-gamepad feature-icon"></i>
                            <h3>Игровые достижения</h3>
                            <p>Отслеживайте свои игровые достижения и делитесь ими с друзьями</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-users feature-icon"></i>
                            <h3>Сообщество</h3>
                            <p>Общайтесь с другими игроками и находите команду для совместной игры</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-trophy feature-icon"></i>
                            <h3>Рейтинги</h3>
                            <p>Поднимайтесь в рейтингах и покажите всем, кто здесь лучший</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Дополнительные стили для страницы входа */
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
        
        .auth-links {
            text-align: center;
            margin: 15px 0;
        }
        
        .auth-links a {
            color: var(--text-link);
            font-size: 14px;
        }
        
        .auth-footer {
            margin-top: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
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
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: rgba(240, 71, 71, 0.1);
            border-left: 4px solid var(--text-danger);
            color: var(--text-danger);
        }
        
        .invalid-feedback {
            color: var(--text-danger);
            font-size: 13px;
            margin-top: 4px;
        }
        
        .is-invalid {
            border-color: var(--text-danger) !important;
        }
        
        .w-100 {
            width: 100%;
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