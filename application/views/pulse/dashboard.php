<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/pulse/pulse.css') ?>">
</head>
<body>

    <!-- Top Bar -->
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
                Auto-refresh 10s &nbsp;|&nbsp;
                <span id="last-updated">just now</span>
            </span>

            <!-- Export Dropdown -->
            <div class="dropdown-wrap" id="export-menu-wrap">
                <button class="btn-outline-blue" onclick="toggleExportMenu()">
                    📤 Export ▾
                </button>
                <div class="dropdown-menu" id="export-menu">
                    <a href="<?= site_url('pulse/export/all') ?>"        class="dropdown-item">📦 Export All (CSV)</a>
                    <a href="<?= site_url('pulse/export/requests') ?>"   class="dropdown-item">📋 Requests Only</a>
                    <a href="<?= site_url('pulse/export/queries') ?>"    class="dropdown-item">🔍 Queries Only</a>
                    <a href="<?= site_url('pulse/export/performance') ?>" class="dropdown-item">⚡ Performance Only</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">

        <?php $this->load->view('pulse/_partials/summary_cards',    $data ?? get_defined_vars()); ?>
        <?php $this->load->view('pulse/_partials/trend_chart',      $data ?? get_defined_vars()); ?>
        <?php $this->load->view('pulse/_partials/query_analysis',   $data ?? get_defined_vars()); ?>
        <?php $this->load->view('pulse/_partials/recent_requests',  $data ?? get_defined_vars()); ?>
        <?php $this->load->view('pulse/_partials/detail_panel',     $data ?? get_defined_vars()); ?>

    </div>

    <script>
        // Pass PHP data to JS
        const PULSE_REFRESH_URL = '<?= site_url('pulse/refresh') ?>';
        const PULSE_DETAIL_URL  = '<?= site_url('pulse/request_detail') ?>';
    </script>
    <script src="<?= base_url('assets/pulse/pulse.js') ?>"></script>

</body>
</html>