<?php
/**
 * index.php — Main public frontend
 * Serves templates/index.html exactly as Flask's render_template("index.html") did.
 * Zero changes to the HTML/CSS/JS.
 */
header('Content-Type: text/html; charset=UTF-8');
$template = __DIR__ . '/templates/index.html';
if (!file_exists($template)) {
    http_response_code(500);
    echo 'Template not found. Please upload templates/index.html';
    exit;
}
readfile($template);
