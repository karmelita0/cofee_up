<?php
$pageTitle = 'Мои заказы';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([(int)$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Получаем состав для всех заказов пользователя одним запросом
$itemsByOrder = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmtItems = $pdo->prepare("
        SELECT oi.order_id, oi.quantity, oi.price_per_item, m.title 
        FROM order_items oi 
        JOIN menu_items m ON oi.menu_item_id = m.id 
        WHERE oi.order_id IN ($placeholders)
    ");
    $stmtItems->execute($orderIds);
    foreach ($stmtItems->fetchAll() as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

$statuses = [
    'new' => ['label' => 'Новый', 'class' => 'status-new'],
    'cooking' => ['label' => 'Готовится', 'class' => 'status-cooking'],
    'ready' => ['label' => 'Готов к выдаче', 'class' => 'status-ready'],
    'completed' => ['label' => 'Выдан', 'class' => 'status-completed'],
    'cancelled' => ['label' => 'Отменён', 'class' => 'status-cancelled']
];
?>

<section class="container profile-page">
    <div class="section-header">
        <div>
            <h2>Мои заказы</h2>
            <p class="section-sub">История и актуальный статус готовности</p>
        </div>
        <a href="menu.php" class="btn-primary">Новый заказ</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-box">
            <h3>У вас пока нет заказов</h3>
            <p>Выберите напитки и блюда в каталоге, чтобы оформить первый заказ.</p>
            <a href="menu.php" class="btn-primary">Перейти в меню</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $o): 
                $st = $statuses[$o['status']] ?? ['label' => $o['status'], 'class' => ''];
            ?>
                <div class="order-card">
                    <div class="order-card-head">
                        <div>
                            <span class="order-number">Заказ №<?= (int)$o['id'] ?></span>
                            <span class="order-date">от <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></span>
                        </div>
                        <span class="status-badge <?= $st['class'] ?>"><?= htmlspecialchars($st['label']) ?></span>
                    </div>

                    <ul class="order-composition">
                        <?php foreach ($itemsByOrder[$o['id']] ?? [] as $pos): ?>
                            <li>
                                <span><?= htmlspecialchars($pos['title']) ?> &times; <?= (int)$pos['quantity'] ?> шт.</span>
                                <span><?= number_format($pos['price_per_item'] * $pos['quantity'], 0, '', ' ') ?> ₽</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="order-card-foot">
                        <span>Обновлено: <?= date('d.m.Y H:i', strtotime($o['updated_at'] ?? $o['created_at'])) ?></span>
                        <strong>Итого: <?= number_format($o['total_price'], 0, '', ' ') ?> ₽</strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>