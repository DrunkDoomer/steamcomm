<?php
require_once 'core.php';

// Инициализация переменных
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Получаем ID профиля из запроса
$profile_id = $_GET['id'] ?? $current_user_id;
if (!$profile_id) {
    header('Location: /');
    exit;
}

// Получаем данные профиля
$profile = $user->getUserProfile($profile_id);
if (!$profile) {
    header('Location: /');
    exit;
}

// Определяем дополнительные данные
$is_current_user = $is_logged_in && ($current_user_id == $profile_id);
$posts = $post->getPostsByUser($profile_id, $current_user_id, 20, 0);
$follow_counts = $follower->getFollowCounts($profile_id);
$is_following = $is_logged_in ? $follower->isFollowing($current_user_id, $profile_id) : false;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль <?= htmlspecialchars($profile['username']) ?> | Gamer Network</title>
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
                
                <?php if ($is_logged_in): ?>
                    <a href="/profile.php?id=<?= $current_user_id ?>" class="nav-item">
                        <i class="fas fa-user"></i>
                        <span>Профиль</span>
                    </a>
                    <a href="/notifications.php" class="nav-item">
                        <i class="fas fa-bell"></i>
                        <span>Уведомления</span>
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="nav-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Вход</span>
                    </a>
                    <a href="/register.php" class="nav-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Регистрация</span>
                    </a>
                <?php endif; ?>
                
                <div class="nav-divider"></div>
                
                <a href="/about.php" class="nav-item">
                    <i class="fas fa-info-circle"></i>
                    <span>О проекте</span>
                </a>
            </div>
            
            <?php if ($is_logged_in): ?>
                <div class="user-panel">
                    <img src="<?= htmlspecialchars($user->getUserProfile($current_user_id)['avatar_url'] ?? 'https://via.placeholder.com/32') ?>" 
                         alt="<?= htmlspecialchars($user->getUserProfile($current_user_id)['username']) ?>" 
                         class="user-avatar">
                    <div class="user-info">
                        <div class="username"><?= htmlspecialchars($user->getUserProfile($current_user_id)['username']) ?></div>
                        <div class="user-level">
                            <span class="level-badge"><?= $user->getUserProfile($current_user_id)['level_id'] ?? 1 ?> lvl</span>
                        </div>
                        <div class="xp-bar-container">
                            <div class="xp-bar" style="width: <?= min(100, ($user->getUserProfile($current_user_id)['xp'] ?? 0) / 100 * 100) ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
        
        <!-- Основное содержимое -->
        <div class="main-content">
            <div class="top-bar">
                <div class="breadcrumbs">
                    <a href="/">Главная</a>
                    <span>/</span>
                    <span><?= htmlspecialchars($profile['username']) ?></span>
                </div>
            </div>
            
            <div class="content-area">
                <div class="profile-header">
                    <img src="<?= htmlspecialchars($profile['avatar_url'] ?? 'https://via.placeholder.com/100') ?>" 
                         alt="<?= htmlspecialchars($profile['username']) ?>" 
                         class="profile-avatar">
                    
                    <div class="profile-info">
                        <div class="profile-name">
                            <?= htmlspecialchars($profile['username']) ?>
                            <span class="profile-level"><?= $profile['level_id'] ?? 1 ?> lvl</span>
                        </div>
                        
                        <?php if (!empty($profile['bio'])): ?>
                            <div class="profile-bio"><?= htmlspecialchars($profile['bio']) ?></div>
                        <?php endif; ?>
                        
                        <div class="profile-meta">
                            <?php if (!empty($profile['location'])): ?>
                                <div class="profile-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($profile['location']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile['website'])): ?>
                                <div class="profile-meta-item">
                                    <i class="fas fa-link"></i>
                                    <a href="<?= htmlspecialchars($profile['website']) ?>" target="_blank">
                                        <?= htmlspecialchars(parse_url($profile['website'], PHP_URL_HOST)) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?= $follow_counts['followers_count'] ?? 0 ?></div>
                                <div class="profile-stat-label">Подписчиков</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?= $follow_counts['following_count'] ?? 0 ?></div>
                                <div class="profile-stat-label">Подписок</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat-value"><?= $profile['xp'] ?? 0 ?></div>
                                <div class="profile-stat-label">Опыта</div>
                            </div>
                        </div>
                        
                        <?php if ($is_logged_in && !$is_current_user): ?>
                            <button class="button <?= $is_following ? 'button-secondary' : 'button-primary' ?> follow-button" 
                                    data-user-id="<?= $profile_id ?>">
                                <?= $is_following ? 'Отписаться' : 'Подписаться' ?>
                            </button>
                        <?php elseif ($is_current_user): ?>
                            <a href="/settings.php" class="button button-secondary">Редактировать профиль</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Блок с игровыми аккаунтами -->
                <?php $game_accounts = $gamePlatform->getUserGameAccounts($profile_id); ?>
                <?php if (!empty($game_accounts)): ?>
                    <div class="profile-section">
                        <h3 class="section-title">Игровые аккаунты</h3>
                        <div class="game-accounts">
                            <?php foreach ($game_accounts as $account): ?>
                                <div class="game-account">
                                    <img src="/assets/platforms/<?= strtolower($account['platform_name']) ?>.png" 
                                         alt="<?= $account['platform_name'] ?>" 
                                         class="game-account-icon">
                                    <span class="game-account-name"><?= $account['platform_name'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Лента постов пользователя -->
                <div class="profile-section">
                    <h3 class="section-title">Посты</h3>
                    
                    <?php if ($is_current_user): ?>
                        <div class="post-form-container">
                            <form method="POST" action="/profile.php" class="post-form">
                                <div class="post-form-header">
                                    <img src="<?= htmlspecialchars($profile['avatar_url'] ?? 'https://via.placeholder.com/40') ?>" 
                                         alt="<?= htmlspecialchars($profile['username']) ?>" 
                                         class="post-avatar">
                                    <textarea name="post_content" placeholder="Что нового, <?= htmlspecialchars($profile['username']) ?>?" 
                                              rows="3" required></textarea>
                                </div>
                                
                                <div class="post-form-footer">
                                    <div class="post-form-actions">
                                        <button type="button" class="post-form-button" title="Медиа">
                                            <i class="fas fa-image"></i>
                                        </button>
                                        <button type="button" class="post-form-button" title="Гифка">
                                            <i class="fas fa-film"></i>
                                        </button>
                                        <button type="button" class="post-form-button" title="Опрос">
                                            <i class="fas fa-poll"></i>
                                        </button>
                                        
                                        <select name="visibility" class="visibility-select">
                                            <option value="public">🌍 Публичный</option>
                                            <option value="followers">🔒 Только подписчики</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="button button-primary post-submit-button">
                                        Опубликовать
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <div class="posts-feed">
                        <?php if (empty($posts)): ?>
                            <div class="empty-feed">
                                <i class="fas fa-newspaper"></i>
                                <p>Здесь пока нет постов</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post_item): ?>
                                <div class="post" data-post-id="<?= $post_item['id'] ?>">
                                    <div class="post-header">
                                        <a href="/profile.php?id=<?= $post_item['user_id'] ?>">
                                            <img src="<?= htmlspecialchars($post_item['avatar_url'] ?? 'https://via.placeholder.com/40') ?>" 
                                                 alt="<?= htmlspecialchars($post_item['username']) ?>" 
                                                 class="post-avatar">
                                        </a>
                                        
                                        <div class="post-user">
                                            <a href="/profile.php?id=<?= $post_item['user_id'] ?>" class="post-username">
                                                <?= htmlspecialchars($post_item['username']) ?>
                                                <span class="post-user-level"><?= $post_item['level_id'] ?? 1 ?> lvl</span>
                                            </a>
                                            <div class="post-time">
                                                <?= $helper->formatDate($post_item['created_at']) ?>
                                                <?php if ($post_item['is_edited']): ?>
                                                    <span class="edited">(ред.)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($is_current_user): ?>
                                            <div class="post-actions">
                                                <button class="post-action-button edit-post" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="post-action-button delete-post" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="post-content">
                                        <?= nl2br(htmlspecialchars($post_item['content'])) ?>
                                        
                                        <?php if (!empty($post_item['tags'])): ?>
                                            <div class="post-tags">
                                                <?php foreach ($post_item['tags'] as $tag): ?>
                                                    <a href="/tag/<?= urlencode($tag['tag_name']) ?>" class="post-tag">
                                                        #<?= htmlspecialchars($tag['tag_name']) ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($post_item['media'])): ?>
                                        <div class="post-media">
                                            <?php foreach ($post_item['media'] as $media): ?>
                                                <?php if ($media['media_type'] === 'image'): ?>
                                                    <img src="<?= htmlspecialchars($media['media_url']) ?>" 
                                                         alt="<?= htmlspecialchars($media['description']) ?>" 
                                                         class="post-media-item">
                                                <?php elseif ($media['media_type'] === 'video'): ?>
                                                    <video controls class="post-media-item">
                                                        <source src="<?= htmlspecialchars($media['media_url']) ?>">
                                                    </video>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="post-actions">
                                        <button class="post-action like-button <?= $post_item['is_liked'] ? 'liked' : '' ?>" 
                                                data-post-id="<?= $post_item['id'] ?>">
                                            <i class="fas fa-heart"></i>
                                            <span class="like-count"><?= $post_item['like_count'] ?></span>
                                        </button>
                                        
                                        <button class="post-action comment-button" data-post-id="<?= $post_item['id'] ?>">
                                            <i class="fas fa-comment"></i>
                                            <span class="comment-count"><?= $post_item['comment_count'] ?></span>
                                        </button>
                                        
                                        <button class="post-action share-button">
                                            <i class="fas fa-share"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Обработка подписки/отписки
        document.querySelector('.follow-button')?.addEventListener('click', async function() {
            const userId = this.dataset.userId;
            const isFollowing = this.textContent.trim() === 'Отписаться';
            const action = isFollowing ? 'unfollow' : 'follow';
            
            try {
                const response = await fetch('/api/' + action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    this.textContent = isFollowing ? 'Подписаться' : 'Отписаться';
                    this.classList.toggle('button-primary');
                    this.classList.toggle('button-secondary');
                    
                    // Обновляем счетчики подписчиков
                    const followerCountElement = document.querySelector('.profile-stat-value:nth-child(1)');
                    const currentCount = parseInt(followerCountElement.textContent);
                    followerCountElement.textContent = isFollowing ? currentCount - 1 : currentCount + 1;
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });

        // Обработка лайков (аналогично index.php)
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', async function() {
                const postId = this.dataset.postId;
                const isLiked = this.classList.contains('liked');
                
                try {
                    const response = await fetch('/api/like', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            post_id: postId,
                            action: isLiked ? 'unlike' : 'like'
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.classList.toggle('liked');
                        this.querySelector('.like-count').textContent = result.new_count;
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Обработка удаления поста
        document.querySelectorAll('.delete-post').forEach(button => {
            button.addEventListener('click', async function() {
                const post = this.closest('.post');
                const postId = post.dataset.postId;
                
                if (confirm('Вы уверены, что хотите удалить этот пост?')) {
                    try {
                        const response = await fetch('/api/delete_post', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                post_id: postId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            post.remove();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                }
            });
        });

        // Обработка комментариев (аналогично index.php)
        document.querySelectorAll('.comment-button').forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.dataset.postId;
                // Реализация модального окна для комментариев
                console.log('Open comments for post', postId);
            });
        });
    </script>
</body>
</html>