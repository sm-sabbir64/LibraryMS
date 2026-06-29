<?php
require 'db.php';
$stmt = $pdo->query("SELECT id, title FROM books");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total books: " . count($books) . "\n";
print_r($books);
