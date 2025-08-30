<?php
$host = "127.0.0.1";
$dbname = "minimarket";
$user = "root";
$pass = "ZtoprionZA425";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function createData($table, $data) {
    global $pdo;
    $columns = implode(", ", array_keys($data));
    $placeholders = ":" . implode(", :", array_keys($data));
    $sql = "INSERT INTO {$table} ($columns) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($data);
}

function updateData($table, $data) {
    global $pdo;
    $keys = array_keys($data);
    $whereKey = $keys[0]; 
    $whereVal = $data[$whereKey];
    unset($data[$whereKey]);

    $set = [];
    foreach ($data as $col => $val) {
        $set[] = "$col = :$col";
    }
    $sql = "UPDATE {$table} SET " . implode(", ", $set) . " WHERE {$whereKey} = :whereVal";
    $stmt = $pdo->prepare($sql);

    $params = $data;
    $params['whereVal'] = $whereVal;

    return $stmt->execute($params);
}

function deleteData($table, $where) {
    global $pdo;
    $conditions = [];
    foreach ($where as $col => $val) {
        $conditions[] = "$col = :$col";
    }
    $sql = "DELETE FROM {$table} WHERE " . implode(" AND ", $conditions);
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($where);
}

function readData($table, $where = []) {
    global $pdo;
    $sql = "SELECT * FROM {$table}";
    if (!empty($where)) {
        $conditions = [];
        foreach ($where as $col => $val) {
            $conditions[] = "$col = :$col";
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where);
    return $stmt->fetchAll();
}