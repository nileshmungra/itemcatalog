<?php
function renderProductCard($row, $conn) {
  $parent_code = $row['item_code'];
  $map_sql = "SELECT child_item_code, quantity FROM product_mapping WHERE parent_item_code = '$parent_code'";
  $map_result = $conn->query($map_sql);

  echo '<div class="card">';
  echo '<img src="' . htmlspecialchars($row['image_path']) . '" alt="' . htmlspecialchars($row['item_name']) . '">';
  echo '<h3>' . htmlspecialchars($row['item_name']) . '</h3>';
  echo '<p><strong>Code:</strong> ' . htmlspecialchars($row['item_code']) . '</p>';
  echo '<p><strong>HSN:</strong> ' . htmlspecialchars($row['hsn_code']) . '</p>';
  echo '<p><strong>Price:</strong> ₹' . htmlspecialchars($row['price']) . '</p>';

  if ($map_result->num_rows > 0) {
    echo '<div class="mapping"><strong>Includes:</strong><ul>';
    while ($map = $map_result->fetch_assoc()) {
      echo '<li>' . htmlspecialchars($map['child_item_code']) . ' × ' . $map['quantity'] . '</li>';
    }
    echo '</ul></div>';
  }

  echo '</div>';
}
?>