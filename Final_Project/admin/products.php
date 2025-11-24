<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../street_clothing/db.php';

// Access control: only admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Webdev2/Final_Project/index.php');
    exit;
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    // Delete the product from the database
    $stmt = $db->prepare("DELETE FROM products WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $delete_id]);

    // Redirect to the same page to refresh the product list
    header('Location: /Webdev2/Final_Project/admin/products.php');
    exit;
}

// Fetch products
$stmt = $db->query("SELECT p.product_id, p.name, p.price, p.created_at, c.category_name, i.filename
                      FROM products p
                      JOIN categories c ON p.category_id = c.category_id
                      LEFT JOIN images i ON p.image_id = i.image_id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../partials.php/header.php'; ?>

<h2>Street Clothing Product Catalog</h2>
<a href="/Webdev2/Final_Project/street_clothing/logout.php">Logout</a>
<p><a href="product-create.php" style="display: <?= $_SESSION['role'] === 'admin' ? 'inline' : 'none'; ?>;">+ Add New Product</a></p>
<p><a href="page-list.php">View Page List</a></p>

<table border="1" cellpadding="8">
    <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Created At</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($products as $product): ?>
    <tr>
        <td><?= htmlspecialchars($product['name']) ?></td>
        <td>$<?= number_format($product['price'], 2) ?></td>
        <td><?= htmlspecialchars($product['created_at']) ?></td>
        <td>
            <?php if (!empty($product['filename'])): ?>
                <img src="/Webdev2/Final_Project/uploads/<?= htmlspecialchars($product['filename']) ?>" alt="Product Image" style="width: 100px; height: auto;">
            <?php else: ?>
                No image available
            <?php endif; ?>
        </td>
        <td>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <form method="get" style="display:inline;">
                    <input type="hidden" name="edit_id" value="<?= $product['product_id'] ?>">
                    <button type="submit">Edit</button>
                </form>
                |
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                    <input type="hidden" name="delete_id" value="<?= $product['product_id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            <?php else: ?>
                View Only
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../partials.php/footer.php'; ?>