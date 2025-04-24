<?php
require_once __DIR__ . '/functions.php';
?>
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-chart-pie text-primary me-2"></i> MediLinkX
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= isActiveLink('dashboard') ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="products.php" class="nav-link <?= isActiveLink('products') ?>">
                <i class="fas fa-pills"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="categories.php" class="nav-link <?= isActiveLink('categories') ?>">
                <i class="fas fa-list"></i>
                <span>Categories</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="inventory_management.php" class="nav-link <?= isActiveLink('inventory_management') ?>">
                <i class="fas fa-boxes"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="sales_report.php" class="nav-link <?= isActiveLink('sales_report') ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Sales Report</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="user-management.php" class="nav-link">
            <i class="fa-solid fa-user-plus"></i> User Management
            </a>
        </li> -->
        
        <div class="account-section-title">
            ACCOUNT PAGES
        </div>
        
        <li class="nav-item">
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <!-- <li class="nav-item">
            <a href="." class="nav-link">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In</span>
            </a>
        </li> -->
        <li class="nav-item">
            <a href="registration.php" class="nav-link">
                <i class="fas fa-user-plus"></i>
                <span>Sign Up</span>
            </a>
        </li>
    </ul>
</div>