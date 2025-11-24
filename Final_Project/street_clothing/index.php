<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $db->prepare("SELECT user_id, username, password, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // 'admin', 'manager', or 'customer'

            if ($user['role'] === 'admin') {
                header('Location: /Webdev2/Final_Project/admin/products.php');
            } else {
                header('Location: /Webdev2/Final_Project/street_clothing/home.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Added logic to handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $comment = trim($_POST['comment_text'] ?? '');

    if (!$user_id) {
        $error = 'You must be logged in to leave a comment.';
    } elseif ($comment === '') {
        $error = 'Comment cannot be empty.';
    } else {
        $stmt = $db->prepare("INSERT INTO comments (user_id, product_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $product_id, $comment]);
        $success = 'Comment added successfully!';
    }
}
?>

<h2>Street Clothing Admin Login</h2>

<?php if ($error): ?>
    <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

<p><a href="/Webdev2/Final_Project/street_clothing/register.php">Register</a></p>