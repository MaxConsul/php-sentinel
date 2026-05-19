<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ============================================================
     Summary Cards — 6 key metrics for the last 24 hours
     ============================================================ -->
<div class="cards" id="cards">

    <?php $s = $summary; ?>

    <div class="card">
        <div class="label">Total Requests (24h)</div>
        <div class="value blue"><?= number_format($s['total_requests']) ?></div>
    </div>

    <div class="card">
        <div class="label">Avg Response Time</div>
        <div class="value <?= $s['avg_response'] > 500 ? 'red' : ($s['avg_response'] > 200 ? 'yellow' : 'green') ?>">
            <?= $s['avg_response'] ?>ms
        </div>
    </div>

    <div class="card">
        <div class="label">Slowest Response</div>
        <div class="value <?= $s['max_response'] > 1000 ? 'red' : 'yellow' ?>">
            <?= $s['max_response'] ?>ms
        </div>
    </div>

    <div class="card">
        <div class="label">Total Queries (24h)</div>
        <div class="value blue"><?= number_format($s['total_queries']) ?></div>
    </div>

    <div class="card">
        <div class="label">Slow Queries &gt;100ms</div>
        <div class="value <?= $s['slow_queries'] > 0 ? 'red' : 'green' ?>">
            <?= number_format($s['slow_queries']) ?>
        </div>
    </div>

    <div class="card">
        <div class="label">Avg Memory Usage</div>
        <div class="value <?= $s['avg_memory_mb'] > 64 ? 'red' : ($s['avg_memory_mb'] > 32 ? 'yellow' : 'green') ?>">
            <?= $s['avg_memory_mb'] ?>MB
        </div>
    </div>

</div>