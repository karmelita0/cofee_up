<?php
$pageTitle = 'Главная';
include 'includes/header.php';
?>

<section class="hero">
    <div class="container hero-inner">
        <span class="hero-kicker">Учебное кафе колледжа</span>
        <h1>Кофе <span class="italic">без</span> очереди.</h1>
        <p>Выберите напитки и свежую выпечку заранее, оформите заказ онлайн и заберите его на стойке выдачи по готовности.</p>
        <div class="hero-actions">
            <a href="menu.php" class="btn-primary">Открыть меню</a>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="btn-cream">Войти в аккаунт</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="features container">
    <div class="feature-item">
        <span class="feature-num">01</span>
        <h3>Свежая обжарка</h3>
        <p>Зерна спешелти-класса обжариваются еженедельно для сбалансированного вкуса эспрессо и молочных напитков.</p>
    </div>
    <div class="feature-item">
        <span class="feature-num">02</span>
        <h3>Точное время</h3>
        <p>Отслеживайте статус приготовления в личном кабинете от принятия заказа до готовности к выдаче.</p>
    </div>
    <div class="feature-item">
        <span class="feature-num">03</span>
        <h3>Ассортимент дня</h3>
        <p>Классический кофе, авторские сезонные напитки, десерты и сытные сэндвичи между парами.</p>
    </div>
</section>

<section class="container menu-section">
    <div class="section-header">
        <div>
            <h2>Выбор гостей</h2>
            <p class="section-sub">Популярные позиции из нашего меню</p>
        </div>
        <a href="menu.php" class="btn-outline">Все позиции</a>
    </div>
    <div class="grid-menu">
        <?php
        $stmt = $pdo->query("SELECT m.*, c.name AS category_name FROM menu_items m JOIN categories c ON m.category_id = c.id WHERE m.is_available = 1 ORDER BY m.id ASC LIMIT 3");
        $popular = $stmt->fetchAll();
        foreach ($popular as $item):
        ?>
            <article class="menu-card">
                <div class="card-img" style="background-image: url('/cofee_up/assets/img/<?= htmlspecialchars($item['image']) ?>');">
                    <span class="badge-cat"><?= htmlspecialchars($item['category_name']) ?></span>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="desc"><?= htmlspecialchars($item['description']) ?></p>
                    <div class="card-footer">
                        <span class="price"><?= number_format($item['price'], 0, '', ' ') ?> ₽</span>
                        <form action="cart.php" method="POST" class="add-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="redirect" value="index.php">
                            <input type="number" name="quantity" value="1" min="1" class="qty-input" required>
                            <button type="submit" name="add_to_cart" class="btn-icon" title="Добавить в корзину">+</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>