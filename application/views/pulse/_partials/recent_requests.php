<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ============================================================
     Recent Requests
     - Quick filter buttons
     - Search + method + status filters
     - Requests table with bottleneck scoring
     - Pagination
     ============================================================ -->
<div class="section">
    <h2>📋 Recent Requests</h2>

    <!-- Quick Filter Buttons -->
    <div class="filter-bar">

        <div class="filter-quick">
            <span class="filter-label">Quick Filter:</span>
            <button class="qfilter active" data-filter="all"      onclick="setQuickFilter(this, 'all')">All</button>
            <button class="qfilter"        data-filter="critical" onclick="setQuickFilter(this, 'critical')">Critical</button>
            <button class="qfilter"        data-filter="warning"  onclick="setQuickFilter(this, 'warning')">Warning</button>
            <button class="qfilter"        data-filter="slow"     onclick="setQuickFilter(this, 'slow')">Slow Response</button>
            <button class="qfilter"        data-filter="memory"   onclick="setQuickFilter(this, 'memory')">Memory Hog</button>
            <button class="qfilter"        data-filter="db"       onclick="setQuickFilter(this, 'db')">Slow DB</button>
        </div>

        <div class="filter-controls">
            <span class="filter-divider">|</span>

            <input
                type="text"
                id="filter-search"
                placeholder="Search URI or controller..."
                oninput="applyFilters()"
                class="filter-input"
            />

            <select id="filter-method" onchange="applyFilters()" class="filter-select">
                <option value="">All Methods</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>

            <select id="filter-status" onchange="applyFilters()" class="filter-select">
                <option value="">All Status</option>
                <option value="2">2xx</option>
                <option value="3">3xx</option>
                <option value="4">4xx</option>
                <option value="5">5xx</option>
            </select>

            <input
                type="text"
                id="filter-username"
                placeholder="Filter by user..."
                oninput="applyFilters()"
                class="filter-input"
                style="max-width:160px;"
            />

            <span id="filter-count" class="filter-count"></span>
        </div>

    </div><!-- /filter-bar -->

    <!-- Requests Table -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Method</th>
                    <th>URI</th>
                    <th>Controller</th>
                    <th>Response</th>
                    <th>Memory</th>
                    <th>Queries</th>
                    <th>Avg Query</th>
                    <th>Status</th>
                    <th>User</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody id="recent-requests">

            <?php if ( ! empty($recent_requests)): ?>

                <?php foreach ($recent_requests as $r):

                    // --- Bottleneck scoring ---
                    $score  = 0;
                    $mem_mb = round($r['memory_peak_kb'] / 1024, 1);

                    // Response time score
                    if ($r['response_time_ms'] > 500)     $score += 2;
                    elseif ($r['response_time_ms'] > 200) $score += 1;

                    // Memory score
                    if ($mem_mb > 64)     $score += 2;
                    elseif ($mem_mb > 32) $score += 1;

                    // Avg query time score
                    if ($r['avg_query_ms'] > 100)     $score += 2;
                    elseif ($r['avg_query_ms'] > 50)  $score += 1;

                    // Severity label
                    if ($score >= 4) {
                        $severity       = 'CRITICAL';
                        $severity_class = 'red';
                        $severity_data  = 'critical';
                    } elseif ($score >= 2) {
                        $severity       = 'WARNING';
                        $severity_class = 'yellow';
                        $severity_data  = 'warning';
                    } else {
                        $severity       = 'OK';
                        $severity_class = 'green';
                        $severity_data  = 'ok';
                    }

                    // Individual signal flags for quick filters
                    $is_slow   = $r['response_time_ms'] > 200 ? 'true' : 'false';
                    $is_memory = $mem_mb > 32               ? 'true' : 'false';
                    $is_db     = $r['avg_query_ms'] > 50    ? 'true' : 'false';

                ?>
                    <tr
                        onclick="openDetail('<?= $r['request_id'] ?>')"
                        class="clickable-row"
                        data-severity="<?= $severity_data ?>"
                        data-method="<?= $r['method'] ?>"
                        data-uri="<?= htmlspecialchars(strtolower($r['uri'])) ?>"
                        data-controller="<?= htmlspecialchars(strtolower($r['controller'] . '/' . $r['action'])) ?>"
                        data-status="<?= $r['status_code'] ?>"
                        data-response="<?= $r['response_time_ms'] ?>"
                        data-slow="<?= $is_slow ?>"
                        data-memory="<?= $is_memory ?>"
                        data-db="<?= $is_db ?>"
                        data-username="<?= htmlspecialchars(strtolower($r['username'] ?? 'guest')) ?>"
                    >
                        <td>
                            <span class="tag <?= $severity_class ?>"><?= $severity ?></span>
                        </td>
                        <td>
                            <span class="tag <?= $r['method'] === 'GET' ? 'blue' : 'yellow' ?>">
                                <?= $r['method'] ?>
                            </span>
                        </td>
                        <td class="truncate-cell" title="<?= htmlspecialchars($r['uri']) ?>">
                            <?= htmlspecialchars($r['uri']) ?>
                        </td>
                        <td style="font-size:.78rem;">
                            <?= htmlspecialchars($r['controller'] . '/' . $r['action']) ?>
                        </td>
                        <td>
                            <span class="tag <?= $r['response_time_ms'] > 500 ? 'red' : ($r['response_time_ms'] > 200 ? 'yellow' : 'green') ?>">
                                <?= $r['response_time_ms'] ?>ms
                            </span>
                        </td>
                        <td>
                            <span class="tag <?= $mem_mb > 64 ? 'red' : ($mem_mb > 32 ? 'yellow' : 'green') ?>">
                                <?= $mem_mb ?>MB
                            </span>
                        </td>
                        <td class="text-center text-muted">
                            <?= $r['query_count'] ?>
                        </td>
                        <td>
                            <?php if ($r['avg_query_ms'] > 0): ?>
                                <span class="tag <?= $r['avg_query_ms'] > 100 ? 'red' : ($r['avg_query_ms'] > 50 ? 'yellow' : 'green') ?>">
                                    <?= $r['avg_query_ms'] ?>ms
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="tag <?= $r['status_code'] >= 500 ? 'red' : ($r['status_code'] >= 400 ? 'yellow' : 'green') ?>">
                                <?= $r['status_code'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="tag gray">
                                <?= htmlspecialchars($r['username'] ?? 'guest') ?>
                            </span>
                        </td>
                        <td class="text-muted nowrap text-sm">
                            <?= $r['created_at'] ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>
                <tr>
                    <td colspan="11" class="empty-cell">No requests recorded yet</td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div><!-- /table-wrap -->

    <!-- Pagination -->
    <div class="pagination" id="pagination"></div>

    <!-- No results message -->
    <div id="no-results" class="no-results" style="display:none;">
        No requests match your filters.
    </div>

</div><!-- /section -->