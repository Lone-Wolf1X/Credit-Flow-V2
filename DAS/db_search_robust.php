<?php
require_once 'config/config.php';

$res = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Check if it's a view or table
    $create = $conn->query("SHOW CREATE TABLE `$table` ");
    if (!$create) continue;
    
    $cols = $conn->query("SHOW COLUMNS FROM `$table` ");
    if (!$cols) continue;
    
    $search_cols = [];
    while ($col = $cols->fetch_assoc()) {
        if (strpos($col['Type'], 'char') !== false || strpos($col['Type'], 'text') !== false) {
            $search_cols[] = $col['Field'];
        }
    }
    
    if (empty($search_cols)) continue;
    
    $where = [];
    foreach ($search_cols as $c) {
        $where[] = "`$c` LIKE '%Madhesh%'";
        $where[] = "`$c` LIKE '%Bagmati%'";
    }
    
    $query = "SELECT count(*) FROM `$table` WHERE " . implode(" OR ", $where);
    $res_count = $conn->query($query);
    if ($res_count) {
        $count = $res_count->fetch_row()[0];
        if ($count > 0) {
            echo "Found in table: $table ($count rows)\n";
        }
    }
}
?>
