<?php
// Compatibility shim: some code expects `config/mail.php` to exist.
// Reuse the root mail implementation.
$rootMail = __DIR__ . '/../mail.php';
if (file_exists($rootMail)) {
    require_once $rootMail;
} else {
    // Fail loudly in development; in production prefer logging and graceful fallback
    throw new \RuntimeException('Missing mail implementation: ' . $rootMail);
}
