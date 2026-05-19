'use strict';

// ============================================================
// Auto-refresh every 5 seconds via AJAX
// Updates all metrics without full page reload
// ============================================================

const REFRESH_INTERVAL = 5000;

let refreshTimer = null;

// ============================================================
// Start auto-refresh
// ============================================================
function startRefresh() {
    refreshTimer = setInterval(fetchMetrics, REFRESH_INTERVAL);
}

// ============================================================
// Fetch fresh metrics from server
// ============================================================
function fetchMetrics() {
    fetch(SERVER_REFRESH_URL)
        .then(r => r.json())
        .then(data => {
            updateDashboard(data);
            updateTimestamp();
            setBadgeOnline();
        })
        .catch(() => {
            setBadgeOffline();
        });
}

// ============================================================
// Update all sections with fresh data
// ============================================================
function updateDashboard(m) {
    updateOverviewCards(m);
    updateCpuDetail(m.cpu);
    updateMemoryDetail(m.memory);
    updateDiskDetail(m.disk);
    updateNetworkDetail(m.network);
    updateLoadDetail(m.load);
    updateMysqlDetail(m.mysql);
    updateApacheDetail(m.apache);
    updateProcesses(m.processes);
}

// ============================================================
// Helper — get color class based on percentage
// ============================================================
function colorFromPct(pct, warnAt, critAt) {
    if (pct >= critAt)  return 'red';
    if (pct >= warnAt)  return 'yellow';
    return 'green';
}

// ============================================================
// Helper — set element text safely
// ============================================================
function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

// ============================================================
// Helper — set progress bar width + color
// ============================================================
function setProgress(trackId, pct, colorClass) {
    const track = document.getElementById(trackId);
    if ( ! track) return;

    const fill = track.querySelector('.progress-fill');
    if ( ! fill) return;

    fill.style.width = Math.min(100, Math.max(0, pct)) + '%';

    // Update color class
    fill.classList.remove('green', 'yellow', 'red', 'blue', 'gray');
    fill.classList.add(colorClass);
}

// ============================================================
// Helper — set value color class
// ============================================================
function setValueColor(id, colorClass) {
    const el = document.getElementById(id);
    if ( ! el) return;
    el.classList.remove('green', 'yellow', 'red', 'blue', 'gray');
    el.classList.add(colorClass);
}

// ============================================================
// Update Overview Cards
// ============================================================
function updateOverviewCards(m) {
    // CPU
    const cpuColor = colorFromPct(m.cpu.usage, 50, 80);
    setText('card-cpu-value',  m.cpu.usage + '%');
    setText('card-cpu-sub',    m.cpu.cores + ' cores — ' + m.cpu.model);
    setValueColor('card-cpu-value', cpuColor);

    // Memory
    const memColor = colorFromPct(m.memory.usage_pct, 65, 85);
    setText('card-mem-value', m.memory.usage_pct + '%');
    setText('card-mem-sub',   m.memory.used + 'MB / ' + m.memory.total + 'MB');
    setValueColor('card-mem-value', memColor);

    // Disk
    const disk      = m.disk[0] || { pct: 0, used: 'N/A', total: 'N/A' };
    const diskColor = colorFromPct(disk.pct, 65, 85);
    setText('card-disk-value', disk.pct + '%');
    setText('card-disk-sub',   disk.used + ' / ' + disk.total);
    setValueColor('card-disk-value', diskColor);

    // Load
    const loadColor = colorFromPct(m.load['1min_pct'], 50, 80);
    setText('card-load-value', m.load['1min']);
    setText('card-load-sub',   '5m: ' + m.load['5min'] + ' | 15m: ' + m.load['15min']);
    setValueColor('card-load-value', loadColor);

    // Uptime
    setText('card-uptime-value', m.uptime.human);

    // Apache
    const apacheColor = m.apache.status === 'running' ? 'green' : 'red';
    setText('card-apache-value', m.apache.status.toUpperCase());
    setText('card-apache-sub',   m.apache.processes + ' processes | v' + m.apache.version);
    setValueColor('card-apache-value', apacheColor);
}

// ============================================================
// Update CPU Detail
// ============================================================
function updateCpuDetail(cpu) {
    const cpuColor = colorFromPct(cpu.usage, 50, 80);

    setText('cpu-usage',  cpu.usage  + '%');
    setText('cpu-user',   cpu.user   + '%');
    setText('cpu-system', cpu.system + '%');
    setText('cpu-iowait', cpu.iowait + '%');
    setText('cpu-idle',   cpu.idle   + '%');
    setText('cpu-model',  cpu.model);
    setText('cpu-cores',  cpu.cores);

    setProgress('cpu-usage-track',  cpu.usage,  cpuColor);
    setProgress('cpu-user-track',   cpu.user,   'blue');
    setProgress('cpu-system-track', cpu.system, 'yellow');
    setProgress('cpu-iowait-track', cpu.iowait, cpu.iowait > 20 ? 'red' : 'gray');
    setProgress('cpu-idle-track',   cpu.idle,   'green');

    setValueColor('cpu-usage',  cpuColor);
    setValueColor('cpu-iowait', cpu.iowait > 20 ? 'red' : 'gray');
}

// ============================================================
// Update Memory Detail
// ============================================================
function updateMemoryDetail(mem) {
    const memColor  = colorFromPct(mem.usage_pct, 65, 85);
    const swapColor = mem.swap_pct > 50 ? 'red' : 'gray';

    setText('mem-usage',       mem.usage_pct + '%');
    setText('mem-total',       mem.total     + ' MB');
    setText('mem-used',        mem.used      + ' MB');
    setText('mem-available',   mem.available + ' MB');
    setText('mem-cached',      mem.cached    + ' MB');
    setText('mem-buffers',     mem.buffers   + ' MB');
    setText('mem-swap-total',  mem.swap_total + ' MB');
    setText('mem-swap-used',   mem.swap_used  + ' MB');
    setText('mem-swap-pct',    mem.swap_pct   + '%');

    setProgress('mem-usage-track',      mem.usage_pct, memColor);
    setProgress('mem-swap-usage-track', mem.swap_pct,  swapColor);

    setValueColor('mem-usage',     memColor);
    setValueColor('mem-used',      memColor);
    setValueColor('mem-swap-pct',  swapColor);
}

// ============================================================
// Update Disk Detail
// ============================================================
function updateDiskDetail(disks) {
    disks.forEach((disk, i) => {
        const color = colorFromPct(disk.pct, 65, 85);
        setText('disk-pct-'   + i, disk.pct  + '%');
        setText('disk-used-'  + i, disk.used);
        setText('disk-free-'  + i, disk.free);
        setText('disk-total-' + i, disk.total);
        setProgress('disk-track-' + i, disk.pct, color);
        setValueColor('disk-pct-' + i, color);
    });
}

// ============================================================
// Update Network Detail
// ============================================================
function updateNetworkDetail(networks) {
    networks.forEach((net, i) => {
        setText('net-rx-' + i, net.rx_human);
        setText('net-tx-' + i, net.tx_human);
    });
}

// ============================================================
// Update Load Average Detail
// ============================================================
function updateLoadDetail(load) {
    const items = [
        { key: '1min',  pctKey: '1min_pct'  },
        { key: '5min',  pctKey: '5min_pct'  },
        { key: '15min', pctKey: '15min_pct' },
    ];

    items.forEach(item => {
        const color = colorFromPct(load[item.pctKey], 50, 80);
        setText('load-' + item.key,      load[item.key]);
        setText('load-' + item.key + '-pct', load[item.pctKey] + '% per core');
        setProgress('load-' + item.key + '-track', load[item.pctKey], color);
        setValueColor('load-' + item.key, color);
    });

    setText('load-cores-hint',
        '💡 Load average above ' + load.cores + '.0 (1 per core) indicates the server is overloaded.'
    );
}

// ============================================================
// Update MySQL Detail
// ============================================================
function updateMysqlDetail(mysql) {
    setText('mysql-version',     mysql.version);
    setText('mysql-uptime',      mysql.uptime);
    setText('mysql-connections', mysql.connections);
    setText('mysql-max-conn',    mysql.max_connections);
    setText('mysql-queries',     mysql.queries);
    setText('mysql-slow',        mysql.slow_queries);
    setText('mysql-qps',         mysql.questions_ps);

    // Color connections
    const connColor = parseInt(mysql.connections) > 50 ? 'red' : 'green';
    setValueColor('mysql-connections', connColor);

    // Color slow queries
    const slowColor = parseInt(mysql.slow_queries) > 0 ? 'red' : 'green';
    setValueColor('mysql-slow', slowColor);
}

// ============================================================
// Update Apache Detail
// ============================================================
function updateApacheDetail(apache) {
    const color = apache.status === 'running' ? 'green' : 'red';
    setText('card-apache-value', apache.status.toUpperCase());
    setText('card-apache-sub',
        apache.processes + ' processes | v' + apache.version
    );
    setValueColor('card-apache-value', color);
}

// ============================================================
// Update Top Processes tables
// ============================================================
function updateProcesses(processes) {
    updateProcessTable('proc-cpu-tbody', processes.by_cpu);
    updateProcessTable('proc-mem-tbody', processes.by_mem);
}

function updateProcessTable(tbodyId, procs) {
    const tbody = document.getElementById(tbodyId);
    if ( ! tbody) return;

    if ( ! procs || procs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-cell">No data</td></tr>';
        return;
    }

    tbody.innerHTML = procs.map(p => {
        const cpuColor = p.cpu > 50 ? 'red' : p.cpu > 20 ? 'yellow' : 'green';
        const memColor = p.mem > 50 ? 'red' : p.mem > 20 ? 'yellow' : 'gray';

        return `
            <tr>
                <td class="text-muted">${escHtml(p.user)}</td>
                <td class="text-muted">${p.pid}</td>
                <td><span class="tag ${cpuColor}">${p.cpu}%</span></td>
                <td><span class="tag ${memColor}">${p.mem}%</span></td>
                <td class="mono" style="font-size:.75rem;">${escHtml(p.command)}</td>
            </tr>
        `;
    }).join('');
}

// ============================================================
// Timestamp + badge helpers
// ============================================================
function updateTimestamp() {
    const time = new Date().toLocaleTimeString();
    setText('last-updated',        time);
    setText('last-updated-bottom', time);
}

function setBadgeOnline() {
    const badge = document.getElementById('status-badge');
    if ( ! badge) return;
    badge.textContent = 'LIVE';
    badge.classList.remove('red');
}

function setBadgeOffline() {
    const badge = document.getElementById('status-badge');
    if ( ! badge) return;
    badge.textContent = 'OFFLINE';
    badge.classList.add('red');
}

// ============================================================
// Utility — escape HTML
// ============================================================
function escHtml(str) {
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;');
}

// ============================================================
// Init
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    startRefresh();
});