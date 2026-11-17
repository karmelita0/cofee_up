<?php
$pageTitle = 'Вход и регистрация';
include 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'manager' ? 'admin.php' : 'menu.php'));
    exit;
}

$activeTab = isset($_GET['register']) ? 'register' : 'login';
$formData = [
    'first_name' => '',
    'last_name'  => '',
    'phone'      => '',
    'login'      => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['login_btn'])) {
        $activeTab = 'login';
        $login = trim($_POST['login'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $formData['login'] = $login;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['login']      = $user['login'];
            $_SESSION['first_name'] = !empty($user['first_name']) ? $user['first_name'] : $user['login'];

            header("Location: " . ($user['role'] === 'manager' ? 'admin.php' : 'menu.php'));
            exit;
        } else {
            $error = "Неверный логин или пароль.";
        }
    }

    if (isset($_POST['register_btn'])) {
        $activeTab = 'register';
        $formData['first_name'] = trim($_POST['first_name'] ?? '');
        $formData['last_name']  = trim($_POST['last_name'] ?? '');
        $formData['phone']      = trim($_POST['phone'] ?? '');
        $formData['login']      = trim($_POST['login'] ?? '');
        $pass                   = $_POST['password'] ?? '';
        $passConfirm            = $_POST['password_confirm'] ?? '';

        if (mb_strlen($formData['first_name']) < 2) {
            $error = "Укажите имя (минимум 2 символа).";
        } elseif (mb_strlen($formData['last_name']) < 2) {
            $error = "Укажите фамилию (минимум 2 символа).";
        } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $formData['phone'])) {
            $error = "Введите корректный номер телефона (например, +7 999 123-45-67).";
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $formData['login'])) {
            $error = "Логин должен содержать от 3 до 30 символов (латиница, цифры, _ или -).";
        } elseif (strlen($pass) < 6) {
            $error = "Пароль должен содержать не менее 6 символов.";
        } elseif ($pass !== $passConfirm) {
            $error = "Введённые пароли не совпадают.";
        } else {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
            $checkStmt->execute([$formData['login']]);
            if ($checkStmt->fetch()) {
                $error = "Этот логин уже занят. Выберите другой.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (login, first_name, last_name, phone, password_hash, role) 
                    VALUES (?, ?, ?, ?, ?, 'client')
                ");
                $stmt->execute([
                    $formData['login'],
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['phone'],
                    $hash
                ]);

                $_SESSION['success'] = "Аккаунт создан. Теперь войдите, используя свой логин и пароль.";
                header("Location: login.php");
                exit;
            }
        }
    }
}
?>

<section class="auth-section container">
    <div class="auth-card <?= $activeTab === 'register' ? 'auth-card-wide' : '' ?>" id="auth-card">
        <div class="auth-tabs" role="tablist">
            <button type="button" class="auth-tab <?= $activeTab === 'login' ? 'active' : '' ?>" data-tab="login">Вход</button>
            <button type="button" class="auth-tab <?= $activeTab === 'register' ? 'active' : '' ?>" data-tab="register">Регистрация</button>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- ФОРМА ВХОДА -->
        <div class="auth-panel <?= $activeTab === 'login' ? 'active' : '' ?>" id="panel-login">
            <h2>Вход в систему</h2>
            <p class="auth-sub">Авторизуйтесь для оформления заказа или работы с панелью кафе</p>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="field-label" for="login-field">Логин</label>
                    <input id="login-field" type="text" name="login"
                           value="<?= $activeTab === 'login' ? htmlspecialchars($formData['login']) : '' ?>"
                           placeholder="Введите ваш логин" required>
                </div>

                <div class="form-group">
                    <label class="field-label" for="pass-field">Пароль</label>
                    <input id="pass-field" type="password" name="password" placeholder="Введите пароль" required>
                </div>

                <button type="submit" name="login_btn" class="btn-primary w-100">Войти в аккаунт</button>
            </form>

            <p class="auth-switch-hint">
                Нет учётной записи? <a href="login.php?register=1" class="js-switch-tab" data-target="register">Зарегистрироваться</a>
            </p>
        </div>

        <!-- ФОРМА РЕГИСТРАЦИИ -->
        <div class="auth-panel <?= $activeTab === 'register' ? 'active' : '' ?>" id="panel-register">
            <h2>Регистрация клиента</h2>
            <p class="auth-sub">Создайте профиль студента или сотрудника для предзаказа в учебном кафе</p>

            <form method="POST">
                <?= csrf_field() ?>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="field-label" for="reg-fname">Имя</label>
                        <input id="reg-fname" type="text" name="first_name"
                               value="<?= htmlspecialchars($formData['first_name']) ?>"
                               placeholder="Например: Анна" required>
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="reg-lname">Фамилия</label>
                        <input id="reg-lname" type="text" name="last_name"
                               value="<?= htmlspecialchars($formData['last_name']) ?>"
                               placeholder="Например: Смирнова" required>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="field-label" for="reg-phone">Телефон</label>
                        <input id="reg-phone" type="tel" name="phone"
                               value="<?= htmlspecialchars($formData['phone']) ?>"
                               placeholder="+7 (999) 123-45-67" required>
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="reg-login">Логин</label>
                        <input id="reg-login" type="text" name="login"
                               value="<?= $activeTab === 'register' ? htmlspecialchars($formData['login']) : '' ?>"
                               placeholder="Латиница, от 3 символов" required>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="field-label" for="reg-pass">Пароль</label>
                        <input id="reg-pass" type="password" name="password" placeholder="Минимум 6 символов" required>
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="reg-pass2">Подтверждение пароля</label>
                        <input id="reg-pass2" type="password" name="password_confirm" placeholder="Повторите пароль" required>
                    </div>
                </div>

                <button type="submit" name="register_btn" class="btn-primary w-100">Зарегистрироваться</button>
            </form>

            <p class="auth-switch-hint">
                Уже зарегистрированы? <a href="login.php" class="js-switch-tab" data-target="login">Войти в аккаунт</a>
            </p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>