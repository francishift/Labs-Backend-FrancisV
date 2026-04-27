<?php
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$request = \Illuminate\Http\Request::create('/admin/calendar/events', 'GET');
$controller = new \App\Http\Controllers\Admin\CalendarController();
$response = $controller->events($request);
echo json_encode(array_slice(json_decode($response->getContent(), true), 0, 1), JSON_PRETTY_PRINT);
