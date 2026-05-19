'use strict';

// ============================================================
// Constants (set in dashboard.php via inline script)
// PULSE_REFRESH_URL and PULSE_DETAIL_URL are defined there
// ============================================================

const PAGE_SIZE = 10;

let activeQuickFilter = 'all';
let currentPage       = 1;
let filteredRows      = [];

// ============================================================
// Auto-refresh every 10 seconds
// ============================================================
setInterval(function () {
    fetch(PULSE_REFRESH_URL)
        .then(r => r.json())
        .then(() => {
            document.getElementById('last-updated').textContent =
                new Date().toLocaleTimeString();
            window.location.reload();
        })
        .catch(() => {
            const badge = document.getElementById('status-badge');
            if (badge) {
                badge.textContent = 'OFFLINE';
                badge.classList.add('red');
            }
        });
}, 10000);

// ============================================================
// Quick filter buttons
// ============================================================
function setQuickFilter(btn, filter) {
    activeQuickFilter = filter;
    currentPage       = 1;

    document.querySelectorAll('.qfilter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    applyFilters();
}

// ============================================================
// Core filter + pagination logic
// ============================================================
function applyFilters() {
    const search = document.getElementById('filter-search').value.toLowerCase();
    const method = document.getElementById('filter-method').value;
    const status = document.getElementById('filter-status').value;
    const rows   = Array.from(
        document.querySelectorAll('#recent-requests tr[data-severity]')
    );

    // Hide all rows first
    rows.forEach(r => r.style.display = 'none');

    // Apply filters
    filteredRows = rows.filter(row => {
        const d = row.dataset;

        let matchQuick = true;
        if      (activeQuickFilter === 'critical') matchQuick = d.severity === 'critical';
        else if (activeQuickFilter === 'warning')  matchQuick = d.severity === 'warning';
        else if (activeQuickFilter === 'slow')     matchQuick = d.slow     === 'true';
        else if (activeQuickFilter === 'memory')   matchQuick = d.memory   === 'true';
        else if (activeQuickFilter === 'db')       matchQuick = d.db       === 'true';

        const username = document.getElementById('filter-username')
            ? document.getElementById('filter-username').value.toLowerCase()
            : '';

        const matchSearch   = ! search   || d.uri.includes(search) || d.controller.includes(search);
        const matchMethod   = ! method   || d.method === method;
        const matchStatus   = ! status   || d.status.startsWith(status);
        const matchUsername = ! username || (d.username && d.username.includes(username));

        return matchQuick && matchSearch && matchMethod && matchStatus && matchUsername;
    });

    renderPage(currentPage);
    renderPagination();

    // Update count
    const countEl = document.getElementById('filter-count');
    if (countEl) {
        countEl.textContent = filteredRows.length + ' of ' + rows.length + ' requests';
    }

    // No results message
    const noResults = document.getElementById('no-results');
    if (noResults) {
        noResults.style.display = filteredRows.length === 0 ? 'block' : 'none';
    }
}

// ============================================================
// Render one page of filtered rows
// ============================================================
function renderPage(page) {
    filteredRows.forEach(r => r.style.display = 'none');

    const start = (page - 1) * PAGE_SIZE;
    const end   = start + PAGE_SIZE;

    filteredRows.slice(start, end).forEach(r => r.style.display = '');
}

// ============================================================
// Render pagination controls
// ============================================================
function renderPagination() {
    const totalPages = Math.ceil(filteredRows.length / PAGE_SIZE);
    const container  = document.getElementById('pagination');

    if ( ! container) return;
    container.innerHTML = '';

    if (totalPages <= 1) return;

    // Prev button
    const prev       = document.createElement('button');
    prev.textContent = '← Prev';
    prev.disabled    = currentPage === 1;
    prev.onclick     = () => goToPage(currentPage - 1);
    container.appendChild(prev);

    // Page number buttons
    getPageRange(currentPage, totalPages).forEach(p => {
        if (p === '...') {
            const dots       = document.createElement('span');
            dots.textContent = '...';
            dots.style.color   = '#475569';
            dots.style.padding = '0 4px';
            container.appendChild(dots);
        } else {
            const btn       = document.createElement('button');
            btn.textContent = p;
            if (p === currentPage) btn.classList.add('active');
            btn.onclick = () => goToPage(p);
            container.appendChild(btn);
        }
    });

    // Next button
    const next       = document.createElement('button');
    next.textContent = 'Next →';
    next.disabled    = currentPage === totalPages;
    next.onclick     = () => goToPage(currentPage + 1);
    container.appendChild(next);

    // Page info
    const info         = document.createElement('span');
    info.className     = 'pg-info';
    const start        = ((currentPage - 1) * PAGE_SIZE) + 1;
    const end          = Math.min(currentPage * PAGE_SIZE, filteredRows.length);
    info.textContent   = 'Showing ' + start + '–' + end + ' of ' + filteredRows.length;
    container.appendChild(info);
}

function goToPage(page) {
    currentPage = page;
    renderPage(page);
    renderPagination();
}

// Smart page range with ellipsis
function getPageRange(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages = new Set([1, total, current]);
    for (let i = Math.max(2, current - 2); i <= Math.min(total - 1, current + 2); i++) {
        pages.add(i);
    }

    const sorted = Array.from(pages).sort((a, b) => a - b);
    const result = [];

    sorted.forEach((p, i) => {
        if (i > 0 && p - sorted[i - 1] > 1) result.push('...');
        result.push(p);
    });

    return result;
}

// ============================================================
// Reset all filters
// ============================================================
function resetFilters() {
    document.getElementById('filter-search').value = '';
    document.getElementById('filter-method').value = '';
    document.getElementById('filter-status').value = '';
    const unameEl = document.getElementById('filter-username');
    if (unameEl) unameEl.value = '';

    activeQuickFilter = 'all';
    currentPage       = 1;

    document.querySelectorAll('.qfilter').forEach(b => b.classList.remove('active'));

    const allBtn = document.querySelector('.qfilter[data-filter="all"]');
    if (allBtn) allBtn.classList.add('active');

    applyFilters();
}

// ============================================================
// Export dropdown toggle
// ============================================================
function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    if (menu) {
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    }
}

// Close export menu when clicking outside
document.addEventListener('click', function (e) {
    const wrap = document.getElementById('export-menu-wrap');
    if (wrap && ! wrap.contains(e.target)) {
        const menu = document.getElementById('export-menu');
        if (menu) menu.style.display = 'none';
    }
});

// ============================================================
// Drill-down panel — open
// ============================================================
function openDetail(requestId) {
    if ( ! requestId) return;

    const panel   = document.getElementById('detail-panel');
    const overlay = document.getElementById('detail-overlay');
    const body    = document.getElementById('detail-body');

    if ( ! panel || ! overlay || ! body) return;

    overlay.style.display = 'block';
    panel.style.display   = 'block';
    body.innerHTML        = '<div class="loading-state">Loading...</div>';

    setTimeout(() => panel.style.transform = 'translateX(0)', 10);

    document.getElementById('detail-title').textContent    = 'Request Detail';
    document.getElementById('detail-subtitle').textContent = 'Loading...';

    fetch(PULSE_DETAIL_URL + '/' + requestId)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                body.innerHTML = '<div class="error-state">' + data.error + '</div>';
                return;
            }
            renderDetail(data);
        })
        .catch(() => {
            body.innerHTML = '<div class="error-state">Failed to load detail.</div>';
        });
}

// ============================================================
// Drill-down panel — close
// ============================================================
function closeDetail() {
    const panel   = document.getElementById('detail-panel');
    const overlay = document.getElementById('detail-overlay');

    if ( ! panel || ! overlay) return;

    panel.style.transform = 'translateX(100%)';

    setTimeout(() => {
        panel.style.display   = 'none';
        overlay.style.display = 'none';
    }, 300);
}

// Close on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDetail();
});

// ============================================================
// Drill-down panel — render content
// ============================================================
function renderDetail(data) {
    const r = data.request;
    const s = data.summary;

    // Update header
    document.getElementById('detail-title').textContent =
        r.method + ' ' + (r.uri.length > 50 ? r.uri.substring(0, 50) + '...' : r.uri);
    document.getElementById('detail-subtitle').textContent =
    r.controller + '/' + r.action + ' — ' + r.created_at;

    const unameDetailEl = document.getElementById('detail-username');
    if (unameDetailEl) {
        unameDetailEl.textContent = '👤 ' + (r.username || 'guest');
    }

    // Severity score
    const memMb = s.memory_mb;
    let score   = 0;

    if (r.response_time_ms > 500)      score += 2;
    else if (r.response_time_ms > 200) score += 1;

    if (memMb > 64)      score += 2;
    else if (memMb > 32) score += 1;

    if (s.total_query_ms > 100)     score += 2;
    else if (s.total_query_ms > 50) score += 1;

    const severity      = score >= 4 ? 'CRITICAL' : score >= 2 ? 'WARNING' : 'OK';
    const severityColor = score >= 4 ? '#ef4444'  : score >= 2 ? '#f59e0b' : '#22c55e';

    // DB time bar
    const dbBarWidth = Math.min(100, s.db_percent);
    const dbBarColor = s.db_percent > 70 ? '#ef4444' : s.db_percent > 40 ? '#f59e0b' : '#22c55e';

    // Response color
    const respColor = r.response_time_ms > 500 ? '#ef4444'
                    : r.response_time_ms > 200 ? '#f59e0b'
                    : '#22c55e';

    // Memory color
    const memColor = memMb > 64 ? '#ef4444' : memMb > 32 ? '#f59e0b' : '#22c55e';

    let html = `
        <!-- Summary strip -->
        <div class="detail-summary-grid">
            <div class="detail-summary-card">
                <div class="detail-summary-label">Response</div>
                <div class="detail-summary-value" style="color:${respColor}">
                    ${r.response_time_ms}ms
                </div>
            </div>
            <div class="detail-summary-card">
                <div class="detail-summary-label">Memory</div>
                <div class="detail-summary-value" style="color:${memColor}">
                    ${memMb}MB
                </div>
            </div>
            <div class="detail-summary-card">
                <div class="detail-summary-label">Severity</div>
                <div class="detail-summary-value" style="color:${severityColor}">
                    ${severity}
                </div>
            </div>
        </div>

        <!-- DB time vs response time bar -->
        <div class="detail-db-bar-wrap">
            <div class="detail-db-bar-header">
                <span class="detail-db-bar-label">DB Time vs Response Time</span>
                <span class="detail-db-bar-stats">
                    <span style="color:${dbBarColor}; font-weight:700;">${s.total_query_ms}ms</span>
                    of ${r.response_time_ms}ms
                    (<span style="color:${dbBarColor}">${s.db_percent}%</span> in DB)
                </span>
            </div>
            <div class="detail-db-bar-track">
                <div class="detail-db-bar-fill"
                    style="width:${dbBarWidth}%; background:${dbBarColor};">
                </div>
            </div>
            <div class="detail-db-bar-footer">
                <span>${s.total_queries} queries</span>
                <span>${(100 - s.db_percent).toFixed(1)}% in PHP/framework</span>
            </div>
        </div>
    `;

    // Query type breakdown
    if (data.query_types.length > 0) {
        const typeColors = {
            SELECT: '#38bdf8',
            INSERT: '#22c55e',
            UPDATE: '#f59e0b',
            DELETE: '#ef4444',
        };

        html += '<div class="detail-query-types">';
        data.query_types.forEach(qt => {
            const c = typeColors[qt.query_type] || '#94a3b8';
            html += `
                <div class="detail-query-type-card">
                    <div class="detail-query-type-name">${qt.query_type}</div>
                    <div class="detail-query-type-count" style="color:${c}">${qt.count}</div>
                    <div class="detail-query-type-ms">${qt.total_ms}ms</div>
                </div>
            `;
        });
        html += '</div>';
    }

    // Duplicate queries / N+1 warning
    if (data.duplicates.length > 0) {
        html += `
            <div class="detail-n1-wrap">
                <div class="detail-n1-title">
                    N+1 Detected —
                    ${data.duplicates.length} duplicate quer${data.duplicates.length > 1 ? 'ies' : 'y'}
                </div>
        `;

        data.duplicates.forEach(d => {
            html += `
                <div class="detail-n1-item">
                    <div class="detail-n1-sql">
                        ${escHtml(d.query_sql.substring(0, 120))}
                        ${d.query_sql.length > 120 ? '...' : ''}
                    </div>
                    <div class="detail-n1-stats">
                        <span style="color:#ef4444;">ran ${d.run_count}×</span>
                        <span style="color:#f59e0b;">total ${d.total_ms}ms</span>
                        <span style="color:#94a3b8;">avg ${d.avg_ms}ms</span>
                    </div>
                </div>
            `;
        });

        html += '</div>';
    }

    // Query timeline
    html += `
        <div class="detail-timeline-title">
            Query Timeline (${s.total_queries} queries)
        </div>
        <div class="detail-timeline">
    `;

    if (data.queries.length === 0) {
        html += '<div class="empty-state">No queries recorded for this request</div>';
    } else {
        const maxMs = Math.max(...data.queries.map(q => q.execution_ms));

        data.queries.forEach((q, i) => {
            const ms    = q.execution_ms;
            const color = ms > 100 ? '#ef4444' : ms > 50 ? '#f59e0b' : '#22c55e';
            const barW  = Math.min(100, Math.max(2, (ms / Math.max(maxMs, 1)) * 100));

            html += `
                <div class="detail-query-item">
                    <div class="detail-query-meta">
                        <span class="detail-query-num">#${i + 1}</span>
                        <span class="detail-query-ms" style="color:${color}">${ms}ms</span>
                    </div>
                    <div class="detail-query-bar-track">
                        <div class="detail-query-bar-fill"
                            style="width:${barW}%; background:${color};">
                        </div>
                    </div>
                    <div class="detail-query-sql">
                        ${escHtml(q.query_sql.substring(0, 200))}
                        ${q.query_sql.length > 200 ? '...' : ''}
                    </div>
                </div>
            `;
        });
    }

    html += '</div>'; // /detail-timeline

    document.getElementById('detail-body').innerHTML = html;
}

// ============================================================
// Utility — escape HTML
// ============================================================
function escHtml(str) {
    return str
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;');
}

// ============================================================
// Init on page load
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    applyFilters();
});