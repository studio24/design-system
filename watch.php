<?php

/**
 * Router to use with local PHP server to rebuild templates on file change
 *
 * Usage:
 * php -S localhost:8000 -t dist watch.php
 */

// @todo add code to check if file changed, if so reload
// Support reload via Twig & JS command (e.g. build CSS)
if ($_SERVER["REQUEST_URI"] == '/') {
    return false;

} else {
    http_response_code(404);
    echo "PAGE NOT FOUND";
    return true;
}