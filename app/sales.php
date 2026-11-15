<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
$conn = db();
[$msg, $type] = take_flash();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
  if ($_POST['action']==='add') {
    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['qty']);

    // Get unit price and current stock
    $pr = $conn->prepare("SELECT p.Price, COALESCE(i.Quantity,0) as stock FROM product p LEFT JOIN inventory i ON p.Product_ID=i.Product_ID WHERE p.Product_ID=?");
    $pr->bind_param("i", $product_id);
    $pr->execute();
    $unit_price = null; $current_stock = 0;  // stay null/0 when the product doesn't exist
    $pr->bind_result($unit_price, $current_stock);
    $pr->fetch();
    $pr->close();

    // PHP-side stock check (replaces trigger)
    if ($qty < 1 || $unit_price === null) {
      $msg = "Error: choose a product and a quantity of at least 1."; $type = 'error';
    } elseif ($current_stock < $qty) {
      $msg = "Error: Insufficient inventory. Only $current_stock units available."; $type = 'error';
    } else {

    $total = $unit_price * $qty;

    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("INSERT INTO sales (Customer_ID,Employee_ID,Date,Total_amount) VALUES (?,?,?,?)");
      $stmt->bind_param("iisd",$_POST['customer_id'],$_POST['employee_id'],$_POST['date'],$total);
      $stmt->execute();
      $sale_id = $stmt->insert_id;
      $stmt->close();

      $sp = $conn->prepare("INSERT INTO sale_product (Sale_ID,Product_ID,Quantity,Unit_price) VALUES (?,?,?,?)");
      $sp->bind_param("iiid", $sale_id, $product_id, $qty, $unit_price);
      $sp->execute();
      $sp->close();

      $conn->commit();
      $msg = "Sale recorded. Inventory updated."; $type = 'success';
    } catch (\mysqli_sql_exception $e) {
      $conn->rollback();
      $msg = db_error($e); $type = 'error';
    } catch (\Exception $e) {
      $conn->rollback();
      $msg = db_error($e); $type = 'error';
    }
    } // end stock check else
  }
  if ($_POST['action']==='delete') {
    $sale_id = intval($_POST['id']);
    $conn->begin_transaction();
    try {
      // Restore inventory for all products in this sale before deleting
      $restore = $conn->prepare("UPDATE inventory i JOIN sale_product sp ON i.Product_ID = sp.Product_ID SET i.Quantity = i.Quantity + sp.Quantity WHERE sp.Sale_ID = ?");
      $restore->bind_param("i", $sale_id);
      $restore->execute();
      $restore->close();

      $d1 = $conn->prepare("DELETE FROM sale_product WHERE Sale_ID=?");
      $d1->bind_param("i", $sale_id);
      $d1->execute();
      $d1->close();

      $d2 = $conn->prepare("DELETE FROM sales WHERE Sale_ID=?");
      $d2->bind_param("i", $sale_id);
      $d2->execute();
      $d2->close();

      $conn->commit();
      $msg = 'Sale deleted. Inventory restored.'; $type = 'success';
    } catch (\Exception $e) {
      $conn->rollback();
      $msg = db_error($e); $type = 'error';
    }
  }
}

$customers = $conn->query("SELECT Customer_ID, Name FROM customer ORDER BY Name");
$employees = $conn->query("SELECT Employee_ID, Employee_name FROM employee ORDER BY Employee_name");
$products  = $conn->query("SELECT p.Product_ID, p.Product_name, p.Price, i.Quantity FROM product p LEFT JOIN inventory i ON p.Product_ID = i.Product_ID ORDER BY p.Product_name");

$search = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$sql = "SELECT s.Sale_ID, c.Name AS customer, e.Employee_name AS employee,
               s.Date, s.Total_amount
        FROM sales s
        LEFT JOIN customer c ON s.Customer_ID = c.Customer_ID
        LEFT JOIN employee e ON s.Employee_ID = e.Employee_ID";
if ($search) $sql .= " WHERE c.Name LIKE '%$search%' OR e.Employee_name LIKE '%$search%'";
$sql .= " ORDER BY s.Date DESC";
$result = $conn->query($sql);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FinCore – Sales</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<main class="main-content">
  <header class="page-header">
    <div>
      <h1>Sales</h1>
      <p class="page-sub">Track and manage all sales transactions</p>
    </div>
    <div class="header-actions">
      <button class="btn btn-primary" onclick="openModal('addModal')">+ Record Sale</button>
    </div>
  </header>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="page-card">
    <div class="filter-bar">
      <form method="GET" style="display:flex;gap:.75rem;flex:1;flex-wrap:wrap;">
        <input class="form-control search-input" name="q" placeholder="Search by customer or employee…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?><a href="sales.php" class="btn btn-secondary">Clear</a><?php endif; ?>
      </form>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Sale #</th><th>Customer</th><th>Employee</th><th>Date</th><th>Total</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="6" class="empty-msg">No sales records found.</td></tr>
      <?php else: ?>
        <?php while ($r = $result->fetch_assoc()): ?>
        <tr>
          <td class="mono">#<?= $r['Sale_ID'] ?></td>
          <td><?= htmlspecialchars($r['customer'] ?? '—') ?></td>
          <td class="muted"><?= htmlspecialchars($r['employee'] ?? '—') ?></td>
          <td class="mono muted"><?= $r['Date'] ?></td>
          <td class="mono green-text">$<?= number_format($r['Total_amount'],2) ?></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete sale #<?= $r['Sale_ID'] ?><?= csrf_field() ?>?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $r['Sale_ID'] ?>">
              <button type="submit" class="btn btn-danger" style="padding:.3rem .65rem;font-size:.78rem;">Delete</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Add Sale Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Record Sale</span>
      <button class="modal-close" onclick="closeModal('addModal')">×</button>
    </div>
    <form method="POST"><?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Customer</label>
          <select class="form-control" name="customer_id" required>
            <option value="">— Select —</option>
            <?php while ($c = $customers->fetch_assoc()): ?>
              <option value="<?= $c['Customer_ID'] ?>"><?= htmlspecialchars($c['Name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Employee</label>
          <select class="form-control" name="employee_id" required>
            <option value="">— Select —</option>
            <?php while ($e = $employees->fetch_assoc()): ?>
              <option value="<?= $e['Employee_ID'] ?>"><?= htmlspecialchars($e['Employee_name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Product</label>
          <select class="form-control" name="product_id" id="product_select" required onchange="updateTotal()">
            <option value="">— Select Product —</option>
            <?php while ($p = $products->fetch_assoc()): ?>
              <option value="<?= $p['Product_ID'] ?>" data-price="<?= $p['Price'] ?>" data-stock="<?= $p['Quantity'] ?? 0 ?>">
                <?= htmlspecialchars($p['Product_name']) ?> — $<?= number_format($p['Price'],2) ?> (<?= $p['Quantity'] ?? 0 ?> in stock)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity</label>
          <input class="form-control" type="number" name="qty" id="qty_input" min="1" required placeholder="1" oninput="updateTotal()">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date</label>
          <input class="form-control" type="date" name="date" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Calculated Total</label>
          <input class="form-control" type="text" id="total_display" placeholder="$0.00" readonly style="background:#f5f5f5;">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Sale</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/app.js"></script>
<script>
function updateTotal() {
  const sel = document.getElementById('product_select');
  const qty = parseInt(document.getElementById('qty_input').value) || 0;
  const price = parseFloat(sel.options[sel.selectedIndex]?.dataset.price) || 0;
  document.getElementById('total_display').value = '$' + (price * qty).toFixed(2);
}
</script>
</body></html>
