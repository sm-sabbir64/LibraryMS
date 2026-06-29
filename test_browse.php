<?php
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'browse.php';
$html = ob_get_clean();
echo substr_count($html, 'class="book-card');
