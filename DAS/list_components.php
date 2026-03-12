<?php
require_once __DIR__ . '/vendor/autoload.php';

// Database Connection
$das_conn = new mysqli('localhost', 'root', '', 'das_db');
if ($das_conn->connect_error) {
    die("Connection failed: " . $das_conn->connect_error);
}
$das_conn->set_charset("utf8mb4");

echo "=== DOCUMENT COMPONENTS ===\n";
$res = $das_conn->query("SELECT id, code, name, SUBSTRING(html_content, 1, 100) as snippet FROM document_components");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "[{$row['id']}] Code: {$row['code']} | Name: {$row['name']} \n";
        echo "    Snippet: " . str_replace("\n", " ", $row['snippet']) . "...\n";
    }
}

echo "\n=== DOC PARAGRAPHS ===\n";
$res = $das_conn->query("SELECT id, code, title, SUBSTRING(content, 1, 100) as snippet FROM doc_paragraphs");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "[{$row['id']}] Code: {$row['code']} | Title: {$row['title']} \n";
        echo "    Snippet: " . str_replace("\n", " ", $row['snippet']) . "...\n";
    }
}
?>
