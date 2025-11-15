<?php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

function is_get() { return $_SERVER['REQUEST_METHOD'] === 'GET'; }
function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}
function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}
function req($key, $default = null) {
    return $_REQUEST[$key] ?? $default;
}