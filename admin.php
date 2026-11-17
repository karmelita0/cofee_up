<?php
$pageTitle = 'Панель менеджера';
include 'includes/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die("<div class='container' style='padding:100px 20px;'>
            <div class='empty-box'>
                <h2>403 — Доступ запрещён</h2>
                <p>Данный раздел доступен только персоналу учебного кафе с ролью «Менеджер».</p>
                <a href='index.php' class='btn-primary'>Вернуться на главную</a>
            </div>
         </div></main></body></html>");
}

// Вспомогательная функция безопасной загрузки изображения блюда
function handle_menu_image_upload(string $fallbackName = 'default.jpg'): string {
    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return $fallbackName;
    }
    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
        return $fallbackName;
    }
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return $fallbackName;
    }
    $uploadDir = __DIR__ . '/assets/img/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $newName = 'item_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newName)) {
        return $newName;
    }
    return $fallbackName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // 1. Обновление статуса заказа
    if (isset($_POST['update_status'])) {
        $allowedStatuses = ['new', 'cooking', 'ready', 'completed', 'cancelled'];
        $status = $_POST['status'] ?? '';
        $orderId = (int)($_POST['order_id'] ?? 0);

        if (in_array($status, $allowedStatuses, true) && $orderId > 0) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $_SESSION['success'] = "Статус заказа №{$orderId} успешно обновлён.";
        }
        header("Location: admin.php");
        exit;
    }

    // 2. Создание новой позиции меню
    if (isset($_POST['create_item'])) {
        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $price       = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $imageText   = trim($_POST['image'] ?? '');
        $image       = handle_menu_image_upload($imageText !== '' ? $imageText : 'default.jpg');

        if ($title === '' || $categoryId <= 0 || $price <= 0) {
            $_SESSION['error'] = "Укажите название блюда, категорию и цену больше 0.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, title, price, description, image, is_available) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$categoryId, $title, $price, $description, $image]);
            $_SESSION['success'] = "Позиция «{$title}» добавлена в меню.";
        }
        header("Location: admin.php#catalog-section");
        exit;
    }

    // 3. Редактирование позиции меню
    if (isset($_POST['edit_item'])) {
        $id          = (int)($_POST['item_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $price       = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $imageText   = trim($_POST['image'] ?? 'default.jpg');
        $image       = handle_menu_image_upload($imageText !== '' ? $imageText : 'default.jpg');
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;

        if ($id > 0 && $title !== '' && $categoryId > 0 && $price > 0) {
            $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, title = ?, price = ?, description = ?, image = ?, is_available = ? WHERE id = ?");
            $stmt->execute([$categoryId, $title, $price, $description, $image, $isAvailable, $id]);
            $_SESSION['success'] = "Изменения для позиции «{$title}» сохранены.";
        } else {
            $_SESSION['error'] = "Проверьте корректность данных при редактировании.";
        }
        header("Location: admin.php#catalog-section");
        exit;
    }

    // 4. Быстрое возвращение скрытой позиции в меню
    if (isset($_POST['restore_item'])) {
        $id = (int)($_POST['item_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE menu_items SET is_available = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Позиция снова активна и отображается в каталоге.";
        }
        header("Location: admin.php#catalog-section");
        exit;
    }

    // 5. Удаление позиции (мягкое при наличии связей в order_items, полное если связей нет)
    if (isset($_POST['delete_item'])) {
        $id = (int)($_POST['item_id'] ?? 0);
        if ($id > 0) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE menu_item_id = ?");
            $checkStmt->execute([$id]);
            $inOrdersCount = (int)$checkStmt->fetchColumn();

            if ($inOrdersCount > 0) {
                $stmt = $pdo->prepare("UPDATE menu_items SET is_available = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Позиция связана с заказами ({$inOrdersCount}), поэтому выполнено мягкое удаление (скрыта из меню).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Позиция не участвовала в заказах и была полностью удалена из БД.";
            }
        }
        header("Location: admin.php#catalog-section");
        exit;
    }
}

// Фильтрация заказов по статусу
$statusFilter = $_GET['order_status'] ?? '';
$allowedFilterStatuses = ['new', 'cooking', 'ready', 'completed', 'cancelled'];
if (in_array($statusFilter, $allowedFilterStatuses, true)) {
    $stmtOrders = $pdo->prepare("
        SELECT o.*, u.login, u.first_name, u.last_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.status = ? 
        ORDER BY o.created_at DESC
    ");
    $stmtOrders->execute([$statusFilter]);
    $orders = $stmtOrders->fetchAll();
} else {
    $orders = $pdo->query("
        SELECT o.*, u.login, u.first_name, u.last_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ")->fetchAll();
}

$items = $pdo->query("
    SELECT m.*, c.name AS category_name 
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    ORDER BY m.is_available DESC, c.sort_order ASC, m.id DESC
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC")->fetchAll();

// Сводная статистика для шапки админки
$statsNew     = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$statsCooking = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cooking'")->fetchColumn();
$statsReady   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready'")->fetchColumn();
$statsMenu    = (int)$pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1")->fetchColumn();

// Состав заказов
$orderItemsMap = [];
$allOrderItems = $pdo->query("
    SELECT oi.order_id, oi.quantity, m.title 
    FROM order_items oi 
    JOIN menu_items m ON oi.menu_item_id = m.id
")->fetchAll();
foreach ($allOrderItems as $oi) {
    $orderItemsMap[$oi['order_id']][] = $oi['title'] . ' × ' . $oi['quantity'];
}

// Загрузка позиции для формы редактирования
$editItem = null;
if (isset($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmtEdit->execute([(int)$_GET['edit']]);
    $editItem = $stmtEdit->fetch();
}
?>

<section class="container admin-page">
    <div class="section-header">
        <div>
            <h1>Панель управления кафе</h1>
            <p class="section-sub">Обработка поступающих заказов и управление ассортиментом меню</p>
        </div>
        <a href="#item-form-section" class="btn-primary">+ Добавить блюдо</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- СВОДКА ПОКАЗАТЕЛЕЙ -->
    <div class="admin-kpi-grid">
        <div class="kpi-card">
            <span class="kpi-label">Новые заказы</span>
            <strong class="kpi-value"><?= $statsNew ?></strong>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Готовятся сейчас</span>
            <strong class="kpi-value kpi-terra"><?= $statsCooking ?></strong>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Готовы к выдаче</span>
            <strong class="kpi-value kpi-sage"><?= $statsReady ?></strong>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Активных блюд в меню</span>
            <strong class="kpi-value"><?= $statsMenu ?></strong>
        </div>
    </div>

    <!-- БЛОК 1: ЖУРНАЛ ЗАКАЗОВ -->
    <div class="admin-block-head">
        <h2 class="admin-subtitle">Журнал заказов</h2>
        <div class="status-pills">
            <a href="admin.php" class="pill <?= $statusFilter === '' ? 'active' : '' ?>">Все</a>
            <a href="admin.php?order_status=new" class="pill <?= $statusFilter === 'new' ? 'active' : '' ?>">Новые</a>
            <a href="admin.php?order_status=cooking" class="pill <?= $statusFilter === 'cooking' ? 'active' : '' ?>">Готовятся</a>
            <a href="admin.php?order_status=ready" class="pill <?= $statusFilter === 'ready' ? 'active' : '' ?>">Готовы</a>
            <a href="admin.php?order_status=completed" class="pill <?= $statusFilter === 'completed' ? 'active' : '' ?>">Выданы</a>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-box" style="padding: 36px 20px;">
            <h3>Заказов с выбранным статусом нет</h3>
            <p style="margin-bottom: 0;">Попробуйте выбрать другой фильтр или дождитесь новых заказов от студентов.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Клиент</th>
                        <th>Состав заказа</th>
                        <th>Сумма</th>
                        <th>Создан</th>
                        <th>Управление статусом</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): 
                        $fullName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                    ?>
                    <tr>
                        <td><strong>#<?= (int)$o['id'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($fullName !== '' ? $fullName : $o['login']) ?></strong>
                            <div class="muted">@<?= htmlspecialchars($o['login']) ?><?= !empty($o['phone']) ? ' • ' . htmlspecialchars($o['phone']) : '' ?></div>
                        </td>
                        <td class="order-items-cell"><?= htmlspecialchars(implode(', ', $orderItemsMap[$o['id']] ?? ['—'])) ?></td>
                        <td><strong><?= number_format($o['total_price'], 0, '', ' ') ?> ₽</strong></td>
                        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                <select name="status" class="admin-select">
                                    <option value="new" <?= $o['status'] === 'new' ? 'selected' : '' ?>>Новый</option>
                                    <option value="cooking" <?= $o['status'] === 'cooking' ? 'selected' : '' ?>>Готовится</option>
                                    <option value="ready" <?= $o['status'] === 'ready' ? 'selected' : '' ?>>Готов к выдаче</option>
                                    <option value="completed" <?= $o['status'] === 'completed' ? 'selected' : '' ?>>Выдан</option>
                                    <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Отменён</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-small">Обновить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- БЛОК 2: ФОРМА ДОБАВЛЕНИЯ / РЕДАКТИРОВАНИЯ ПОЗИЦИИ -->
    <div id="item-form-section" class="crud-card-wrapper">
        <div class="crud-card-head">
            <div>
                <h2 class="admin-subtitle" style="margin: 0;">
                    <?= $editItem ? 'Редактирование позиции #' . (int)$editItem['id'] . ' — «' . htmlspecialchars($editItem['title']) . '»' : 'Добавление новой позиции в меню' ?>
                </h2>
                <p class="section-sub">Заполните характеристики напитка или блюда для отображения в каталоге</p>
            </div>
            <?php if ($editItem): ?>
                <a href="admin.php#item-form-section" class="btn-outline btn-sm">+ Перейти к созданию новой</a>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data" class="crud-form">
            <?= csrf_field() ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="item_id" value="<?= (int)$editItem['id'] ?>">
            <?php endif; ?>

            <div class="crud-grid">
                <div>
                    <label class="field-label" for="item-title">Наименование блюда или напитка *</label>
                    <input id="item-title" type="text" name="title"
                           value="<?= htmlspecialchars($editItem['title'] ?? '') ?>"
                           placeholder="Например: Капучино 300 мл" required>
                </div>

                <div>
                    <label class="field-label" for="item-cat">Категория *</label>
                    <select id="item-cat" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (isset($editItem['category_id']) && (int)$editItem['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="field-label" for="item-price">Цена (₽) *</label>
                    <input id="item-price" type="number" step="0.01" min="1" name="price"
                           value="<?= htmlspecialchars($editItem['price'] ?? '') ?>"
                           placeholder="220" required>
                </div>

                <div>
                    <label class="field-label" for="item-image">Имя файла фото (в папке assets/img/)</label>
                    <input id="item-image" type="text" name="image"
                           value="<?= htmlspecialchars($editItem['image'] ?? 'default.jpg') ?>"
                           placeholder="cappuccino.jpg">
                </div>
            </div>

            <div class="crud-grid" style="margin-top: 4px;">
                <div>
                    <label class="field-label" for="item-desc">Описание состава и объёма</label>
                    <textarea id="item-desc" name="description" rows="3" placeholder="Классический эспрессо на свежеобжаренном зерне с бархатной молочной пенкой."><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="field-label" for="item-file">Или загрузите новое фото с компьютера (JPG, PNG, WEBP)</label>
                    <input id="item-file" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp" class="file-input">
                    <?php if ($editItem && !empty($editItem['image'])): ?>
                        <div class="edit-preview">
                            <span class="edit-preview-img" style="background-image: url('/cofee_up/assets/img/<?= htmlspecialchars($editItem['image']) ?>');"></span>
                            <span class="muted">Текущее изображение: <strong><?= htmlspecialchars($editItem['image']) ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($editItem): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_available" <?= (int)$editItem['is_available'] === 1 ? 'checked' : '' ?>>
                    Позиция активна и доступна клиентам для заказа
                </label>
            <?php endif; ?>

            <div class="crud-actions">
                <?php if ($editItem): ?>
                    <button type="submit" name="edit_item" class="btn-primary">Сохранить изменения</button>
                    <a href="admin.php#catalog-section" class="btn-outline">Отменить редактирование</a>
                <?php else: ?>
                    <button type="submit" name="create_item" class="btn-primary">Добавить позицию в меню</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- БЛОК 3: КАТАЛОГ БЛЮД И НАПИТКОВ -->
    <div id="catalog-section" class="admin-block-head" style="margin-top: 52px;">
        <h2 class="admin-subtitle" style="margin: 0;">Каталог блюд и напитков (<?= count($items) ?>)</h2>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Фото</th>
                    <th>ID</th>
                    <th>Наименование</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i): ?>
                <tr class="<?= !(int)$i['is_available'] ? 'row-muted' : '' ?>">
                    <td>
                        <div class="table-thumb" style="background-image: url('/cofee_up/assets/img/<?= htmlspecialchars($i['image'] ?: 'default.jpg') ?>');"></div>
                    </td>
                    <td>#<?= (int)$i['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($i['title']) ?></strong>
                        <?php if (!empty($i['description'])): ?>
                            <div class="muted" style="max-width: 280px;"><?= htmlspecialchars($i['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($i['category_name']) ?></td>
                    <td><strong><?= number_format($i['price'], 0, '', ' ') ?> ₽</strong></td>
                    <td>
                        <span class="status-badge <?= $i['is_available'] ? 'status-ready' : 'status-cancelled' ?>">
                            <?= $i['is_available'] ? 'В меню' : 'Скрыто' ?>
                        </span>
                    </td>
                    <td>
                        <div class="inline-form">
                            <a href="admin.php?edit=<?= (int)$i['id'] ?>#item-form-section" class="btn-outline btn-xs">Изменить</a>

                            <?php if (!(int)$i['is_available']): ?>
                                <form method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int)$i['id'] ?>">
                                    <button type="submit" name="restore_item" class="btn-small">Вернуть</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" class="inline-form" onsubmit="return confirm('Удалить позицию «<?= htmlspecialchars($i['title'], ENT_QUOTES) ?>»? Если она уже есть в заказах, она будет скрыта из меню (мягкое удаление).');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="item_id" value="<?= (int)$i['id'] ?>">
                                <button type="submit" name="delete_item" class="btn-danger-xs">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include 'includes/footer.php'; ?>