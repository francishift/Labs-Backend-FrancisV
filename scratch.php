<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$factura = \App\Models\Factura::first();
if (!$factura) { echo "No factura"; exit; }
try {
    $controller = app(\App\Http\Controllers\Admin\FacturaController::class);
    $output = $controller->exportPdf($factura, new \Illuminate\Http\Request());
    echo strlen($output->getContent()) . " bytes\n";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
