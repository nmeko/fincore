<?php $page = basename($_SERVER['PHP_SELF'], '.php'); ?>
<button class="nav-toggle" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Open menu" onclick="toggleNav()">☰</button>
<div class="nav-backdrop" onclick="toggleNav(false)"></div>
<nav class="sidebar" id="sidebar" aria-label="Main">
  <div class="sidebar-brand">
    <span class="brand-icon">◈</span>
    <span class="brand-name">FinCore</span>
  </div>
  <ul class="nav-list">
    <li class="nav-section-label">Overview</li>
    <li><a href="index.php" class="nav-link <?= $page==='index'?'active':'' ?>">
      <span class="nav-icon">⊞</span> Dashboard
    </a></li>

    <li class="nav-section-label">Finance</li>
    <li><a href="sales.php" class="nav-link <?= $page==='sales'?'active':'' ?>">
      <span class="nav-icon">↑</span> Sales
    </a></li>
    <li><a href="payments.php" class="nav-link <?= $page==='payments'?'active':'' ?>">
      <span class="nav-icon">◎</span> Payments
    </a></li>
    <li><a href="expenses.php" class="nav-link <?= $page==='expenses'?'active':'' ?>">
      <span class="nav-icon">↓</span> Expenses
    </a></li>

    <li class="nav-section-label">Catalog</li>
    <li><a href="products.php" class="nav-link <?= $page==='products'?'active':'' ?>">
      <span class="nav-icon">▣</span> Products
    </a></li>
    <li><a href="inventory.php" class="nav-link <?= $page==='inventory'?'active':'' ?>">
      <span class="nav-icon">≡</span> Inventory
    </a></li>

    <li class="nav-section-label">People</li>
    <li><a href="customers.php" class="nav-link <?= $page==='customers'?'active':'' ?>">
      <span class="nav-icon">○</span> Customers
    </a></li>
    <li><a href="employees.php" class="nav-link <?= $page==='employees'?'active':'' ?>">
      <span class="nav-icon">◉</span> Employees
    </a></li>
  </ul>
  <div class="sidebar-footer">
    <span>FinCore v1.0</span>
    <form method="POST" action="logout.php"><?= csrf_field() ?>
      <button type="submit" class="logout-btn"><span aria-hidden="true">⎋</span> Logout</button>
    </form>
  </div>
</nav>
