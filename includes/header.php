<?php require_once __DIR__ . '/../config/db.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($pageDescription) ? htmlspecialchars($pageDescription) : 'nōri — учебное кафе колледжа с онлайн-заказом напитков и свежей выпечки без очереди.' ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — nōri' : 'nōri — учебное кафе онлайн-заказов' ?></title>
    <link rel="stylesheet" href="/cofee_up/assets/css/style.css?v=2.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="/cofee_up/index.php" class="logo">nōri <span>coffee flow</span></a>
        <nav class="nav-links">
            <a href="/cofee_up/index.php">Главная</a>
            <a href="/cofee_up/menu.php">Меню</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] === 'manager'): ?>
                    <a href="/cofee_up/admin.php">Панель менеджера</a>
                <?php else: ?>
                    <a href="/cofee_up/profile.php">Мои заказы</a>
                    <a href="/cofee_up/cart.php" class="cart-link">
                        Корзина <span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0 ?></span>
                    </a>
                <?php endif; ?>
                <a href="/cofee_up/logout.php" class="btn-outline btn-sm">Выйти (<?= htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['login']) ?>)</a>
            <?php else: ?>
                <a href="/cofee_up/login.php" class="btn-primary btn-sm">Войти</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>