<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/street_clothing/db.php';

// Get the slug from the query string
$slug = $_GET['slug'] ?? '';

if ($slug === '') {
    echo "<p style='color:red;'>Page not found.</p>";
    exit;
}

// Updated query to fetch products based on category_id
$stmt = $db->prepare("SELECT p.title, p.created_at, p.updated_at, i.file_path, i.alt_text, pr.name AS product_name, pr.price, pr.image_id
                      FROM pages p
                      LEFT JOIN categories c ON LOWER(p.title) = LOWER(c.category_name)
                      LEFT JOIN products pr ON c.category_id = pr.category_id
                      LEFT JOIN images i ON pr.image_id = i.image_id
                      WHERE LOWER(p.slug) = LOWER(TRIM(?))");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    echo "<p style='color:red;'>Page not found.</p>";
    exit;
}
?>

<?php include __DIR__ . '/partials.php/header.php'; ?>

<h1><?= htmlspecialchars($page['title']) ?></h1>

<?php if (isset($page['product_name'])): ?>
    <h2>Products in this Category</h2>
    <div class="product">
        <div class="product-name"> <?= htmlspecialchars($page['product_name']) ?> </div>
        <?php if (!empty($page['file_path'])): ?>
            <img src="<?= htmlspecialchars($page['file_path']) ?>" alt="Product Image" class="product-image">
        <?php else: ?>
            <p>No image available for this product.</p>
        <?php endif; ?>
        <div class="product-price">$<?= number_format($page['price'], 2) ?></div>
    </div>
<?php endif; ?>

<h2>Leave a Comment</h2>
<?php if (isset($_SESSION['user_id'])): ?>
    <form method="post">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($page['product_id'] ?? '') ?>">
        <textarea name="comment_text" rows="4" cols="50" placeholder="Write your comment here..." required></textarea><br>
        <button type="submit" name="comment">Submit Comment</button>
    </form>
<?php else: ?>
    <p>You must <a href="/Webdev2/Final_Project/street_clothing/index.php">log in</a> to leave a comment.</p>
<?php endif; ?>

<style>
    .product-image {
        width: 225px; /* Adjusted width to make images half times bigger */
        height: auto;
        border-radius: 10px;
    }
    .product {
        display: inline-block;
        text-align: center;
        margin: 10px;
    }
</style>

<p><a href="/Webdev2/Final_Project/street_clothing/home.php">Back to Home Page</a></p>

<?php include __DIR__ . '/partials.php/footer.php'; ?>