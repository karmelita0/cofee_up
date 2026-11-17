<?php
$pageTitle = 'Корзина';
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Для работы с корзиной и оформления заказа необходимо войти в аккаунт.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Добавление позиции в корзину
    if (isset($_POST['add_to_cart'])) {
        $id = (int)($_POST['id'] ?? 0);
        $rawQty = $_POST['quantity'] ?? '';
        $qty = filter_var($rawQty, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if ($qty === false || $qty <= 0) {
            $_SESSION['error'] = "Ошибка валидации: количество позиции должно быть целым числом больше нуля (минимум 1).";
            $back = $_POST['redirect'] ?? 'cart.php';
            header("Location: " . $back);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT m.id, m.title, m.price, m.image, c.name AS category_name 
            FROM menu_items m 
            JOIN categories c ON m.category_id = c.id 
            WHERE m.id = ? AND m.is_available = 1
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if ($item) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['quantity'] += $qty;
            } else {
                $_SESSION['cart'][$id] = [
                    'title'    => $item['title'],
                    'category' => $item['category_name'],
                    'image'    => $item['image'],
                    'price'    => (float)$item['price'],
                    'quantity' => $qty
                ];
            }
            $_SESSION['success'] = "Позиция «{$item['title']}» ({$qty} шт.) добавлена в корзину.";
        }
        header("Location: cart.php");
        exit;
    }

    // Изменение количества в корзине (ввод числа или кнопки +/-)
    if (isset($_POST['update_qty'])) {
        $id = (int)($_POST['id'] ?? 0);
        $qty = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if ($qty === false || $qty <= 0) {
            $_SESSION['error'] = "Количество позиции должно быть строго положительным целым числом (≥ 1).";
        } elseif (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity'] = $qty;
        }
        header("Location: cart.php");
        exit;
    }

    // Удаление позиции из корзины
    if (isset($_POST['remove_item'])) {
        $id = (int)($_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        header("Location: cart.php");
        exit;
    }

    // Полная очистка корзины
    if (isset($_POST['clear_cart'])) {
        unset($_SESSION['cart']);
        header("Location: cart.php");
        exit;
    }

    // Оформление заказа (ACID-транзакция + правило Варианта 19)
    if (isset($_POST['checkout'])) {
        if (empty($_SESSION['cart'])) {
            $_SESSION['error'] = "Корзина пуста.";
            header("Location: cart.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $total = 0;
            $validatedItems = [];

            foreach ($_SESSION['cart'] as $menuItemId => $cartItem) {
                $qty = filter_var($cartItem['quantity'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
                if ($qty === false || $qty <= 0) {
                    throw new Exception("Нарушено ограничение количества: позиция должна иметь количество > 0.");
                }

                $stmtCheck = $pdo->prepare("SELECT id, price, is_available FROM menu_items WHERE id = ? FOR UPDATE");
                $stmtCheck->execute([(int)$menuItemId]);
                $dbItem = $stmtCheck->fetch();

                if (!$dbItem || !(int)$dbItem['is_available']) {
                    throw new Exception("Одна из выбранных позиций временно недоступна в меню.");
                }

                $price = (float)$dbItem['price'];
                $total += $price * $qty;
                $validatedItems[] = [
                    'menu_item_id'   => (int)$dbItem['id'],
                    'quantity'       => $qty,
                    'price_per_item' => $price
                ];
            }

            $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, status, total_price) VALUES (?, 'new', ?)");
            $stmtOrder->execute([(int)$_SESSION['user_id'], $total]);
            $orderId = (int)$pdo->lastInsertId();

            $stmtOrderItem = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price_per_item) VALUES (?, ?, ?, ?)");
            foreach ($validatedItems as $vItem) {
                $stmtOrderItem->execute([
                    $orderId,
                    $vItem['menu_item_id'],
                    $vItem['quantity'],
                    $vItem['price_per_item']
                ]);
            }

            $pdo->commit();
            unset($_SESSION['cart']);
            $_SESSION['success'] = "Заказ №{$orderId} успешно оформлен и передан в работу.";
            header("Location: profile.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = "Транзакция отменена: " . $e->getMessage();
            header("Location: cart.php");
            exit;
        }
    }
}
?>

<section class="container cart-page">
    <div class="section-header">
        <div>
            <h1>Корзина заказа</h1>
            <p class="section-sub">Проверьте выбранные позиции перед отправкой на кухню учебного кафе</p>
        </div>
        <a href="menu.php" class="btn-outline">Продолжить выбор</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-box">
            <h3>Ваша корзина пока пуста</h3>
            <p>Перейдите в меню, чтобы выбрать кофе, напитки или свежую выпечку.</p>
            <a href="menu.php" class="btn-primary">Открыть меню</a>
        </div>
    <?php else: ?>
        <?php
        $totalSum = 0;
        $totalCount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $totalSum += $item['price'] * $item['quantity'];
            $totalCount += $item['quantity'];
        }
        ?>
        <div class="cart-layout">
            <!-- ЛЕВАЯ КОЛОНКА: СПИСОК ТОВАРОВ -->
            <div class="cart-list-box">
                <div class="cart-list-head">
                    <span>Позиции в заказе (<?= $totalCount ?> шт.)</span>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" name="clear_cart" class="btn-link-danger">Очистить всё</button>
                    </form>
                </div>

                <?php foreach ($_SESSION['cart'] as $id => $item):
                    $lineTotal = $item['price'] * $item['quantity'];
                    $imgName = !empty($item['image']) ? $item['image'] : 'default.jpg';
                ?>
                    <div class="cart-row">
                        <div class="cart-thumb" style="background-image: url('/cofee_up/assets/img/<?= htmlspecialchars($imgName) ?>');"></div>

                        <div class="cart-details">
                            <?php if (!empty($item['category'])): ?>
                                <span class="cart-cat"><?= htmlspecialchars($item['category']) ?></span>
                            <?php endif; ?>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <span class="cart-unit-price"><?= number_format($item['price'], 0, '', ' ') ?> ₽ за 1 шт.</span>
                        </div>

                        <div class="cart-qty-block">
                            <form method="POST" class="qty-stepper">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="1" class="qty-input" required>
                                <button type="submit" name="update_qty" class="btn-small">Ок</button>
                            </form>
                        </div>

                        <div class="cart-sum-block">
                            <span class="cart-line-total"><?= number_format($lineTotal, 0, '', ' ') ?> ₽</span>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$id ?>">
                                <button type="submit" name="remove_item" class="btn-remove-text">Удалить</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: ИТОГИ ОФОРМЛЕНИЯ -->
            <aside class="cart-summary-box">
                <h3>Итог заказа</h3>

                <div class="summary-line">
                    <span>Количество позиций</span>
                    <span><?= $totalCount ?> шт.</span>
                </div>
                <div class="summary-line">
                    <span>Способ получения</span>
                    <span>Самовывоз (стойка кафе)</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-total">
                    <span>К оплате</span>
                    <strong><?= number_format($totalSum, 0, '', ' ') ?> ₽</strong>
                </div>

                <form method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" name="checkout" class="btn-primary w-100">Оформить заказ</button>
                </form>
                <p class="summary-note">После подтверждения заказ сразу поступит бариста, а статус готовности появится в разделе «Мои заказы».</p>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>