<?php
session_start();
require __DIR__ . '/db.php';
?>

<?php include __DIR__ . '/partials/header.php'; ?>

<style>
    body {
        background-color: white;
        color: black;
    }
</style>

<style>
.center-content {
    text-align: center;
    margin: 20px 0;
}
.center-content h2 {
    font-size: 2.5em;
    margin-bottom: 10px;
}
.center-content p {
    font-size: 1.2em;
    margin-bottom: 20px;
}
</style>

<div class="center-content">
    <h2>Welcome to Street Clothing</h2>
    <p>We’re a Winnipeg-based urban fashion brand bringing you high-quality, creative streetwear that celebrates individuality and culture.</p>
    <h2>Our Products</h2>
</div>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <!-- Removed the logout link -->
<?php endif; ?>

<?php if (!isset($_SESSION['user_id'])): ?>
    <p><a href="/Webdev2/Final_Project/street_clothing/index.php">Admin Login</a></p>
<?php endif; ?>

<style>
.product-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}
.product {
    text-align: center;
    width: 200px;
}
.product img {
    width: 100%;
    height: auto;
    border-radius: 10px;
}
.product-name {
    font-weight: bold;
    margin-bottom: 10px;
}
.product-price {
    margin-top: 5px;
    color: green;
    font-size: 1.2em;
}
</style>

<style>
    .category-section {
        margin-bottom: 30px;
    }
    .category-section h3 {
        text-align: center;
        margin-bottom: 15px;
    }
</style>

<?php
// Fetch categories and their products
$categories = $db->query("SELECT category_id, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($categories as $category):
    $stmt = $db->prepare("SELECT p.name, p.price, i.filename FROM products p LEFT JOIN images i ON p.image_id = i.image_id WHERE p.category_id = ?");
    $stmt->execute([$category['category_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="category-section">
    <h3><?= htmlspecialchars($category['category_name']) ?></h3>
    <div class="product-container">
        <?php foreach ($products as $product): ?>
        <div class="product">
            <div class="product-name"> <?= htmlspecialchars($product['name']) ?> </div>
            <?php if (!empty($product['filename'])): ?>
                <img src="/Webdev2/Final_Project/uploads/<?= htmlspecialchars($product['filename']) ?>" alt="Product Image">
            <?php else: ?>
                <img src="/Webdev2/Final_Project/uploads/default-placeholder.png" alt="No Image Available">
            <?php endif; ?>
            <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<style>
    .page-links {
        margin-top: 20px;
        text-align: center;
    }
    .page-links a {
        display: inline-block;
        margin: 5px;
        padding: 10px 15px;
        background-color: #007BFF;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }
    .page-links a:hover {
        background-color: #0056b3;
    }
</style>

<div class="page-links">
    <h3>Explore Our Pages</h3>
    <a href="/Webdev2/Final_Project/view-page.php?slug=beanies">Beanies</a>
    <a href="/Webdev2/Final_Project/view-page.php?slug=pants">Pants</a>
    <a href="/Webdev2/Final_Project/view-page.php?slug=hats">Hats</a>
</div>

<!-- Single comment button at the bottom left corner -->
<div style="position: fixed; bottom: 10px; left: 10px;">
    <form method="get" action="/Webdev2/Final_Project/street_clothing/index.php">
        <input type="hidden" name="redirect" value="comment">
        <button type="submit">Leave a Comment</button>
    </form>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>