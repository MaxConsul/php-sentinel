<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!-- ============================================================
     Drill-down Side Panel
     - Slides in from the right when a request row is clicked
     - Shows full request detail, query timeline, N+1 warnings
     ============================================================ -->

<!-- Overlay (click to close) -->
<div
    id="detail-overlay"
    onclick="closeDetail()"
    style="display:none; position:fixed; inset:0;
           background:#00000088; z-index:200;">
</div>

<!-- Side Panel -->
<div id="detail-panel" class="detail-panel">

    <!-- Panel Header -->
    <div class="detail-header">
        <div class="detail-header-text">
            <div class="detail-title" id="detail-title">Request Detail</div>
            <div class="detail-subtitle" id="detail-subtitle"></div>
            <div class="detail-username" id="detail-username"></div>
        </div>
        <button onclick="closeDetail()" class="detail-close">✕ Close</button>
    </div>

    <!-- Panel Body -->
    <div class="detail-body" id="detail-body">
        <div class="loading-state">Loading...</div>
    </div>

</div>