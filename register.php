<?php
require_once 'core.php';

// Инициализация переменных
$errors = [];
$username = '';
$email = '';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные
    $username = $helper->sanitize($_POST['username'] ?? '');
    $email = $helper->sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Валидация
    if (empty($username)) {
        $errors['username'] = 'Имя пользователя обязательно';
    } elseif (!$helper->validateUsername($username)) {
        $errors['username'] = 'Имя пользователя может содержать только буквы, цифры и подчеркивания (3-50 символов)';
    } elseif ($user->getUserByUsername($username)) {
        $errors['username'] = 'Это имя пользователя уже занято';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email обязателен';
    } elseif (!$helper->validateEmail($email)) {
        $errors['email'] = 'Некорректный email';
    } elseif ($user->getUserByUsername($email)) {
        $errors['email'] = 'Этот email уже зарегистрирован';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Пароль обязателен';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Пароль должен быть не менее 8 символов';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Пароли не совпадают';
    }
    
    // Если ошибок нет - регистрируем пользователя
    if (empty($errors)) {
        $user_id = $user->register($username, $email, $password);
        
        if ($user_id) {
            // Автоматически входим после регистрации
            $user_data = $user->login($username, $password);
            
            if ($user_data) {
                $_SESSION['user_id'] = $user_data['id'];
                header('Location: /');
                exit;
            }
        } else {
            $errors['general'] = 'Произошла ошибка при регистрации. Попробуйте позже.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Gamer Network</title>
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
                        <span class="level-badge">1 lvl</span>
                        <span class="xp-percent">0%</span>
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
                    <span>Регистрация</span>
                </div>
            </div>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="auth-container">
                    <div class="auth-card">
                        <h1 class="auth-title">Создать аккаунт</h1>
                        <p class="auth-subtitle">Присоединяйтесь к сообществу геймеров</p>
                        
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger">
                                <?= $errors['general'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="auth-form">
                            <div class="form-group">
                                <label for="username" class="form-label">Имя пользователя</label>
                                <input type="text" id="username" name="username" class="form-input <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($username) ?>" required>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?= $errors['username'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($email) ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Пароль</label>
                                <input type="password" id="password" name="password" class="form-input <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Подтвердите пароль</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?= $errors['confirm_password'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group form-checkbox">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">Я принимаю <a href="/terms.php" target="_blank">Условия использования</a> и <a href="/privacy.php" target="_blank">Политику конфиденциальности</a></label>
                            </div>
                            
                            <button type="submit" class="button button-primary w-100">Зарегистрироваться</button>
                        </form>
                        
                        <div class="auth-footer">
                            Уже есть аккаунт? <a href="/login.php">Войдите</a>
                        </div>
                    </div>
                    
                    <div class="auth-features">
                        <div class="feature-card">
                            <i class="fas fa-gamepad feature-icon"></i>
                            <h3>Игровые интеграции</h3>
                            <p>Подключайте аккаунты Steam, Xbox Live и других платформ</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-trophy feature-icon"></i>
                            <h3>Система уровней</h3>
                            <p>Получайте опыт и повышайте уровень за активность</p>
                        </div>
                        
                        <div class="feature-card">
                            <i class="fas fa-users feature-icon"></i>
                            <h3>Сообщество</h3>
                            <p>Общайтесь с другими геймерами и находите команду</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Дополнительные стили для страницы регистрации */
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
        
        .auth-features {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
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