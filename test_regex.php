<?php
$str = "_8crjeh1i6oqjcb9k8kp3eb9k6sq46ba2851jiba365136h9m6grk2e1o8o_20260901T070000Z";
$regex = '/_([0-9]{8})(T[0-9]{6}Z)?$/';
var_dump(preg_match($regex, $str));
var_dump(preg_replace($regex, '', $str));
