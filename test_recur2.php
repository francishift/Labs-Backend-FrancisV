<?php
$id1 = "_8crjeh1i6oqj_20260801T070000Z";
$id2 = "123_456_20260801";
$id3 = "normal_id";
var_dump(preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $id1));
var_dump(preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $id2));
var_dump(preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $id3));
