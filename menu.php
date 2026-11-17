<?php
$pageTitle = 'Меню';
include 'includes/header.php';

$search = trim($_GET['search'] ?? '');
$category = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$where = "WHERE m.is_available = 1";
$params = [];

if ($search !== '') {
    $where .= " AND m.title LIKE :search";
    $params['search'] = '%' . $search . '%';
}
if ($category !== null) {
    $where .= " AND m.category_id = :category";
    $params['category'] = $category;
}

// Подсчет общего количества записей для пагинации
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items m $where");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Выборка текущей страницы
$sql = "SELECT m.*, c.name AS category_name 
        FROM menu_items m 
        JOIN categories c ON m.category_id = c.id 
        $where 
        ORDER BY c.sort_order ASC, m.id ASC 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();
?>

<section class="menu-page container">
    <div class="section-header">
        <div>
            <h2>Меню кафе</h2>
            <p class="section-sub">Найдено позиций: <?= $totalItems ?></p>
        </div>
        <form method="GET" action="menu.php" class="filter-form">
            <input type="text" name="search" placeholder="Поиск по названию..." value="<?= htmlspecialchars($search) ?>">
            <select name="category">
                <option value="">Все категории</option>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= ($category === (int)$cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Найти</button>
            <?php if ($search !== '' || $category !== null): ?>
                <a href="menu.php" class="btn-outline">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <div class="empty-box">
            <h3>Позиции не найдены</h3>
            <p>Попробуйте изменить поисковый запрос или выбрать другую категорию.</p>
            <a href="menu.php" class="btn-outline">Показать всё меню</a>
        </div>
    <?php else: ?>
        <div class="grid-menu">
            <?php foreach ($items as $item): ?>
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
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                                <input type="number" name="quantity" value="1" min="1" class="qty-input" required>
                                <button type="submit" name="add_to_cart" class="btn-icon" title="Добавить в корзину">+</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Постраничная навигация">
                <?php for ($p = 1; $p <= $totalPages; $p++): 
                    $queryParams = ['page' => $p];
                    if ($search !== '') $queryParams['search'] = $search;
                    if ($category !== null) $queryParams['category'] = $category;
                    $url = 'menu.php?' . http_build_query($queryParams);
                ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>