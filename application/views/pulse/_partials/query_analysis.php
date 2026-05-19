<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ============================================================
     Query Analysis
     - Slowest Controllers
     - Slowest Queries
     - Query Type Breakdown
     - N+1 Detector
     - Most Repeated Queries
     ============================================================ -->

<!-- Two column: Slowest Controllers + Slowest Queries -->
<div class="two-col">

    <!-- Slowest Controllers -->
    <div class="section">
        <h2>🐢 Slowest Controllers (24h)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Controller</th>
                        <th>Method</th>
                        <th>Avg ms</th>
                        <th>Max ms</th>
                        <th>Avg Mem</th>
                        <th>Avg Queries</th>
                        <th>Hits</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! empty($slow_controllers)): ?>
                    <?php foreach ($slow_controllers as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['controller']) ?></td>
                            <td>
                                <span class="tag blue">
                                    <?= htmlspecialchars($r['method']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="tag <?= $r['avg_ms'] > 500 ? 'red' : ($r['avg_ms'] > 200 ? 'yellow' : 'green') ?>">
                                    <?= $r['avg_ms'] ?>ms
                                </span>
                            </td>
                            <td><?= $r['max_ms'] ?>ms</td>
                            <td><?= $r['avg_memory_mb'] ?>MB</td>
                            <td><?= $r['avg_queries'] ?></td>
                            <td><span class="tag gray"><?= $r['hit_count'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-cell">No data yet</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Slowest Queries -->
    <div class="section">
        <h2>🔍 Slowest Queries (24h)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SQL</th>
                        <th>Time</th>
                        <th>Controller</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ( ! empty($slow_queries)): ?>
                    <?php foreach ($slow_queries as $r): ?>
                        <tr>
                            <td class="sql-cell" title="<?= htmlspecialchars($r['query_sql']) ?>">
                                <?= htmlspecialchars(substr($r['query_sql'], 0, 80)) ?>
                            </td>
                            <td>
                                <span class="tag <?= $r['execution_ms'] > 500 ? 'red' : ($r['execution_ms'] > 100 ? 'yellow' : 'green') ?>">
                                    <?= $r['execution_ms'] ?>ms
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['controller'] . '/' . $r['method']) ?></td>
                            <td class="text-muted nowrap"><?= $r['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-cell">No queries recorded yet</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /two-col -->

<!-- Query Type Breakdown -->
<div class="section">
    <h2>🍩 Query Type Breakdown (24h)</h2>

    <?php if ( ! empty($query_types)): ?>

        <?php
            $type_colors = [
                'SELECT' => 'blue',
                'INSERT' => 'green',
                'UPDATE' => 'yellow',
                'DELETE' => 'red',
                'SHOW'   => 'gray',
            ];
        ?>

        <div class="query-type-grid">
            <?php foreach ($query_types as $qt):
                $color = $type_colors[$qt['query_type']] ?? 'gray';
            ?>
                <div class="card" style="min-width:140px; flex:1;">
                    <div class="label"><?= htmlspecialchars($qt['query_type']) ?></div>
                    <div class="value <?= $color ?>"><?= number_format($qt['count']) ?></div>
                    <div class="sub">
                        avg <?= $qt['avg_ms'] ?>ms &nbsp;|&nbsp; total <?= $qt['total_ms'] ?>ms
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="empty-state">No query data yet</div>
    <?php endif; ?>
</div>

<!-- N+1 Detector (only shown when issues found) -->
<?php if ( ! empty($n1_queries)): ?>
    <div class="section">
        <h2>
            🚨 N+1 Query Detector
            <span class="tag red" style="font-size:.7rem; vertical-align:middle;">
                ISSUES FOUND
            </span>
        </h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SQL</th>
                        <th>Ran</th>
                        <th>Total Time</th>
                        <th>Controller</th>
                        <th>URI</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($n1_queries as $r): ?>
                    <tr>
                        <td class="sql-cell" title="<?= htmlspecialchars($r['query_sql']) ?>">
                            <?= htmlspecialchars(substr($r['query_sql'], 0, 80)) ?>
                        </td>
                        <td><span class="tag red"><?= $r['run_count'] ?>×</span></td>
                        <td><span class="tag yellow"><?= $r['total_ms'] ?>ms</span></td>
                        <td><?= htmlspecialchars($r['controller'] . '/' . $r['method']) ?></td>
                        <td class="text-muted truncate-cell">
                            <?= htmlspecialchars($r['request_uri']) ?>
                        </td>
                        <td class="text-muted nowrap"><?= $r['first_seen'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Most Repeated Queries -->
<div class="section">
    <h2>🔁 Most Repeated Queries (24h)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SQL</th>
                    <th>Runs</th>
                    <th>Total ms</th>
                    <th>Avg ms</th>
                    <th>Max ms</th>
                    <th>Controllers</th>
                </tr>
            </thead>
            <tbody>
            <?php if ( ! empty($grouped_queries)): ?>
                <?php foreach ($grouped_queries as $r): ?>
                    <tr>
                        <td class="sql-cell" title="<?= htmlspecialchars($r['query_sql']) ?>">
                            <?= htmlspecialchars(substr($r['query_sql'], 0, 80)) ?>
                        </td>
                        <td>
                            <span class="tag <?= $r['run_count'] >= 10 ? 'red' : ($r['run_count'] >= 5 ? 'yellow' : 'gray') ?>">
                                <?= $r['run_count'] ?>×
                            </span>
                        </td>
                        <td><?= $r['total_ms'] ?>ms</td>
                        <td>
                            <span class="tag <?= $r['avg_ms'] > 100 ? 'red' : ($r['avg_ms'] > 50 ? 'yellow' : 'green') ?>">
                                <?= $r['avg_ms'] ?>ms
                            </span>
                        </td>
                        <td><?= $r['max_ms'] ?>ms</td>
                        <td class="text-muted" style="font-size:.78rem;">
                            <?= htmlspecialchars($r['controllers']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-cell">No query data yet</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>