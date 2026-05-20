<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ============================================================
     Response Time Trend Chart — SVG line chart, last 60 minutes
     ============================================================ -->
<div class="section">
    <h2>📈 Response Time Trend — Last 60 Minutes</h2>

    <?php
        $trend   = $response_trend;
        $max_ms  = max(1, max(array_column($trend, 'avg_ms')    ?: [1]));
        $max_req = max(1, max(array_column($trend, 'req_count') ?: [1]));
        $points  = count($trend);
    ?>

    <?php if (empty($trend)): ?>

        <div class="empty-state">
            No data yet — load a few pages first.
        </div>

    <?php else: ?>

        <div class="chart-wrap">

            <!-- Legend -->
            <div class="chart-legend">
                <span style="color:#38bdf8;">— Avg Response (ms)</span>
                <span style="color:#ef444466;">— Max Response (ms)</span>
                <span style="color:#f59e0b; opacity:.6;">▬ Warning (200ms)</span>
                <span style="color:#1e40af; opacity:.6;">▮ Request Volume</span>
            </div>

            <!-- SVG Chart -->
            <?php
                $W        = 800;
                $H        = 180;
                $pad_l    = 10;
                $pad_r    = 10;
                $chart_w  = $W - $pad_l - $pad_r;
                $chart_h  = $H - 20;

                // Warning line Y position
                $warn_ms = 200;
                $warn_y  = $chart_h - ($warn_ms / max($max_ms, $warn_ms + 50)) * $chart_h;
                $warn_y  = max(5, $warn_y);

                // Build coordinate arrays
                $avg_coords = [];
                $max_coords = [];

                foreach ($trend as $i => $r) {
                    $x            = $pad_l + ($points > 1 ? ($i / ($points - 1)) * $chart_w : $chart_w / 2);
                    $avg_y        = $chart_h - ($r['avg_ms'] / max($max_ms, 1)) * $chart_h;
                    $max_y        = $chart_h - ($r['max_ms'] / max($max_ms, 1)) * $chart_h;
                    $avg_coords[] = round($x, 1) . ',' . round(max(5, $avg_y), 1);
                    $max_coords[] = round($x, 1) . ',' . round(max(5, $max_y), 1);
                }

                $avg_poly  = implode(' ', $avg_coords);
                $max_poly  = implode(' ', $max_coords);
                $first_x   = $pad_l;
                $last_x    = $pad_l + $chart_w;
                $area_path = 'M ' . $avg_coords[0]
                           . ' L ' . implode(' L ', $avg_coords)
                           . " L {$last_x},{$chart_h} L {$first_x},{$chart_h} Z";
            ?>

            <svg id="trend-chart"
                viewBox="0 0 <?= $W ?> <?= $H ?>"
                xmlns="http://www.w3.org/2000/svg"
                style="width:100%; height:auto; overflow:visible;"
                preserveAspectRatio="none">

                <!-- Volume bars (background) -->
                <?php foreach ($trend as $i => $r):
                    $x     = $pad_l + ($points > 1 ? ($i / ($points - 1)) * $chart_w : $chart_w / 2);
                    $bar_h = ($r['req_count'] / $max_req) * ($chart_h * 0.4);
                    $bar_w = max(2, ($chart_w / $points) * 0.6);
                    $bar_x = $x - ($bar_w / 2);
                    $bar_y = $chart_h - $bar_h;
                ?>
                    <rect
                        x="<?= round($bar_x, 1) ?>"
                        y="<?= round($bar_y, 1) ?>"
                        width="<?= round($bar_w, 1) ?>"
                        height="<?= round($bar_h, 1) ?>"
                        fill="#1e40af44"
                        rx="2">
                        <title><?= $r['minute'] ?> — <?= $r['req_count'] ?> requests</title>
                    </rect>
                <?php endforeach; ?>

                <!-- Warning threshold line -->
                <line
                    x1="<?= $pad_l ?>" y1="<?= round($warn_y, 1) ?>"
                    x2="<?= $W - $pad_r ?>" y2="<?= round($warn_y, 1) ?>"
                    stroke="#f59e0b" stroke-width="1"
                    stroke-dasharray="4,4" opacity="0.5"/>
                <text
                    x="<?= $W - $pad_r - 2 ?>"
                    y="<?= round($warn_y - 3, 1) ?>"
                    font-size="8" fill="#f59e0b"
                    text-anchor="end" opacity="0.7">
                    <?= $warn_ms ?>ms
                </text>

                <!-- Area fill under avg line -->
                <path d="<?= $area_path ?>" fill="#38bdf822"/>

                <!-- Max line (subtle) -->
                <polyline
                    points="<?= $max_poly ?>"
                    fill="none" stroke="#ef444444"
                    stroke-width="1.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"/>

                <!-- Avg line -->
                <polyline
                    points="<?= $avg_poly ?>"
                    fill="none" stroke="#38bdf8"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"/>

                <!-- Data points + tooltips -->
                <?php foreach ($trend as $i => $r):
                    $x         = $pad_l + ($points > 1 ? ($i / ($points - 1)) * $chart_w : $chart_w / 2);
                    $avg_y     = max(5, $chart_h - ($r['avg_ms'] / max($max_ms, 1)) * $chart_h);
                    $dot_color = $r['avg_ms'] > 500 ? '#ef4444' : ($r['avg_ms'] > 200 ? '#f59e0b' : '#38bdf8');
                ?>
                    <circle
                        cx="<?= round($x, 1) ?>"
                        cy="<?= round($avg_y, 1) ?>"
                        r="3.5"
                        fill="<?= $dot_color ?>"
                        stroke="#0f172a"
                        stroke-width="1.5">
                        <title>
                            <?= $r['minute'] ?> |
                            avg: <?= $r['avg_ms'] ?>ms |
                            max: <?= $r['max_ms'] ?>ms |
                            <?= $r['req_count'] ?> reqs
                        </title>
                    </circle>
                <?php endforeach; ?>

                <!-- X axis labels (max 8 labels) -->
                <?php foreach ($trend as $i => $r):
                    if ($i % max(1, intval($points / 8)) !== 0 && $i !== $points - 1) continue;
                    $x = $pad_l + ($points > 1 ? ($i / ($points - 1)) * $chart_w : $chart_w / 2);
                ?>
                    <text
                        x="<?= round($x, 1) ?>"
                        y="<?= $H ?>"
                        font-size="8" fill="#475569"
                        text-anchor="middle">
                        <?= $r['minute'] ?>
                    </text>
                <?php endforeach; ?>

            </svg>

            <!-- Y axis labels -->
            <div class="chart-y-axis">
                <span><?= round($max_ms) ?>ms</span>
                <span><?= round($max_ms / 2) ?>ms</span>
                <span>0ms</span>
            </div>

            <!-- Stats strip -->
            <?php
                $all_avg = array_column($trend, 'avg_ms');
                $all_max = array_column($trend, 'max_ms');
                $all_req = array_column($trend, 'req_count');
            ?>
            <div class="chart-stats">
                <div class="chart-stat">
                    <span class="chart-stat-label">Avg</span>
                    <span class="chart-stat-value blue">
                        <?= format_ms(round(array_sum($all_avg) / count($all_avg), 1)) ?>
                    </span>
                </div>
                <div class="chart-stat">
                    <span class="chart-stat-label">Peak</span>
                    <span class="chart-stat-value red"><?= format_ms(max($all_max)) ?></span>
                </div>
                <div class="chart-stat">
                    <span class="chart-stat-label">Best</span>
                    <span class="chart-stat-value green"><?= format_ms(min($all_avg)) ?></span>
                </div>
                <div class="chart-stat">
                    <span class="chart-stat-label">Total Requests</span>
                    <span class="chart-stat-value gray"><?= array_sum($all_req) ?></span>
                </div>
                <div class="chart-stat">
                    <span class="chart-stat-label">Data Points</span>
                    <span class="chart-stat-value gray"><?= $points ?> min</span>
                </div>
            </div>

        </div><!-- /chart-wrap -->

    <?php endif; ?>
</div>