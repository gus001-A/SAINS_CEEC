<?php
// Router SOLO para la vista previa local (php -S localhost:8050 -t public .claude/dev-router.php).
// La base es SQLite: database/database.sqlite.
foreach (['DB_CONNECTION' => 'mysql', 'APP_URL' => 'http://localhost:8050'] as $k => $v) {
    putenv("$k=$v");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}
chdir(__DIR__ . '/../public');
return require __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
