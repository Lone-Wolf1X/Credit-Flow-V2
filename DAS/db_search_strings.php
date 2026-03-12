<?php
require_once 'config/config.php';

$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];

$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $table = $row[0];
    $columns = $conn->query("SHOW COLUMNS FROM $table");
    $has_string_col = false;
    $cols_to_search = [];
    while ($col = $columns->fetch_assoc()) {
        if (strpos($col['Type'], 'char') !== false || strpos($col['Type'], 'text') !== false) {
            $cols_to_search[] = $col['Field'];
        }
    }
    
    if (!empty($cols_to_search)) {
        foreach ($provinces as $p) {
            $where = [];
            foreach ($cols_to_search as $c) {
                $where[] = "`$c` LIKE '%$p%'";
            }
            $query = "SELECT count(*) FROM `$table` WHERE " . implode(" OR ", $where);
            $count = $conn->query($query)->fetch_row()[0];
            if ($count > 0) {
                echo "Found '$p' in table '$table' ($count rows)\n";
                $sample = $conn->query("SELECT * FROM `$table` WHERE " . implode(" OR ", $where) . " LIMIT 1");
                print_r($sample->fetch_assoc());
            }
        }
    }
}
?>
