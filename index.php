<?php
include 'db.php';

// 🔧 Helper: Get component details for an assembly
function getComponentDetails($conn, $assembly_id) {
  $details = [];
  $res = $conn->query("SELECT p.item_name, p.image_path FROM product_components pc JOIN products p ON pc.component_id = p.id WHERE pc.assembly_id = $assembly_id");
  while ($row = $res->fetch_assoc()) {
    $details[] = $row;
  }
  return $details;
}

$where = "WHERE 1";
if (!empty($_GET['search'])) {
  $search = $conn->real_escape_string($_GET['search']);
  $where .= " AND (item_name LIKE '%$search%' OR item_code LIKE '%$search%')";
}
if (!empty($_GET['category'])) {
  $cat = $conn->real_escape_string($_GET['category']);
  $where .= " AND category = '$cat'";
}

$res = $conn->query("SELECT * FROM products $where ORDER BY item_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Product Catalog</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background-color: #f9f9f9;
      padding: 20px;
      color: #333;
    }
    h1 {
      font-size: 28px;
      margin-bottom: 20px;
      color: #2c3e50;
    }
    form {
      margin-bottom: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    input[type="text"], select {
      padding: 10px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      width: 200px;
    }
    button {
      padding: 10px 16px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0056b3;
    }
    .grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .card {
      background: #fff;
      border: 1px solid #ccc;
      padding: 15px;
      width: 250px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
      position: relative;
    }
    .card:hover {
      transform: scale(1.02);
    }
    .card .full-size-preview {
	  opacity: 0;
	  transition: opacity 0.3s ease;
	}

	.card:hover .full-size-preview {
	  display: block;
	  opacity: 1;
	}

	
	.card img {
	  width: 100%;
	  height: 150px;
	  object-fit: cover;
	  border-radius: 4px;
	  display: block;
	}

    .card h3 {
      margin: 10px 0 5px;
      font-size: 18px;
      color: #34495e;
    }
    .card p {
      margin: 4px 0;
      font-size: 14px;
    }
    .components {
      font-size: 13px;
      color: #555;
      margin-top: 8px;
    }
    .component-tooltip {
      position: relative;
      cursor: pointer;
      border-bottom: 1px dotted #888;
    }
    .component-tooltip:hover .tooltip-img {
      display: block;
    }
    .tooltip-img {
      display: none;
      position: absolute;
      top: 20px;
      left: 0;
      z-index: 10;
      background: #fff;
      border: 1px solid #ccc;
      padding: 5px;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    .tooltip-img img {
      max-width: 120px;
      height: auto;
    }
    .breakdown-btn {
      margin-top: 10px;
      background-color: #28a745;
    }
    .breakdown-btn:hover {
      background-color: #218838;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: #fff;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 80%;
      max-width: 600px;
    }
    .modal-content h2 {
      margin-top: 0;
    }
    .modal-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 15px;
    }
    .modal-grid .item {
      width: 120px;
      text-align: center;
    }
    .modal-grid img {
      max-width: 100%;
      border-radius: 6px;
    }
    .close-btn {
      float: right;
      font-size: 20px;
      cursor: pointer;
      color: #aaa;
    }
    .close-btn:hover {
      color: #000;
    }
    .clear-btn {
      background-color: #6c757d;
      margin-left: auto;
    }
    .clear-btn:hover {
      background-color: #5a6268;
    }

    .img-wrapper {
	  position: relative;
	  width: 100%;
	  height: 150px;
	  overflow: hidden;
	  border-radius: 4px;
	}
.full-size-preview img {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  width: auto;
  height: auto;
  max-width: 400px;
  max-height: 400px;
  border-radius: 4px;
  display: block;
}

.full-size-preview img:hover {
  transform: scale(1.8);
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  z-index: 10;
  position: relative;
}
@media (max-width: 768px) {
  .full-size-preview {
    display: none !important;
  }
}

  </style>
</head>
<body>
  <h1>📦 Product Catalog</h1>

  <!-- 🔍 Filter Form -->
  <form method="GET">
    <input type="text" name="search" placeholder="Search by name or code" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <select name="category">
      <option value="">All Categories</option>
      <option value="assembly" <?= ($_GET['category'] ?? '') === 'assembly' ? 'selected' : '' ?>>Assembly</option>
      <option value="component" <?= ($_GET['category'] ?? '') === 'component' ? 'selected' : '' ?>>Component</option>
    </select>
    <button type="submit">🔍 Filter</button>
    <?php if (!empty($_GET['search']) || !empty($_GET['category'])): ?>
      <button type="button" class="clear-btn" onclick="window.location.href='index.php'">🧹 Clear</button>
    <?php endif; ?>
  </form>

  <!-- 🧾 Product Grid -->
  <div class="grid">
    <?php
    if ($res->num_rows > 0) {
      while ($row = $res->fetch_assoc()) {
        $components = $row['category'] === 'assembly' ? getComponentDetails($conn, $row['id']) : [];

        echo "<div class='card'>
        <div class='img-wrapper'>
          <img src='{$row['image_path']}' alt='{$row['item_name']}' loading='lazy'>
          <div class='full-size-preview'>
            <img src='{$row['image_path']}' alt='Full view of {$row['item_name']}'>
          </div>
        </div>
        <h3>{$row['item_name']}</h3>
        <p><strong>Code:</strong> {$row['item_code']}</p>
        <p><strong>Category:</strong> " . ucfirst($row['category']) . "</p>
        <p><strong>Price:</strong> ₹{$row['price']}</p>";

        if (!empty($components)) {
          echo "<div class='components'>🔗 Includes: ";
          foreach ($components as $index => $comp) {
            echo "<span class='component-tooltip'>{$comp['item_name']}
                    <div class='tooltip-img'><img src='{$comp['image_path']}' alt='{$comp['item_name']}'></div>
                  </span>";
            if ($index < count($components) - 1) echo ", ";
          }
          echo "</div>";
          echo "<button class='breakdown-btn' onclick='openModal(" . json_encode($components) . ", \"" . addslashes($row['item_name']) . "\")'>🔍 View Breakdown</button>";
        }

        echo "</div>";
      }
    } else {
      echo "<p>No products found.</p>";
    }
    ?>
  </div>

  <!-- 🪟 Modal Popup -->
  <div id="breakdownModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">×</span>
      <h2 id="modalTitle">Assembly Breakdown</h2>
      <div class="modal-grid" id="modalGrid"></div>
      </div>

  <!-- 🧠 Scripts -->
  <script>
    function openModal(components, title) {
      document.getElementById('modalTitle').innerText = title + ' – Breakdown';
      const grid = document.getElementById('modalGrid');
      grid.innerHTML = '';
      components.forEach(comp => {
        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = `<img src="${comp.image_path}" alt="${comp.item_name}"><div>${comp.item_name}</div>`;
        grid.appendChild(div);
      });
      document.getElementById('breakdownModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('breakdownModal').style.display = 'none';
    }

    // Optional: Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('breakdownModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>