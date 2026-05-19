<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/pulse/pulse.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/pulse/server.css') ?>">
</head>
<body>

    <!-- ========================================================
         Top Bar
         ======================================================== -->
    <div class="topbar">
        <div class="topbar-brand">
            <h1>
                ⚡ PHP Sentinel
                <span class="badge" id="status-badge">LIVE</span>
            </h1>
        </div>
        <div class="topbar-actions">
            <span class="last-updated">
                <span class="live-dot"></span>
                Auto-refresh 5s &nbsp;|&nbsp;
                <span id="last-updated">just now</span>
            </span>
        </div>
    </div>

    <!-- ========================================================
         Navigation Tabs
         ======================================================== -->
    <div class="pulse-tabs">
        <a href="<?= site_url('pulse') ?>"        class="pulse-tab">📊 App Metrics</a>
        <a href="<?= site_url('pulse/server') ?>" class="pulse-tab active">🖥️ Server Health</a>
    </div>

    <?php $m = $metrics; ?>

    <div class="container">

        <!-- ====================================================
             Overview Cards
             ==================================================== -->
        <div class="cards">

            <!-- CPU -->
            <?php
                $cpu_color = $m['cpu']['usage'] > 80 ? 'red'
                           : ($m['cpu']['usage'] > 50 ? 'yellow' : 'green');
            ?>
            <div class="card">
                <div class="label">CPU Usage</div>
                <div class="value <?= $cpu_color ?>" id="card-cpu-value">
                    <?= $m['cpu']['usage'] ?>%
                </div>
                <div class="sub" id="card-cpu-sub">
                    <?= $m['cpu']['cores'] ?> cores — <?= htmlspecialchars($m['cpu']['model']) ?>
                </div>
            </div>

            <!-- Memory -->
            <?php
                $mem_color = $m['memory']['usage_pct'] > 85 ? 'red'
                           : ($m['memory']['usage_pct'] > 65 ? 'yellow' : 'green');
            ?>
            <div class="card">
                <div class="label">Memory Usage</div>
                <div class="value <?= $mem_color ?>" id="card-mem-value">
                    <?= $m['memory']['usage_pct'] ?>%
                </div>
                <div class="sub" id="card-mem-sub">
                    <?= $m['memory']['used'] ?>MB / <?= $m['memory']['total'] ?>MB
                </div>
            </div>

            <!-- Disk -->
            <?php
                $disk      = isset($m['disk'][0]) ? $m['disk'][0] : ['pct' => 0, 'used' => 'N/A', 'total' => 'N/A'];
                $disk_color = $disk['pct'] > 85 ? 'red'
                            : ($disk['pct'] > 65 ? 'yellow' : 'green');
            ?>
            <div class="card">
                <div class="label">Disk Usage (/)</div>
                <div class="value <?= $disk_color ?>" id="card-disk-value">
                    <?= $disk['pct'] ?>%
                </div>
                <div class="sub" id="card-disk-sub">
                    <?= $disk['used'] ?> / <?= $disk['total'] ?>
                </div>
            </div>

            <!-- Load Average -->
            <?php
                $load_color = $m['load']['1min_pct'] > 80 ? 'red'
                            : ($m['load']['1min_pct'] > 50 ? 'yellow' : 'green');
            ?>
            <div class="card">
                <div class="label">Load Average</div>
                <div class="value <?= $load_color ?>" id="card-load-value">
                    <?= $m['load']['1min'] ?>
                </div>
                <div class="sub" id="card-load-sub">
                    5m: <?= $m['load']['5min'] ?> &nbsp;|&nbsp; 15m: <?= $m['load']['15min'] ?>
                </div>
            </div>

            <!-- Uptime -->
            <div class="card">
                <div class="label">Server Uptime</div>
                <div class="value green" id="card-uptime-value">
                    <?= $m['uptime']['human'] ?>
                </div>
                <div class="sub">since last restart</div>
            </div>

            <!-- Apache -->
            <?php
                $apache_color = $m['apache']['status'] === 'running' ? 'green' : 'red';
            ?>
            <div class="card">
                <div class="label">Apache</div>
                <div class="value <?= $apache_color ?>" id="card-apache-value">
                    <?= strtoupper($m['apache']['status']) ?>
                </div>
                <div class="sub" id="card-apache-sub">
                    <?= $m['apache']['processes'] ?> processes
                    &nbsp;|&nbsp; v<?= $m['apache']['version'] ?>
                </div>
            </div>

        </div><!-- /cards -->

        <!-- ====================================================
             CPU Detail + Memory Detail
             ==================================================== -->
        <div class="two-col">

            <!-- CPU Detail -->
            <div class="section">
                <h2>🖥️ CPU Detail</h2>
                <div class="server-card">

                    <!-- Overall Usage -->
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>Overall Usage</span>
                            <span class="metric-value <?= $cpu_color ?>" id="cpu-usage">
                                <?= $m['cpu']['usage'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="cpu-usage-track">
                            <div class="progress-fill <?= $cpu_color ?>"
                                style="width:<?= $m['cpu']['usage'] ?>%"></div>
                        </div>
                    </div>

                    <!-- User -->
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>User</span>
                            <span class="metric-value blue" id="cpu-user">
                                <?= $m['cpu']['user'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="cpu-user-track">
                            <div class="progress-fill blue"
                                style="width:<?= $m['cpu']['user'] ?>%"></div>
                        </div>
                    </div>

                    <!-- System -->
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>System</span>
                            <span class="metric-value yellow" id="cpu-system">
                                <?= $m['cpu']['system'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="cpu-system-track">
                            <div class="progress-fill yellow"
                                style="width:<?= $m['cpu']['system'] ?>%"></div>
                        </div>
                    </div>

                    <!-- I/O Wait -->
                    <?php $iowait_color = $m['cpu']['iowait'] > 20 ? 'red' : 'gray'; ?>
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>I/O Wait</span>
                            <span class="metric-value <?= $iowait_color ?>" id="cpu-iowait">
                                <?= $m['cpu']['iowait'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="cpu-iowait-track">
                            <div class="progress-fill <?= $iowait_color ?>"
                                style="width:<?= $m['cpu']['iowait'] ?>%"></div>
                        </div>
                    </div>

                    <!-- Idle -->
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>Idle</span>
                            <span class="metric-value green" id="cpu-idle">
                                <?= $m['cpu']['idle'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="cpu-idle-track">
                            <div class="progress-fill green"
                                style="width:<?= $m['cpu']['idle'] ?>%"></div>
                        </div>
                    </div>

                    <!-- Info rows -->
                    <div class="server-info-row">
                        <span class="server-info-label">Model</span>
                        <span class="server-info-value" id="cpu-model">
                            <?= htmlspecialchars($m['cpu']['model']) ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Cores</span>
                        <span class="server-info-value" id="cpu-cores">
                            <?= $m['cpu']['cores'] ?>
                        </span>
                    </div>

                </div>
            </div><!-- /CPU Detail -->

            <!-- Memory Detail -->
            <div class="section">
                <h2>🧠 Memory Detail</h2>
                <div class="server-card">

                    <!-- RAM Usage Bar -->
                    <div class="metric-row">
                        <div class="metric-label">
                            <span>RAM Usage</span>
                            <span class="metric-value <?= $mem_color ?>" id="mem-usage">
                                <?= $m['memory']['usage_pct'] ?>%
                            </span>
                        </div>
                        <div class="progress-track" id="mem-usage-track">
                            <div class="progress-fill <?= $mem_color ?>"
                                style="width:<?= $m['memory']['usage_pct'] ?>%"></div>
                        </div>
                    </div>

                    <div class="server-info-row">
                        <span class="server-info-label">Total</span>
                        <span class="server-info-value" id="mem-total">
                            <?= $m['memory']['total'] ?> MB
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Used</span>
                        <span class="server-info-value <?= $mem_color ?>" id="mem-used">
                            <?= $m['memory']['used'] ?> MB
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Available</span>
                        <span class="server-info-value green" id="mem-available">
                            <?= $m['memory']['available'] ?> MB
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Cached</span>
                        <span class="server-info-value" id="mem-cached">
                            <?= $m['memory']['cached'] ?> MB
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Buffers</span>
                        <span class="server-info-value" id="mem-buffers">
                            <?= $m['memory']['buffers'] ?> MB
                        </span>
                    </div>

                    <!-- Swap -->
                    <?php if ($m['memory']['swap_total'] > 0): ?>
                        <?php $swap_color = $m['memory']['swap_pct'] > 50 ? 'red' : 'gray'; ?>
                        <div class="metric-row" style="margin-top:12px;">
                            <div class="metric-label">
                                <span>Swap Usage</span>
                                <span class="metric-value <?= $swap_color ?>" id="mem-swap-pct">
                                    <?= $m['memory']['swap_pct'] ?>%
                                </span>
                            </div>
                            <div class="progress-track" id="mem-swap-usage-track">
                                <div class="progress-fill <?= $swap_color ?>"
                                    style="width:<?= $m['memory']['swap_pct'] ?>%"></div>
                            </div>
                        </div>
                        <div class="server-info-row">
                            <span class="server-info-label">Swap Total</span>
                            <span class="server-info-value" id="mem-swap-total">
                                <?= $m['memory']['swap_total'] ?> MB
                            </span>
                        </div>
                        <div class="server-info-row">
                            <span class="server-info-label">Swap Used</span>
                            <span class="server-info-value" id="mem-swap-used">
                                <?= $m['memory']['swap_used'] ?> MB
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="server-info-row">
                            <span class="server-info-label">Swap</span>
                            <span class="server-info-value gray">Not configured</span>
                        </div>
                    <?php endif; ?>

                </div>
            </div><!-- /Memory Detail -->

        </div><!-- /two-col -->

        <!-- ====================================================
             Disk Usage + Network I/O
             ==================================================== -->
        <div class="two-col">

            <!-- Disk Usage -->
            <div class="section">
                <h2>💾 Disk Usage</h2>
                <div class="server-card">
                    <?php foreach ($m['disk'] as $i => $disk):
                        $d_color = $disk['pct'] > 85 ? 'red'
                                 : ($disk['pct'] > 65 ? 'yellow' : 'green');
                    ?>
                        <div class="metric-row">
                            <div class="metric-label">
                                <span class="mono"><?= htmlspecialchars($disk['mount']) ?></span>
                                <span class="metric-value <?= $d_color ?>" id="disk-pct-<?= $i ?>">
                                    <?= $disk['pct'] ?>%
                                </span>
                            </div>
                            <div class="progress-track" id="disk-track-<?= $i ?>">
                                <div class="progress-fill <?= $d_color ?>"
                                    style="width:<?= min(100, $disk['pct']) ?>%"></div>
                            </div>
                            <div class="disk-detail">
                                <span id="disk-used-<?= $i ?>"><?= $disk['used'] ?> used</span>
                                <span id="disk-free-<?= $i ?>"><?= $disk['free'] ?> free</span>
                                <span id="disk-total-<?= $i ?>"><?= $disk['total'] ?> total</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div><!-- /Disk -->

            <!-- Network I/O -->
            <div class="section">
                <h2>🌐 Network I/O</h2>
                <div class="server-card">
                    <?php if ( ! empty($m['network'])): ?>
                        <?php foreach ($m['network'] as $i => $net): ?>
                            <div class="server-info-row">
                                <span class="server-info-label mono">
                                    <?= htmlspecialchars($net['interface']) ?>
                                </span>
                            </div>
                            <div class="network-row">
                                <div class="network-stat">
                                    <span class="network-label">↓ IN</span>
                                    <span class="network-value green" id="net-rx-<?= $i ?>">
                                        <?= $net['rx_human'] ?>
                                    </span>
                                </div>
                                <div class="network-stat">
                                    <span class="network-label">↑ OUT</span>
                                    <span class="network-value blue" id="net-tx-<?= $i ?>">
                                        <?= $net['tx_human'] ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">No network data available</div>
                    <?php endif; ?>
                </div>
            </div><!-- /Network -->

        </div><!-- /two-col -->

        <!-- ====================================================
             PHP Info + MySQL Status
             ==================================================== -->
        <div class="two-col">

            <!-- PHP Info -->
            <div class="section">
                <h2>🐘 PHP Info</h2>
                <div class="server-card">
                    <div class="server-info-row">
                        <span class="server-info-label">Version</span>
                        <span class="server-info-value green"><?= $m['php']['version'] ?></span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Memory Limit</span>
                        <span class="server-info-value"><?= $m['php']['memory_limit'] ?></span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Max Execution</span>
                        <span class="server-info-value"><?= $m['php']['max_execution'] ?></span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Upload Max</span>
                        <span class="server-info-value"><?= $m['php']['upload_max'] ?></span>
                    </div>
                    <div class="server-info-row" style="align-items:flex-start;">
                        <span class="server-info-label">Extensions</span>
                        <span class="server-info-value" style="font-size:.72rem; color:#64748b;">
                            <?= htmlspecialchars($m['php']['extensions']) ?>...
                        </span>
                    </div>
                </div>
            </div><!-- /PHP Info -->

            <!-- MySQL Status -->
            <div class="section">
                <h2>🗄️ MySQL Status</h2>
                <div class="server-card">
                    <div class="server-info-row">
                        <span class="server-info-label">Version</span>
                        <span class="server-info-value green" id="mysql-version">
                            <?= $m['mysql']['version'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Uptime</span>
                        <span class="server-info-value" id="mysql-uptime">
                            <?= $m['mysql']['uptime'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Connections</span>
                        <span class="server-info-value <?= (int)$m['mysql']['connections'] > 50 ? 'red' : 'green' ?>"
                            id="mysql-connections">
                            <?= $m['mysql']['connections'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Max Used</span>
                        <span class="server-info-value" id="mysql-max-conn">
                            <?= $m['mysql']['max_connections'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Total Queries</span>
                        <span class="server-info-value" id="mysql-queries">
                            <?= $m['mysql']['queries'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Slow Queries</span>
                        <span class="server-info-value <?= (int)$m['mysql']['slow_queries'] > 0 ? 'red' : 'green' ?>"
                            id="mysql-slow">
                            <?= $m['mysql']['slow_queries'] ?>
                        </span>
                    </div>
                    <div class="server-info-row">
                        <span class="server-info-label">Queries/sec</span>
                        <span class="server-info-value" id="mysql-qps">
                            <?= $m['mysql']['questions_ps'] ?>
                        </span>
                    </div>
                </div>
            </div><!-- /MySQL -->

        </div><!-- /two-col -->

        <!-- ====================================================
             Load Average Detail
             ==================================================== -->
        <div class="section">
            <h2>📊 Load Average Detail</h2>
            <div class="server-card">
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">

                    <?php
                        $load_items = [
                            '1min'  => ['label' => '1 Minute',   'val' => $m['load']['1min'],  'pct' => $m['load']['1min_pct']],
                            '5min'  => ['label' => '5 Minutes',  'val' => $m['load']['5min'],  'pct' => $m['load']['5min_pct']],
                            '15min' => ['label' => '15 Minutes', 'val' => $m['load']['15min'], 'pct' => $m['load']['15min_pct']],
                        ];
                    ?>

                    <?php foreach ($load_items as $key => $item):
                        $l_color = $item['pct'] > 80 ? 'red'
                                 : ($item['pct'] > 50 ? 'yellow' : 'green');
                    ?>
                        <div style="text-align:center;">
                            <div class="label"><?= $item['label'] ?></div>
                            <div class="value <?= $l_color ?>"
                                id="load-<?= $key ?>"
                                style="font-size:2rem;">
                                <?= $item['val'] ?>
                            </div>
                            <div class="sub" id="load-<?= $key ?>-pct">
                                <?= $item['pct'] ?>% per core
                            </div>
                            <div class="progress-track"
                                id="load-<?= $key ?>-track"
                                style="margin-top:8px;">
                                <div class="progress-fill <?= $l_color ?>"
                                    style="width:<?= min(100, $item['pct']) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="server-info-row" style="margin-top:16px;">
                    <span class="server-info-label">CPU Cores</span>
                    <span class="server-info-value"><?= $m['load']['cores'] ?></span>
                </div>
                <div style="font-size:.75rem; color:#475569; margin-top:8px;" id="load-cores-hint">
                    💡 Load average above <?= $m['load']['cores'] ?>.0
                    (1 per core) indicates the server is overloaded.
                </div>

            </div>
        </div><!-- /Load Average -->

        <!-- ====================================================
             Top Processes
             ==================================================== -->
        <div class="two-col">

            <!-- Top by CPU -->
            <div class="section">
                <h2>⚡ Top Processes by CPU</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>PID</th>
                                <th>CPU%</th>
                                <th>Mem%</th>
                                <th>Command</th>
                            </tr>
                        </thead>
                        <tbody id="proc-cpu-tbody">
                        <?php if ( ! empty($m['processes']['by_cpu'])): ?>
                            <?php foreach ($m['processes']['by_cpu'] as $p): ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars($p['user']) ?></td>
                                    <td class="text-muted"><?= $p['pid'] ?></td>
                                    <td>
                                        <span class="tag <?= $p['cpu'] > 50 ? 'red' : ($p['cpu'] > 20 ? 'yellow' : 'green') ?>">
                                            <?= $p['cpu'] ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="tag <?= $p['mem'] > 50 ? 'red' : ($p['mem'] > 20 ? 'yellow' : 'gray') ?>">
                                            <?= $p['mem'] ?>%
                                        </span>
                                    </td>
                                    <td class="mono" style="font-size:.75rem;">
                                        <?= htmlspecialchars($p['command']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-cell">No data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /Top CPU -->

            <!-- Top by Memory -->
            <div class="section">
                <h2>🧠 Top Processes by Memory</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>PID</th>
                                <th>CPU%</th>
                                <th>Mem%</th>
                                <th>Command</th>
                            </tr>
                        </thead>
                        <tbody id="proc-mem-tbody">
                        <?php if ( ! empty($m['processes']['by_mem'])): ?>
                            <?php foreach ($m['processes']['by_mem'] as $p): ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars($p['user']) ?></td>
                                    <td class="text-muted"><?= $p['pid'] ?></td>
                                    <td>
                                        <span class="tag <?= $p['cpu'] > 50 ? 'red' : ($p['cpu'] > 20 ? 'yellow' : 'green') ?>">
                                            <?= $p['cpu'] ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="tag <?= $p['mem'] > 50 ? 'red' : ($p['mem'] > 20 ? 'yellow' : 'gray') ?>">
                                            <?= $p['mem'] ?>%
                                        </span>
                                    </td>
                                    <td class="mono" style="font-size:.75rem;">
                                        <?= htmlspecialchars($p['command']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty-cell">No data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /Top Memory -->

        </div><!-- /two-col -->

        <!-- Last updated footer -->
        <div style="text-align:center; padding:20px; font-size:.75rem; color:#334155;">
            Last updated: <span id="last-updated-bottom"><?= $metrics['timestamp'] ?></span>
        </div>

    </div><!-- /container -->

    <script>
        const SERVER_REFRESH_URL = '<?= site_url('pulse/server/refresh') ?>';
    </script>
    <script src="<?= base_url('assets/pulse/server.js') ?>"></script>

</body>
</html>