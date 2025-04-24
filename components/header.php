<?php
require_once __DIR__ . '/functions.php';
?>
<div class="d-flex justify-content-between align-items-center header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= ucfirst(getActivePage()) ?>
                </li>
            </ol>
        </nav>
        <h5 class="mb-0 mt-1"><?= ucfirst(getActivePage()) ?></h5>
    </div>
    <div class="d-flex align-items-center">
        <input type="text" class="form-control search-bar me-3" placeholder="Type here...">
        <button class="btn btn-light me-2">
            <i class="fas fa-cog"></i>
        </button>
        <button class="btn btn-light me-2">
            <i class="fas fa-bell"></i>
        </button>
        <!-- <button class="btn btn-primary">
            Sign in
        </button> -->
    </div>
</div>