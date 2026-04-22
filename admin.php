<?php
/**
 * admin.php — Admin portal frontend
 * Serves templates/admin.html exactly as Flask's render_template("admin.html") did.
 * Zero changes to the HTML/CSS/JS.
 */
header('Content-Type: text/html; charset=UTF-8');
$template = __DIR__ . '/templates/admin.html';
if (!file_exists($template)) {
    http_response_code(500);
    echo 'Template not found. Please upload templates/admin.html';
    exit;
}
readfile($template);
