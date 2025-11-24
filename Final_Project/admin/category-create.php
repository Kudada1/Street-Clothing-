<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../street_clothing/db.php';

// Restrict access to admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Webdev2/Final_Project/street_clothing/index.php');
    exit;
}

$name = '';
$errors = [];
$edit_id = 0;
$image_id = null;

// Fix the DELETE query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->execute([$delete_id]);
    echo "<p style='color:red;'>🗑️ Category deleted.</p>";
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $edit_id    = (int) $_POST['edit_id'];
    $name       = trim($_POST['category_name'] ?? '');

    if ($name === '')        $errors[] = 'Name is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE categories SET category_name = ?, category_id = ? WHERE category_id = ?");
        $stmt->execute([$name, $category_id, $edit_id]);
        echo "<p style='color:blue;'>✏️ Category updated successfully!</p>";
        $name = $price = '';
        $edit_id = 0;
    }
}

// Corrected the POST key to match the form input name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name = trim($_POST['category_name'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$name]);
        echo "<p style='color:green;'>✅ Category created successfully!</p>";
    }
}

// Load product for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$edit_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($category) {
        $name = $category['category_name'];
    } else {
        echo "<p style='color: red;'>Category not found.</p>";
    }
}
?>

<h2><?= $edit_id ? 'Edit Category' : 'Create Category' ?></h2>

<?php if (!empty($errors)): ?>
    <ul style="color: red;">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?php if ($edit_id): ?>
        <input type="hidden" name="update" value="1">
        <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
    <?php else: ?>
        <input type="hidden" name="create" value="1">
    <?php endif; ?>

    <label>Name:</label><br>
    <input name="category_name" type="text" value="<?= htmlspecialchars($name) ?>"><br><br>

    <button type="submit"><?= $edit_id ? 'Update Category' : 'Create Category' ?></button>
</form>

<hr>

<h2>Existing Categories</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
    </tr>
    <?php
    $stmt = $db->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($categories as $category):
    ?>
    <tr>
        <td><?= htmlspecialchars($category['category_name']) ?></td>
        <td>
            <form method="get" style="display:inline;">
                <input type="hidden" name="edit_id" value="<?= $category['category_id'] ?>">
                <button type="submit">Edit</button>
            </form>
            |
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="delete_id" value="<?= $category['category_id'] ?>">
                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>