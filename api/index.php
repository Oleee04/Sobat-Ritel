<?php
// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean up the URI
$uri = ltrim($uri, '/');

// If the URI is empty, default to index.php
if ($uri === '') {
    $uri = 'index.php';
}

// Target file in the root directory
$file = __DIR__ . '/../' . $uri;

// If the file doesn't have .php extension and it's not a static file, try adding .php
if (!file_exists($file) && !preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico)$/i', $uri)) {
    if (file_exists($file . '.php')) {
        $file .= '.php';
    }
}

// If the file exists and is a PHP file, execute it
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    // Set the working directory to the root so relative paths inside the files work
    chdir(__DIR__ . '/..');
    require $file;
    exit;
}

// Fallback: If it's a static file or doesn't exist, return 404
http_response_code(404);
echo "404 Not Found";
