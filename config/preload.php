<?php

if (file_exists(dirname(__DIR__).'/.env.local.php')) {
    return include dirname(__DIR__).'/.env.local.php';
}

return [];
