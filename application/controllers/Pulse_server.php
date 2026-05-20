<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulse_server
 *
 * Server Health Dashboard
 * Reads system metrics from /proc (Linux only)
 * Route: /pulse/server
 */
class Pulse_server extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->config('pulse');
        $this->_check_access();
    }

    private function _is_authenticated()
    {
        return $this->session->userdata('pulse_authenticated') === TRUE;
    }

    private function _check_access()
    {
        if ( ! $this->_is_authenticated()) {
            redirect('pulse/login');
        }
    }

    // ----------------------------------------------------------
    // Main dashboard view
    // ----------------------------------------------------------
    public function index()
    {
        $data['page_title'] = 'PHP Sentinel — Server Health';
        $data['metrics']    = $this->_get_all_metrics();

        $this->load->view('pulse/server', $data);
    }

    // ----------------------------------------------------------
    // AJAX endpoint — auto refresh every 5 seconds
    // /pulse_server/refresh
    // ----------------------------------------------------------
    public function refresh()
    {
        header('Content-Type: application/json');
        echo json_encode($this->_get_all_metrics());
    }

    // ----------------------------------------------------------
    // Collect all metrics in one call
    // ----------------------------------------------------------
    private function _get_all_metrics()
    {
        return [
            'cpu'        => $this->_get_cpu(),
            'memory'     => $this->_get_memory(),
            'disk'       => $this->_get_disk(),
            'load'       => $this->_get_load_average(),
            'network'    => $this->_get_network(),
            'uptime'     => $this->_get_uptime(),
            'processes'  => $this->_get_top_processes(),
            'php'        => $this->_get_php_info(),
            'mysql'      => $this->_get_mysql_status(),
            'apache'     => $this->_get_apache_status(),
            'timestamp'  => date('Y-m-d H:i:s'),
        ];
    }

    // ----------------------------------------------------------
    // CPU Usage
    // Reads /proc/stat twice with a small delay for accuracy
    // ----------------------------------------------------------
    private function _get_cpu()
    {
        try {
            // First reading
            $stat1  = file('/proc/stat');
            $cpu1   = explode(' ', preg_replace('/\s+/', ' ', $stat1[0]));
            usleep(200000); // wait 200ms

            // Second reading
            $stat2  = file('/proc/stat');
            $cpu2   = explode(' ', preg_replace('/\s+/', ' ', $stat2[0]));

            // Calculate differences
            $user   = $cpu2[1] - $cpu1[1];
            $nice   = $cpu2[2] - $cpu1[2];
            $system = $cpu2[3] - $cpu1[3];
            $idle   = $cpu2[4] - $cpu1[4];
            $iowait = isset($cpu2[5]) ? $cpu2[5] - $cpu1[5] : 0;
            $irq    = isset($cpu2[6]) ? $cpu2[6] - $cpu1[6] : 0;
            $total  = $user + $nice + $system + $idle + $iowait + $irq;

            if ($total == 0) return $this->_cpu_empty();

            return [
                'usage'   => round((($total - $idle) / $total) * 100, 1),
                'user'    => round(($user   / $total) * 100, 1),
                'system'  => round(($system / $total) * 100, 1),
                'idle'    => round(($idle   / $total) * 100, 1),
                'iowait'  => round(($iowait / $total) * 100, 1),
                'cores'   => $this->_get_cpu_cores(),
                'model'   => $this->_get_cpu_model(),
            ];
        } catch (Exception $e) {
            return $this->_cpu_empty();
        }
    }

    private function _cpu_empty()
    {
        return [
            'usage'  => 0, 'user'   => 0,
            'system' => 0, 'idle'   => 100,
            'iowait' => 0, 'cores'  => 1,
            'model'  => 'Unknown',
        ];
    }

    private function _get_cpu_cores()
    {
        $cores = 0;
        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $cores = count($matches[0]);
        }
        return max(1, $cores);
    }

    private function _get_cpu_model()
    {
        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if (preg_match('/model name\s+:\s+(.+)/i', $cpuinfo, $m)) {
                return trim($m[1]);
            }
        }
        return 'Unknown';
    }

    // ----------------------------------------------------------
    // Memory Usage — reads /proc/meminfo
    // ----------------------------------------------------------
    private function _get_memory()
    {
        try {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match_all('/^(\w+):\s+(\d+)/m', $meminfo, $matches);
            $mem = array_combine($matches[1], $matches[2]);

            $total     = isset($mem['MemTotal'])     ? (int)$mem['MemTotal']     : 0;
            $free      = isset($mem['MemFree'])      ? (int)$mem['MemFree']      : 0;
            $available = isset($mem['MemAvailable']) ? (int)$mem['MemAvailable'] : $free;
            $cached    = isset($mem['Cached'])       ? (int)$mem['Cached']       : 0;
            $buffers   = isset($mem['Buffers'])      ? (int)$mem['Buffers']      : 0;
            $swap_total = isset($mem['SwapTotal'])   ? (int)$mem['SwapTotal']    : 0;
            $swap_free  = isset($mem['SwapFree'])    ? (int)$mem['SwapFree']     : 0;

            $used      = $total - $available;
            $usage_pct = $total > 0 ? round(($used / $total) * 100, 1) : 0;
            $swap_used = $swap_total - $swap_free;

            return [
                'total'      => round($total     / 1024, 1), // MB
                'used'       => round($used       / 1024, 1),
                'free'       => round($free       / 1024, 1),
                'available'  => round($available  / 1024, 1),
                'cached'     => round($cached     / 1024, 1),
                'buffers'    => round($buffers    / 1024, 1),
                'usage_pct'  => $usage_pct,
                'swap_total' => round($swap_total / 1024, 1),
                'swap_used'  => round($swap_used  / 1024, 1),
                'swap_pct'   => $swap_total > 0
                                ? round(($swap_used / $swap_total) * 100, 1)
                                : 0,
            ];
        } catch (Exception $e) {
            return [
                'total' => 0, 'used' => 0, 'free' => 0,
                'available' => 0, 'cached' => 0, 'buffers' => 0,
                'usage_pct' => 0, 'swap_total' => 0,
                'swap_used' => 0, 'swap_pct' => 0,
            ];
        }
    }

    // ----------------------------------------------------------
    // Disk Usage — uses df command
    // ----------------------------------------------------------
    private function _get_disk()
    {
        try {
            $disks  = [];
            $output = shell_exec('df -h --output=target,size,used,avail,pcent 2>/dev/null');

            if ( ! $output) {
                // Fallback to PHP built-in for root
                return [[
                    'mount'   => '/',
                    'total'   => $this->_bytes_to_human(disk_total_space('/')),
                    'used'    => $this->_bytes_to_human(disk_total_space('/') - disk_free_space('/')),
                    'free'    => $this->_bytes_to_human(disk_free_space('/')),
                    'pct'     => round(((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/')) * 100, 1),
                ]];
            }

            $lines = explode("\n", trim($output));
            array_shift($lines); // remove header

            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) < 5) continue;

                // Only show real mounts, skip tmpfs/devtmpfs
                $mount = $parts[0];
                if (strpos($mount, '/dev') === 0 || $mount === 'tmpfs') continue;
                if ( ! in_array($mount, ['/', '/var', '/tmp', '/home', '/var/www'])) {
                    if (strpos($mount, '/') !== 0) continue;
                }

                $disks[] = [
                    'mount' => $mount,
                    'total' => $parts[1],
                    'used'  => $parts[2],
                    'free'  => $parts[3],
                    'pct'   => (int) str_replace('%', '', $parts[4]),
                ];
            }

            return ! empty($disks) ? $disks : [[
                'mount' => '/', 'total' => 'N/A',
                'used'  => 'N/A', 'free' => 'N/A', 'pct' => 0,
            ]];

        } catch (Exception $e) {
            return [['mount' => '/', 'total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A', 'pct' => 0]];
        }
    }

    // ----------------------------------------------------------
    // Load Average — reads /proc/loadavg
    // ----------------------------------------------------------
    private function _get_load_average()
    {
        try {
            $load = sys_getloadavg();
            $cores = $this->_get_cpu_cores();

            return [
                '1min'   => round($load[0], 2),
                '5min'   => round($load[1], 2),
                '15min'  => round($load[2], 2),
                'cores'  => $cores,
                // Load per core — above 1.0 per core = overloaded
                '1min_pct'  => round(($load[0] / $cores) * 100, 1),
                '5min_pct'  => round(($load[1] / $cores) * 100, 1),
                '15min_pct' => round(($load[2] / $cores) * 100, 1),
            ];
        } catch (Exception $e) {
            return [
                '1min' => 0, '5min' => 0, '15min' => 0,
                'cores' => 1, '1min_pct' => 0,
                '5min_pct' => 0, '15min_pct' => 0,
            ];
        }
    }

    // ----------------------------------------------------------
    // Network I/O — reads /proc/net/dev
    // Measures bytes per second over 1 second
    // ----------------------------------------------------------
    private function _get_network()
    {
        try {
            $get_net = function() {
                $data   = [];
                $lines  = file('/proc/net/dev');
                foreach ($lines as $line) {
                    if (strpos($line, ':') === false) continue;
                    list($iface, $stats) = explode(':', $line);
                    $iface  = trim($iface);
                    if ($iface === 'lo') continue; // skip loopback
                    $parts  = preg_split('/\s+/', trim($stats));
                    $data[$iface] = [
                        'rx' => (int)$parts[0],
                        'tx' => (int)$parts[8],
                    ];
                }
                return $data;
            };

            $before = $get_net();
            usleep(500000); // 500ms sample
            $after  = $get_net();

            $interfaces = [];
            foreach ($after as $iface => $vals) {
                if ( ! isset($before[$iface])) continue;
                $rx_bytes = ($vals['rx'] - $before[$iface]['rx']) * 2; // per second
                $tx_bytes = ($vals['tx'] - $before[$iface]['tx']) * 2;

                $interfaces[] = [
                    'interface' => $iface,
                    'rx_bps'    => max(0, $rx_bytes),
                    'tx_bps'    => max(0, $tx_bytes),
                    'rx_human'  => $this->_bytes_to_human(max(0, $rx_bytes)) . '/s',
                    'tx_human'  => $this->_bytes_to_human(max(0, $tx_bytes)) . '/s',
                ];
            }

            return $interfaces;

        } catch (Exception $e) {
            return [];
        }
    }

    // ----------------------------------------------------------
    // System Uptime — reads /proc/uptime
    // ----------------------------------------------------------
    private function _get_uptime()
    {
        try {
            $uptime_seconds = (float) explode(' ', file_get_contents('/proc/uptime'))[0];

            $days    = floor($uptime_seconds / 86400);
            $hours   = floor(($uptime_seconds % 86400) / 3600);
            $minutes = floor(($uptime_seconds % 3600) / 60);

            $parts = [];
            if ($days > 0)    $parts[] = $days . 'd';
            if ($hours > 0)   $parts[] = $hours . 'h';
            $parts[] = $minutes . 'm';

            return [
                'seconds' => (int) $uptime_seconds,
                'human'   => implode(' ', $parts),
                'days'    => $days,
            ];
        } catch (Exception $e) {
            return ['seconds' => 0, 'human' => 'Unknown', 'days' => 0];
        }
    }

    // ----------------------------------------------------------
    // Top Processes by CPU and Memory
    // ----------------------------------------------------------
    private function _get_top_processes()
    {
        try {
            // Top 5 by CPU
            $cpu_out = shell_exec(
                "ps aux --sort=-%cpu 2>/dev/null | awk 'NR>1{print $1,$2,$3,$4,$11}' | head -6"
            );

            // Top 5 by Memory
            $mem_out = shell_exec(
                "ps aux --sort=-%mem 2>/dev/null | awk 'NR>1{print $1,$2,$3,$4,$11}' | head -6"
            );

            $parse = function($output) {
                $processes = [];
                if ( ! $output) return $processes;
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    $parts = preg_split('/\s+/', trim($line), 5);
                    if (count($parts) < 5) continue;
                    $processes[] = [
                        'user'    => $parts[0],
                        'pid'     => $parts[1],
                        'cpu'     => $parts[2],
                        'mem'     => $parts[3],
                        'command' => strlen($parts[4]) > 40
                                     ? substr($parts[4], 0, 40) . '...'
                                     : $parts[4],
                    ];
                }
                return $processes;
            };

            return [
                'by_cpu' => $parse($cpu_out),
                'by_mem' => $parse($mem_out),
            ];

        } catch (Exception $e) {
            return ['by_cpu' => [], 'by_mem' => []];
        }
    }

    // ----------------------------------------------------------
    // PHP Info
    // ----------------------------------------------------------
    private function _get_php_info()
    {
        return [
            'version'      => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution'=> ini_get('max_execution_time') . 's',
            'upload_max'   => ini_get('upload_max_filesize'),
            'extensions'   => implode(', ', array_slice(get_loaded_extensions(), 0, 10)),
        ];
    }

    // ----------------------------------------------------------
    // MySQL Status — connects to metrics_db and runs SHOW STATUS
    // ----------------------------------------------------------
    private function _get_mysql_status()
    {
        try {
            $mdb = $this->load->database('metrics_db', TRUE);

            $get = function($key) use ($mdb) {
                $row = $mdb->query(
                    "SHOW STATUS LIKE ?", [$key]
                )->row_array();
                return $row ? $row['Value'] : 'N/A';
            };

            $uptime      = (int) $get('Uptime');
            $up_hours    = floor($uptime / 3600);
            $up_minutes  = floor(($uptime % 3600) / 60);

            return [
                'connections'      => $get('Threads_connected'),
                'max_connections'  => $get('Max_used_connections'),
                'queries'          => number_format((int) $get('Queries')),
                'slow_queries'     => $get('Slow_queries'),
                'uptime'           => $up_hours . 'h ' . $up_minutes . 'm',
                'version'          => $mdb->query('SELECT VERSION() AS v')->row()->v,
                'questions_ps'     => round((int)$get('Questions') / max(1, $uptime), 1),
                'innodb_buffer'    => $get('Innodb_buffer_pool_reads'),
            ];
        } catch (Exception $e) {
            return [
                'connections' => 'N/A', 'max_connections' => 'N/A',
                'queries'     => 'N/A', 'slow_queries'    => 'N/A',
                'uptime'      => 'N/A', 'version'         => 'N/A',
                'questions_ps'=> 'N/A', 'innodb_buffer'   => 'N/A',
            ];
        }
    }

    // ----------------------------------------------------------
    // Apache Status
    // ----------------------------------------------------------
    private function _get_apache_status()
    {
        try {
            $processes = (int) shell_exec(
                "ps aux 2>/dev/null | grep -c '[a]pache\|[h]ttpd'"
            );

            $version = shell_exec('apache2 -v 2>/dev/null || httpd -v 2>/dev/null');
            preg_match('/Apache\/(\S+)/', $version ?? '', $matches);

            return [
                'processes' => $processes,
                'version'   => isset($matches[1]) ? $matches[1] : 'Unknown',
                'status'    => $processes > 0 ? 'running' : 'stopped',
            ];
        } catch (Exception $e) {
            return [
                'processes' => 0,
                'version'   => 'Unknown',
                'status'    => 'unknown',
            ];
        }
    }


    // ----------------------------------------------------------
    // Helper — bytes to human readable
    // ----------------------------------------------------------
    private function _bytes_to_human($bytes)
    {
        $bytes = max(0, (int) $bytes);
        if ($bytes < 1024)        return $bytes . ' B';
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824)  return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}