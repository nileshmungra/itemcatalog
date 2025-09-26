<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: login.php");
  exit;
}
include 'db.php';

$success_message = "";

// 🔧 Helper: Get component IDs for an assembly
function getComponentIds($conn, $assembly_id) {
  $ids = [];
  $res = $conn->query("SELECT component_id FROM product_components WHERE assembly_id = $assembly_id");
  while ($row = $res->fetch_assoc()) {
    $ids[] = $row['component_id'];
  }
  return $ids;
}

// 🔧 Helper: Get component names for display
function getComponentNames($conn, $assembly_id) {
  $names = [];
  $res = $conn->query("SELECT p.item_name FROM product_components pc JOIN products p ON pc.component_id = p.id WHERE pc.assembly_id = $assembly_id");
  while ($row = $res->fetch_assoc()) {
    $names[] = $row['item_name'];
  }
  return implode(', ', $names);
}

// 🗑️ Handle Delete
if (isset($_GET['delete_id'])) {
  $id = $_GET['delete_id'];
  $conn->query("DELETE FROM products WHERE id = $id");
  $conn->query("DELETE FROM product_components WHERE assembly_id = $id");
  header("Location: admin.php?success=" . urlencode("🗑️ Product deleted"));
  exit;
}

// ✏️ Handle Edit Prefill
$edit_data = null;
if (isset($_GET['edit_id'])) {
  $id = $_GET['edit_id'];
  $res = $conn->query("SELECT * FROM products WHERE id = $id");
  $edit_data = $res->fetch_assoc();
}

// ✅ Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  $item_code = $_POST['item_code'];
  $item_name = $_POST['item_name'];
  $price = $_POST['price'];
  $category = $_POST['category'];
  $components = $_POST['components'] ?? [];

  $image_path = '';
  if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $target = 'assets/' . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $target);
    $image_path = $target;
  }

  if ($action === 'add_product') {
    $stmt = $conn->prepare("INSERT INTO products (item_code, item_name, price, image_path, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdss", $item_code, $item_name, $price, $image_path, $category);
    $stmt->execute();
    $new_id = $conn->insert_id;

    if ($category === 'assembly') {
      foreach ($components as $component_id) {
        $stmt = $conn->prepare("INSERT INTO product_components (assembly_id, component_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $new_id, $component_id);
        $stmt->execute();
      }
    }

    header("Location: admin.php?success=" . urlencode("✅ Product added successfully!"));
    exit;
  }

  if ($action === 'update_product') {
    $id = $_POST['product_id'];
    if ($image_path) {
      $stmt = $conn->prepare("UPDATE products SET item_code=?, item_name=?, price=?, image_path=?, category=? WHERE id=?");
      $stmt->bind_param("ssdssi", $item_code, $item_name, $price, $image_path, $category, $id);
    } else {
      $stmt = $conn->prepare("UPDATE products SET item_code=?, item_name=?, price=?, category=? WHERE id=?");
      $stmt->bind_param("ssdsi", $item_code, $item_name, $price, $category, $id);
    }
    $stmt->execute();

    $conn->query("DELETE FROM product_components WHERE assembly_id = $id");
    if ($category === 'assembly') {
      foreach ($components as $component_id) {
        $stmt = $conn->prepare("INSERT INTO product_components (assembly_id, component_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $id, $component_id);
        $stmt->execute();
      }
    }

    header("Location: admin.php?success=" . urlencode("✏️ Product updated"));
    exit;
  }
}

if (isset($_GET['success'])) {
  $success_message = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">
<html>
<head>
  <title>Admin – Product Catalog</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    #notification {
      background-color: #dff0d8;
      color: #3c763d;
      padding: 12px 20px;
      border-radius: 6px;
      font-weight: bold;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: opacity 0.5s ease;
    }
    .action-links a {
      margin-right: 10px;
      text-decoration: none;
      color: #007bff;
    }
    .action-links a:hover {
      text-decoration: underline;
    }
    select[multiple] {
      height: 100px;
      margin-top: 10px;
    }
	td img {
	  transition: transform 0.3s ease, box-shadow 0.3s ease;
	}
	td img:hover {
	  transform: scale(1.8);
	  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
	  z-index: 10;
	  position: relative;
	}
  </style>
</head>

<!-- 🔒 Admin Navbar -->
<div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; font-weight: bold;">
  <a href="admin.php" style="text-decoration: none;">🏠 Dashboard</a>
  <a href="index.php" target="_blank" style="color: green; text-decoration: none;">📦 Catalog</a>
  <a href="logout.php" style="color: red; text-decoration: none;">🚪 Logout</a>
</div>

<body>
  <h1>🛠️ Product Catalog Admin</h1>
  <?php if ($success_message): ?>
    <div id="notification"><?= $success_message ?></div>
  <?php endif; ?>

  <!-- ✅ Add/Edit Product Form -->
  <div class="section">
    <h2><?= $edit_data ? 'Edit Product' : 'Add New Product' ?></h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $edit_data ? 'update_product' : 'add_product' ?>">
      <?php if ($edit_data): ?>
        <input type="hidden" name="product_id" value="<?= $edit_data['id'] ?>">
      <?php endif; ?>
      <label for="item_code">Item Code</label>
		<input type="text" id="item_code" name="item_code" value="<?= $edit_data['item_code'] ?? '' ?>" required>

		<label for="item_name">Item Name</label>
		<input type="text" id="item_name" name="item_name" value="<?= $edit_data['item_name'] ?? '' ?>" required>

		<label for="price">Price</label>
		<input type="number" step="0.01" id="price" name="price" value="<?= $edit_data['price'] ?? '' ?>" required>

		<label for="category">Category</label>
		<select id="category" name="category" required>
		  <option value="component" <?= ($edit_data['category'] ?? '') === 'component' ? 'selected' : '' ?>>Component</option>
		  <option value="assembly" <?= ($edit_data['category'] ?? '') === 'assembly' ? 'selected' : '' ?>>Assembly</option>
		</select>

		<label for="image">Upload Image</label>
		<input type="file" id="image" name="image">

      <?php
      $components = $conn->query("SELECT id, item_name FROM products WHERE category = 'component'");
      ?>
      <div id="componentSelector" style="display: none;">
		  <label><strong>Select Components for Assembly:</strong></label>
		  <select name="components[]" multiple>
			<?php while ($comp = $components->fetch_assoc()): ?>
			  <option value="<?= $comp['id'] ?>"
				<?= isset($edit_data) && in_array($comp['id'], getComponentIds($conn, $edit_data['id'])) ? 'selected' : '' ?>>
				<?= $comp['item_name'] ?>
			  </option>
			<?php endwhile; ?>
		  </select>
	  </div>
	  

      <button type="submit"><?= $edit_data ? 'Update Product' : 'Add Product' ?></button>
    </form>
  </div>

  <!-- 📋 Product List -->
  <div class="section">
    <h2>All Products</h2>
    <table>
        <thead>
			<tr>
			  <th scope="col">Image</th>
			  <th scope="col">Name</th>
			  <th scope="col">Code</th>
			  <th scope="col">Category</th>
			  <th scope="col">Price</th>
			  <th scope="col">Actions</th>
			</tr>
		  </thead>
		<tbody>
      <?php
      $res = $conn->query("SELECT * FROM products ORDER BY item_name ASC");
      while ($row = $res->fetch_assoc()) {
        $components = $row['category'] === 'assembly' ? getComponentNames($conn, $row['id']) : '';
       echo "<tr>
		  <td><img src='{$row['image_path']}' alt='Image of {$row['item_name']}' loading='lazy' style='max-width:80px; border-radius: 6px;'></td>
		  <td>
			  <div style='font-weight: bold;'>{$row['item_name']}</div>
			  " . ($row['category'] === 'assembly' ? "<div style='font-size: 13px; color: #555;'>🔗 Includes: $components</div>" : "") . "
			</td>
		  
		  <td>{$row['item_code']}</td>
		  <td>" . ucfirst($row['category']) . "</td>
		  <td>₹{$row['price']}</td>
		  <td>
			<div style='display: flex; justify-content: center; gap: 10px;'>
			  <a href='admin.php?edit_id={$row['id']}' class='action-link'>✏️ Edit</a>
			  <a href='admin.php?delete_id={$row['id']}' onclick='return confirm(\"Delete this product?\")' class='action-link'>🗑️ Delete</a>
			</div>
		  </td>
		</tr>";
        if ($components) {
          #echo "<tr><td colspan='6' style='font-size: 0.9em; color: #555;'>🔗 Includes: $components</td></tr>";
        }
      }
      ?>
	   </tbody>
    </table>
  </div>

  <!-- 🧠 Auto-hide Notification + Clean URL -->
  <script>
    const note = document.getElementById('notification');
    if (note) {
      setTimeout(() => {
        note.style.opacity = '0';
        setTimeout(() => note.remove(), 500);
      }, 3000);

      const url = new URL(window.location);
      url.searchParams.delete('success');
      window.history.replaceState({}, document.title, url.pathname + url.search);
    }
  </script>
  
  <script>
	  const categorySelect = document.querySelector('select[name="category"]');
	  const componentBox = document.getElementById('componentSelector');

	  function toggleComponentBox() {
		componentBox.style.display = categorySelect.value === 'assembly' ? 'block' : 'none';
	  }

	  categorySelect.addEventListener('change', toggleComponentBox);
	  window.addEventListener('DOMContentLoaded', toggleComponentBox);
	</script>
</body>
</html>