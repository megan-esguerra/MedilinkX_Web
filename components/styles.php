<style>
    :root {
        --primary-color: #4a89dc;
        --secondary-color: #e1f0ff;
        --light-color: #ffffff;
        --text-color: #2c3e50;
        --light-text: #7b8a9d;
        --success-color: #37ca82;
        --warning-color: #ffb93c;
        --danger-color: #f25656;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f9ff;
        color: var(--text-color);
    }
    
    .sidebar {
        background-color: var(--light-color);
        height: 100vh;
        position: fixed;
        box-shadow: 0 0 15px rgba(122, 153, 198, 0.1);
        z-index: 1000;
        width: 250px;
        padding-top: 20px;
        left: 0;
        top: 0;
    }
    
    .sidebar .logo {
        padding: 15px 20px;
        margin-bottom: 20px;
        font-weight: bold;
        font-size: 18px;
        border-bottom: 1px solid rgba(122, 153, 198, 0.1);
        color: var(--text-color);
    }
    
    .sidebar .nav-item {
        margin-bottom: 5px;
    }
    
    .sidebar .nav-link {
        color: var(--light-text);
        padding: 10px 20px;
        border-radius: 8px;
        margin: 0 10px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
    }
    
    .sidebar .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
    
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background-color: var(--secondary-color);
        color: var(--primary-color);
    }
    
    .main-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
    }
    
    .header {
        background-color: var(--light-color);
        padding: 15px 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(122, 153, 198, 0.1);
        margin-bottom: 20px;
    }
    
    .search-bar {
        background-color: var(--secondary-color);
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        width: 300px;
    }
    
    .card {
        background-color: var(--light-color);
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(122, 153, 198, 0.1);
        border: none;
        margin-bottom: 20px;
    }
    
    .card-header {
        background-color: transparent;
        border-bottom: 1px solid rgba(122, 153, 198, 0.1);
        padding: 15px 20px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
    }
    
    .btn-primary:hover {
        background-color: #3b7bd0;
    }
    
    .table {
        margin-bottom: 0;
    }
    
    .table th {
        border-top: none;
        color: var(--light-text);
        font-weight: 500;
        padding: 12px;
    }
    
    .table td {
        vertical-align: middle;
        padding: 12px;
    }
    
    .account-section-title {
        font-size: 12px;
        color: var(--light-text);
        padding: 20px 20px 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .breadcrumb {
        margin-bottom: 0;
    }
    
    .breadcrumb-item a {
        color: var(--primary-color);
        text-decoration: none;
    }
    
    .breadcrumb-item.active {
        color: var(--text-color);
    }
    
    .form-control {
        border: 1px solid rgba(122, 153, 198, 0.2);
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(74, 137, 220, 0.25);
    }
    
    .btn {
        border-radius: 8px;
        padding: 8px 16px;
        font-weight: 500;
    }
    
    .btn-light {
        background-color: var(--secondary-color);
        border: none;
        color: var(--primary-color);
    }
    
    .btn-warning {
        background-color: var(--warning-color);
        border: none;
        color: white;
    }
    
    .btn-danger {
        background-color: var(--danger-color);
        border: none;
    }
</style>