<?php
require_once 'core.php';

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_id']);
$current_user = $is_logged_in ? $user->getUserProfile($_SESSION['user_id']) : null;

// Обработка создания нового поста
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = $helper->sanitize($_POST['post_content']);
    $visibility = $_POST['visibility'] ?? 'public';
    
    if (!empty($content)) {
        $post_id = $post->createPost($_SESSION['user_id'], $content, $visibility);
        
        // Обработка тегов
        $tags = $helper->extractHashtags($content);
        if (!empty($tags)) {
            $tag->addTagsToPost($post_id, $tags);
        }
        
        // Редирект чтобы избежать повторной отправки формы
        header('Location: /');
        exit;
    }
}

// Получаем посты для ленты
$posts = $is_logged_in 
    ? $post->getFeed($_SESSION['user_id'], 20, 0)
    : $post->getPostsByUser(1, null, 20, 0); // Пример: показываем посты пользователя с ID 1 для гостей
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная | Gamer Network</title>
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
                <a href="/" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
                
                <?php if ($is_logged_in): ?>
                    <a href="/profile.php" class="nav-item">
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
                    <img src="<?= $current_user['avatar_url'] ?? 'https://via.placeholder.com/32' ?>" 
                         alt="<?= htmlspecialchars($current_user['username']) ?>" class="user-avatar">
                    <div class="user-info">
                        <div class="username"><?= htmlspecialchars($current_user['username']) ?></div>
                        <div class="user-level">
                            <span class="level-badge"><?= $current_user['level_id'] ?? 1 ?> lvl</span>
                        </div>
                        <div class="xp-bar-container">
                            <div class="xp-bar" style="width: <?= min(100, ($current_user['xp'] ?? 0) / 100 * 100) ?>%"></div>
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
            <!-- Верхняя панель -->
            <div class="top-bar">
                <div class="breadcrumbs">
                    <span>Главная</span>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <div class="search-bar">
                        <input type="text" placeholder="Поиск...">
                        <i class="fas fa-search"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="posts-container">
                    <?php if ($is_logged_in): ?>
                        <!-- Форма создания поста -->
                        <div class="post-form-container">
                            <form method="POST" class="post-form">
                                <div class="post-form-header">
                                    <img src="<?= $current_user['avatar_url'] ?? 'https://via.placeholder.com/40' ?>" 
                                         alt="<?= htmlspecialchars($current_user['username']) ?>" 
                                         class="post-avatar">
                                    <textarea name="post_content" placeholder="Что нового, <?= htmlspecialchars($current_user['username']) ?>?" 
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
                    
                    <!-- Лента постов -->
                    <div class="posts-feed">
                        <?php if (empty($posts)): ?>
                            <div class="empty-feed">
                                <i class="fas fa-newspaper"></i>
                                <p>Здесь пока нет постов</p>
                                <?php if (!$is_logged_in): ?>
                                    <a href="/register.php" class="button button-primary">Присоединиться</a>
                                <?php endif; ?>
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
    
    <?php if ($is_logged_in && $post_item['user_id'] == $_SESSION['user_id']): ?>
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
    
    <style>
        /* Дополнительные стили для главной страницы */
        .posts-container {
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        
        .post-form-container {
            margin-bottom: 20px;
        }
        
        .post-form {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            border: 1px solid var(--border-color);
        }
        
        .post-form-header {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .post-form textarea {
            width: 100%;
            background-color: transparent;
            border: none;
            color: var(--text-normal);
            resize: none;
            font-size: 15px;
            outline: none;
            padding: 8px 0;
        }
        
        .post-form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 8px;
            border-top: 1px solid var(--border-color-light);
        }
        
        .post-form-actions {
            display: flex;
            gap: 8px;
        }
        
        .post-form-button {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
        }
        
        .post-form-button:hover {
            color: var(--interactive-hover);
        }
        
        .visibility-select {
            background-color: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-normal);
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 13px;
            margin-left: 8px;
        }
        
        .post-submit-button {
            padding: 6px 16px;
            font-size: 14px;
        }
        
        .posts-feed {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .post {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            border: 1px solid var(--border-color);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            position: relative;
        }
        
        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }
        
        .post-user {
            flex: 1;
        }
        
        .post-username {
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .post-user-level {
            font-size: 12px;
            color: var(--level-badge);
            font-weight: bold;
        }
        
        .post-time {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        
        .edited {
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .post-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 8px;
            border-top: 1px solid var(--border-color-light);
        }
        
        .post-action {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-muted);
            font-size: 14px;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .post-action:hover {
            color: var(--interactive-hover);
        }
        
        .post-action.liked {
            color: var(--text-danger);
        }
        
        .post-content {
            margin-bottom: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }
        
        .post-tag {
            background-color: var(--bg-tertiary);
            color: var(--gamer-purple);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .post-media {
            margin-bottom: 12px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .post-media-item {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            display: block;
        }
        
        .empty-feed {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        .empty-feed i {
            font-size: 40px;
            margin-bottom: 16px;
            color: var(--text-muted);
        }
        
        .empty-feed p {
            margin-bottom: 16px;
        }
        
        @media (max-width: 768px) {
            .posts-container {
                padding: 0 16px;
            }
            
            .post-media-item {
                max-height: 300px;
            }
        }
    </style>
    
    <script>
        // Обработка лайков
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', async function() {
                const postId = this.dataset.postId;
                const isLiked = this.classList.contains('liked');
                const likeCount = this.querySelector('.like-count');
                
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
                        likeCount.textContent = result.new_count;
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
        
         // Модальное окно для комментариев
    function openCommentsModal(postId) {
        const modal = document.createElement('div');
        modal.className = 'comments-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Комментарии</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="comments-list" data-post-id="${postId}"></div>
                <div class="add-comment">
                    <textarea placeholder="Напишите комментарий..." rows="3"></textarea>
                    <button class="button button-primary post-comment">Отправить</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        loadComments(postId);
        
        // Обработчики событий
        modal.querySelector('.close-modal').addEventListener('click', () => {
            document.body.removeChild(modal);
        });
        
        modal.querySelector('.post-comment').addEventListener('click', async () => {
            const content = modal.querySelector('textarea').value.trim();
            if (content) {
                const response = await fetch('/api/comments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: content
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    loadComments(postId);
                    modal.querySelector('textarea').value = '';
                    // Обновляем счетчик комментариев на странице
                    document.querySelector(`.comment-button[data-post-id="${postId}"] .comment-count`).textContent++;
                }
            }
        });
    }
    
    async function loadComments(postId, page = 1) {
        const commentsList = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
        commentsList.innerHTML = '<div class="loading">Загрузка...</div>';
        
        const response = await fetch(`/api/comments?post_id=${postId}&page=${page}`);
        const result = await response.json();
        
        if (result.success) {
            if (result.comments.length === 0) {
                commentsList.innerHTML = '<div class="no-comments">Комментариев пока нет</div>';
            } else {
                commentsList.innerHTML = result.comments.map(comment => `
                    <div class="comment">
                        <a href="/profile.php?id=${comment.user_id}">
                            <img src="${comment.avatar_url || 'https://via.placeholder.com/32'}" 
                                 alt="${comment.username}" class="comment-avatar">
                        </a>
                        <div class="comment-body">
                            <div class="comment-header">
                                <a href="/profile.php?id=${comment.user_id}" class="comment-username">
                                    ${comment.username}
                                </a>
                                <span class="comment-time">${new Date(comment.created_at).toLocaleString()}</span>
                            </div>
                            <div class="comment-content">${comment.content}</div>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            commentsList.innerHTML = '<div class="error">Ошибка загрузки комментариев</div>';
        }
    }
    
    // Обработчик кнопки комментариев
    document.querySelectorAll('.comment-button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            openCommentsModal(postId);
        });
    });
    
    // Стили для модального окна
    const style = document.createElement('style');
    style.textContent = `
        .comments-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: var(--bg-secondary);
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-muted);
        }
        
        .comments-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .comment {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .comment-body {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        
        .comment-username {
            font-weight: bold;
            color: var(--header-primary);
        }
        
        .comment-time {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .comment-content {
            line-height: 1.4;
        }
        
        .add-comment {
            padding: 16px;
            border-top: 1px solid var(--border-color);
        }
        
        .add-comment textarea {
            width: 100%;
            margin-bottom: 8px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px;
            color: var(--text-normal);
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>