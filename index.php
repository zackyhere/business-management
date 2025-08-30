<?php
// index.php
require_once "database/db.php";

// Ambil URI dari request
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Routing sederhana
switch ($request) {
    case '/':
        require __DIR__ . '/dashboard.php';
        break;
        
        case '/cek':
        $users = readData("product"); 
print_r($users);
        break;

    case '/product-list':
        require __DIR__ . '/product/list.php';
        break;
        
    case '/product-add':
        require __DIR__ . '/product/add.php';
        break;
        
    case '/product-edit':
        require __DIR__ . '/product/edit.php';
        break;
        
    case '/category':
        require __DIR__ . '/product/category.php';
        break;
        
    case '/invoice':
        require __DIR__ . '/transactions/invoice.php';
        break;
        
    case '/transactions-list':
        require __DIR__ . '/transactions/transactions.php';
        break;

    default:
        http_response_code(404);
        echo "404 - Halaman tidak ditemukan";
        break;
}