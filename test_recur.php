<?php
$id = "_8crjeh1i6oqjcb9k8kp3eb9k6sq46ba2851jiba365136h9m6grk2e1o8o_20260801T070000Z";
$parts = explode('_', $id);
$datePart = array_pop($parts);
$masterId = implode('_', $parts);
echo "Master: " . $masterId . "\nDate: " . $datePart . "\n";
