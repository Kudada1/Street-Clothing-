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

// Allowed sort columns
$allowedSorts = ['title', 'created_at', 'updated_at'];
$sort = $_GET['sort'] ?? 'title';
$sort = in_array($sort, $allowedSorts) ? $sort : 'title';

// Fetch sorted pages
$stmt = $db->prepare("SELECT page_id, title, created_at, updated_at FROM pages ORDER BY $sort ASC");
$stmt->execute();
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add category_id to the pages table if not already present
$stmt = $db->query("SELECT category_id FROM pages LIMIT 1");
if ($stmt === false) {
    $db->exec("ALTER TABLE pages ADD category_id INT NULL, ADD FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL");
}

// Fetch categories for the dropdown
$categories = $db->query("SELECT category_id, category_name FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Add image upload functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $title = trim($_POST['title'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $errors = [];

    if ($title === '') {
        $errors[] = 'Title is required.';
    }

    $image_id = null;
    if (!empty($_FILES['page_image']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        $filename = basename($_FILES['page_image']['name']);
        $target_path = $upload_dir . $filename;

        // Validate "image-ness"
        $file_type = mime_content_type($_FILES['page_image']['tmp_name']);
        if (in_array($file_type, ['image/jpeg', 'image/png', 'image/webp'])) {
            if (move_uploaded_file($_FILES['page_image']['tmp_name'], $target_path)) {
                $file_path = '/Webdev2/Final_Project/uploads/' . $filename; // Define the file path
                $alt_text = "Image for " . htmlspecialchars($title); // Generate alt text dynamically
                $uploaded_at = date('Y-m-d H:i:s'); // Set the current timestamp
                $stmt = $db->prepare("INSERT INTO images (filename, file_path, alt_text, uploaded_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$filename, $file_path, $alt_text, $uploaded_at]);
                $image_id = $db->lastInsertId();
            } else {
                $errors[] = 'Failed to upload image.';
            }
        } else {
            $errors[] = 'Invalid image file.';
        }
    }

    // Ensure `image_id` is set to NULL if no image is uploaded
    if (empty($image_id)) {
        $image_id = null;
    }

    // Generate a slug from the title
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    // Update the INSERT query to handle NULL for `image_id`
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO pages (title, slug, image_id, category_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $slug, $image_id, $category_id]);
        echo "<p style='color:green;'>✅ Page created successfully!</p>";
    }
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM pages WHERE page_id = ?");
    $stmt->execute([$delete_id]);
    echo "<p style='color:red;'>❌ Page deleted successfully!</p>";
}

// Handle edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $new_title = trim($_POST['new_title']);
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($new_title !== '') {
        $stmt = $db->prepare("UPDATE pages SET title = ?, category_id = ?, updated_at = NOW() WHERE page_id = ?");
        $stmt->execute([$new_title, $category_id, $edit_id]);
        echo "<p style='color:blue;'>✏️ Page updated successfully!</p>";
    } else {
        echo "<p style='color:red;'>⚠️ Title cannot be empty!</p>";
    }
}

// Handle image update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_image_id'])) {
    $page_id = (int)$_POST['update_image_id'];
    $errors = [];

    if (!empty($_FILES['new_page_image']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        $filename = basename($_FILES['new_page_image']['name']);
        $target_path = $upload_dir . $filename;

        // Validate "image-ness"
        $file_type = mime_content_type($_FILES['new_page_image']['tmp_name']);
    
        if (in_array($file_type, ['image/jpeg', 'image/png', 'image/webp'])) {
            if (move_uploaded_file($_FILES['new_page_image']['tmp_name'], $target_path)) {
                $file_path = '/Webdev2/Final_Project/uploads/' . $filename;
                $alt_text = "Updated image for page ID $page_id";
                $stmt = $db->prepare("UPDATE images SET filename = ?, file_path = ?, alt_text = ?, uploaded_at = NOW() WHERE image_id = (SELECT image_id FROM pages WHERE page_id = ?)");
                $stmt->execute([$filename, $file_path, $alt_text, $page_id]);
                echo "<p style='color:green;'>✅ Image updated successfully!</p>";
            } else {
                $errors[] = 'Failed to upload new image.';
            }
        } else {
            $errors[] = 'Invalid image file.';
        }
    } else {
        $errors[] = 'No image file selected.';
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color:red;'>⚠️ $error</p>";
        }
    }
}

// Handle image deletion
if (isset($_POST['delete_image_id'])) {
    $page_id = (int)$_POST['delete_image_id'];
    $stmt = $db->prepare("SELECT i.file_path FROM images i JOIN pages p ON i.image_id = p.image_id WHERE p.page_id = ?");
    $stmt->execute([$page_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image && !empty($image['file_path'])) {
        $file_path = __DIR__ . '/../' . ltrim($image['file_path'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $stmt = $db->prepare("UPDATE pages SET image_id = NULL WHERE page_id = ?");
        $stmt->execute([$page_id]);
        $stmt = $db->prepare("DELETE FROM images WHERE file_path = ?");
        $stmt->execute([$image['file_path']]);
        echo "<p style='color:red;'>❌ Image deleted successfully!</p>";
    } else {
        echo "<p style='color:red;'>⚠️ No image found to delete.</p>";
    }
}
?>

<h2>Page List</h2>

<p>Sorted by: 
    <a href="?sort=title" <?= $sort === 'title' ? 'style="font-weight:bold;"' : '' ?>>Title</a> |
    <a href="?sort=created_at" <?= $sort === 'created_at' ? 'style="font-weight:bold;"' : '' ?>>Created Date</a> |
    <a href="?sort=updated_at" <?= $sort === 'updated_at' ? 'style="font-weight:bold;"' : '' ?>>Updated Date</a>
</p>

<!-- Display pages with edit and delete options -->
<table border="1" cellpadding="8">
    <tr>
        <th>Title</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($pages as $page): ?>
    <tr>
        <td><?= htmlspecialchars($page['title']) ?></td>
        <td><?= htmlspecialchars($page['created_at']) ?></td>
        <td><?= htmlspecialchars($page['updated_at']) ?></td>
        <td>
            <!-- Edit form -->
            <form method="post" style="display:inline;">
                <input type="hidden" name="edit_id" value="<?= $page['page_id'] ?>">
                <input type="text" name="new_title" placeholder="New Title" required>
                <select name="category_id">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Edit</button>
            </form>
            |
            <!-- Delete form -->
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this page?');">
                <input type="hidden" name="delete_id" value="<?= $page['page_id'] ?>">
                <button type="submit">Delete</button>
            </form>
            |
            <!-- Update image form -->
            <form method="post" enctype="multipart/form-data" style="display:inline;">
                <input type="hidden" name="update_image_id" value="<?= $page['page_id'] ?>">
                <input type="file" name="new_page_image" required>
                <button type="submit">Update Image</button>
            </form>
            |
            <!-- Delete image form -->
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete the image for this page?');">
                <input type="hidden" name="delete_image_id" value="<?= $page['page_id'] ?>">
                <button type="submit">Delete Image</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<!-- Add form for creating pages with image upload -->
<form method="post" enctype="multipart/form-data">
    <label>Title:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Category:</label><br>
    <select name="category_id">
        <option value="">Select Category</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Page Image (optional):</label><br>
    <input type="file" name="page_image"><br><br>

    <button type="submit" name="create">Create Page</button>
</form>