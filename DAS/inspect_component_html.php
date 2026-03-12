<?php
require_once __DIR__ . '/config/config.php';

$code = '${MD_LND_DTLS}';
$stmt = $conn->prepare("SELECT * FROM document_components WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo "Component Found: " . $result['name'] . "\n";
    echo "HTML Content:\n";
    echo "--------------------------------------------------\n";
    echo $result['html_content'] . "\n";
    echo "--------------------------------------------------\n";
} else {
    echo "Component $code not found.\n";
}
