<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/User.php';

if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $user = User::authenticate($email, $password);
        if ($user) {
            Auth::login($user);
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
$csrf_token = Security::generateCSRFToken();
include __DIR__ . '/../app/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo Security::escape($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <p class="mt-3">Don't have an account? <a href="signup.php">Sign up</a></p>
    </div>
</div>
<?php include __DIR__ . '/../app/views/footer.php'; ?>