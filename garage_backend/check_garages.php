<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Garages in database:\n";
foreach (\App\Models\Garage::all() as $g) {
    echo "{$g->id} | {$g->code} | {$g->type} | {$g->status}\n";
}
