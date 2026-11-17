</main>
<footer class="site-footer">
    <div class="container footer-content">
        <div class="footer-logo">nōri</div>
        <div class="footer-links">
            <a href="/cofee_up/index.php">Главная</a>
            <a href="/cofee_up/menu.php">Меню</a>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client'): ?>
                <a href="/cofee_up/cart.php">Корзина</a>
                <a href="/cofee_up/profile.php">Мои заказы</a>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <a href="/cofee_up/login.php">Вход и регистрация</a>
            <?php endif; ?>
        </div>
        <p>&copy; <?= date('Y') ?> Учебное кафе «nōri». Все права защищены.</p>
    </div>
</footer>
<script src="/cofee_up/assets/js/main.js?v=2.0"></script>
</body>
</html>