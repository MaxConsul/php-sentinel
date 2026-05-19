<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| PHP Sentinel — Pulse Dashboard Access Control
|--------------------------------------------------------------------------
*/

// -- IP Whitelist --
// Only these IPs can access /pulse
// Add your server IP, office IP, or VPN IP here
// '127.0.0.1' and '::1' = localhost
$config['pulse_allowed_ips'] = [
    '127.0.0.1',
    '::1',
    '122.54.191.90',
];

// -- Secret token (optional extra layer) --
// Access via: /pulse?token=your_secret_token
// Leave empty '' to disable token check
$config['pulse_token'] = 'change-this-to-a-strong-secret';

// -- Slow query threshold (ms) --
// Queries slower than this are highlighted red
$config['pulse_slow_query_ms'] = 100;

// -- How many days to keep metrics data --
$config['pulse_retention_days'] = 7;