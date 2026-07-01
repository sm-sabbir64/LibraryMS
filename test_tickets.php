<?php
require_once 'db.php';
try {
    \ = \->query(\"SELECT * FROM support_tickets\");
    \ = \->fetchAll(PDO::FETCH_ASSOC);
    print_r(\);
} catch (Exception \) {
    echo \->getMessage();
}
