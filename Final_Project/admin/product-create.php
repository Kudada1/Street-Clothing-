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

$name = $price = '';
$category_id = 0;
$errors = [];
$edit_id = 0;
$image_id = null;

// Handle product deletion and remove corresponding page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    // Fetch the product name to delete the corresponding page
    $stmt = $db->prepare("SELECT name FROM products WHERE product_id = ?");
    $stmt->execute([$delete_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $stmt = $db->prepare("DELETE FROM pages WHERE title = ?");
        $stmt->execute([$product['name']]);
    }

    // Delete the product
    $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$delete_id]);
    echo "<p style='color:red;'>🗑️ Product and corresponding page deleted.</p>";
}

// Handle product update and update corresponding page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $edit_id    = (int) $_POST['edit_id'];
    $name       = trim($_POST['name'] ?? '');
    $price      = trim($_POST['price'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);

    if ($name === '')        $errors[] = 'Name is required.';
    if ($price === '' || !is_numeric($price)) $errors[] = 'Valid price is required.';
    if ($category_id <= 0)   $errors[] = 'Category is required.';

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE products SET name = ?, price = ?, category_id = ? WHERE product_id = ?");
        $stmt->execute([$name, $price, $category_id, $edit_id]);

        // Update the corresponding page
        $stmt = $db->prepare("UPDATE pages SET title = ?, updated_at = NOW() WHERE title = (SELECT name FROM products WHERE product_id = ?)");
        $stmt->execute([$name, $edit_id]);

        echo "<p style='color:blue;'>✏️ Product and corresponding page updated successfully!</p>";
        $name = $price = '';
        $category_id = 0;
        $edit_id = 0;
    }
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name       = trim($_POST['name'] ?? '');
    $price      = trim($_POST['price'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);

    if ($name === '')        $errors[] = 'Name is required.';
    if ($price === '' || !is_numeric($price)) $errors[] = 'Valid price is required.';
    if ($category_id <= 0)   $errors[] = 'Category is required.';

    // Handle image upload
    if (!empty($_FILES['product_image']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        $filename = basename($_FILES['product_image']['name']);
        $target_path = $upload_dir . $filename;

        // Update the INSERT query to include the uploaded_at column
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_path)) {
            $file_path = '/Webdev2/Final_Project/uploads/' . $filename; // Define the file path
            $alt_text = "Image of " . htmlspecialchars($name); // Generate alt text dynamically
            $uploaded_at = date('Y-m-d H:i:s'); // Set the current timestamp
            $stmt = $db->prepare("INSERT INTO images (filename, file_path, alt_text, uploaded_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$filename, $file_path, $alt_text, $uploaded_at]);
            $image_id = $db->lastInsertId();
        } else {
            $errors[] = 'Image upload failed.';
        }
    }

    if (empty($errors)) {
        $created_at = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO products (name, price, category_id, image_id, created_at)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $category_id, $image_id, $created_at]);
        echo "<p style='color:green;'>✅ Product created successfully!</p>";
        $name = $price = '';
        $category_id = 0;

        // After product creation, create a corresponding page
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $stmt = $db->prepare("INSERT INTO pages (title, slug, image_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $slug, $image_id]);
        echo "<p style='color:green;'>✅ Page created for product successfully!</p>";

        // Add a link to redirect the admin to the home page after creating a product
        echo "<p><a href='/Webdev2/Final_Project/street_clothing/home.php'>Go to Home Page</a></p>";
    }
}

// Load product for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$edit_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $name = $product['name'];
        $price = $product['price'];
        $category_id = $product['category_id'];
    } else {
        echo "<p style='color: red;'>Product not found.</p>";
    }
}
?>

<h2><?= $edit_id ? 'Edit Product' : 'Create Product' ?></h2>

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
    <input name="name" value="<?= htmlspecialchars($name) ?>"><br><br>

    <label>Price:</label><br>
    <input name="price" value="<?= htmlspecialchars($price) ?>"><br><br>

    <label>Category:</label><br>
    <select name="category_id">
        <?php
        $categories = $db->query("SELECT category_id, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($categories)) {
            echo "<option value=''>No categories available</option>";
        } else {
            foreach ($categories as $cat) {
                $selected = ($cat['category_id'] == $category_id) ? 'selected' : '';
                echo "<option value='{$cat['category_id']}' $selected>{$cat['category_name']}</option>";
            }
        }
        ?>
    </select><br><br>

    <label>Product Image:</label><br>
    <input type="file" name="product_image"><br><br>

    <button type="submit"><?= $edit_id ? 'Update Product' : 'Create Product' ?></button>
</form>

<hr>

<h2>Existing Products</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Category</th>
        <th>Actions</th>
    </tr>
    <?php
    $stmt = $db->query("SELECT p.product_id, p.name, p.price, p.created_at, c.category_name
                      FROM products p
                      JOIN categories c ON p.category_id = c.category_id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $product):
    ?>
    <tr>
        <td><?= htmlspecialchars($product['name']) ?></td>
        <td>$<?= number_format($product['price'], 2) ?></td>
        <td><?= htmlspecialchars($product['category_name']) ?></td>
        <td>
            <form method="get" style="display:inline;">
                <input type="hidden" name="edit_id" value="<?= $product['product_id'] ?>">
                <button type="submit">Edit</button>
            </form>
            |
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                <input type="hidden" name="delete_id" value="<?= $product['product_id'] ?>">
                <button type="submit">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>