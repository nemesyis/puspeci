<?php
// Compatibility shim: project historically required `config/mail.php`.
// Reuse the root `mail.php` implementation to avoid duplication.
require_once __DIR__ . '/../mail.php';
