<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/common.php';

redirect(is_admin_verified() ? '/admin/dashboard.php' : '/login.php');
