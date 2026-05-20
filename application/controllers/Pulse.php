<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pulse extends CI_Controller {

    protected $mdb; // metrics db connection

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->config('pulse');
        $this->_check_access();
        $this->mdb = $this->load->database('metrics_db', TRUE);
    }

    // ----------------------------------------------------------
    // Auth credentials — change these!
    // ----------------------------------------------------------
    const PULSE_USERNAME = 'sentinel';
    const PULSE_PASSWORD = 'Sentinel@2026';
    const PULSE_SESSION_KEY = 'pulse_authenticated';

    // ----------------------------------------------------------
    // Login page
    // ----------------------------------------------------------
    public function login()
    {
        // Already logged in — redirect to dashboard
        if ($this->_is_authenticated()) {
            redirect('pulse');
        }

        $data['error']      = '';
        $data['page_title'] = 'PHP Sentinel — Login';

        // Handle form submission
        if ($this->input->post('username')) {
            $username = $this->input->post('username', TRUE);
            $password = $this->input->post('password', TRUE);

            if (
                $username === self::PULSE_USERNAME &&
                $password === self::PULSE_PASSWORD
            ) {
                // Set auth session
                $this->session->set_userdata(self::PULSE_SESSION_KEY, TRUE);
                redirect('pulse');
            } else {
                $data['error'] = 'Invalid username or password.';
            }
        }

        $this->load->view('pulse/login', $data);
    }

    // ----------------------------------------------------------
    // Logout
    // ----------------------------------------------------------
    public function logout()
    {
        $this->session->unset_userdata(self::PULSE_SESSION_KEY);
        redirect('pulse/login');
    }

    // ----------------------------------------------------------
    // Check if authenticated
    // ----------------------------------------------------------
    private function _is_authenticated()
    {
        return $this->session->userdata(self::PULSE_SESSION_KEY) === TRUE;
    }

    // ----------------------------------------------------------
    // Guard — replaces old _check_access()
    // ----------------------------------------------------------
    private function _check_access()
    {
        // Always allow login/logout routes
        $method = $this->router->fetch_method();
        if (in_array($method, ['login', 'logout'])) {
            return;
        }

        // Check session auth
        if ( ! $this->_is_authenticated()) {
            redirect('pulse/login');
        }
    }

    // ----------------------------------------------------------
    // Main dashboard
    // ----------------------------------------------------------
    public function index()
    {
        $data['page_title'] = 'PHP Sentinel — Pulse Dashboard';

        // --- Summary cards (last 24 hours) ---
        $data['summary'] = $this->_get_summary();

        // --- Slowest queries (last 24h, top 20) ---
        $data['slow_queries'] = $this->_get_slow_queries(20);

        // --- Slowest controllers (last 24h, top 10) ---
        $data['slow_controllers'] = $this->_get_slow_controllers(10);

        // --- Recent requests (last 50) ---
        $data['recent_requests'] = $this->_get_recent_requests(50);

        // --- Requests per minute (last 60 minutes) ---
        $data['requests_per_minute'] = $this->_get_requests_per_minute();

        $data['grouped_queries'] = $this->_get_grouped_queries(20);
        $data['n1_queries']      = $this->_get_n1_queries(5);
        $data['query_types']     = $this->_get_query_types();
        $data['response_trend'] = $this->_get_response_trend();

        $this->load->view('pulse/dashboard', $data);
    }

    // ----------------------------------------------------------
    // AJAX — live refresh every 10s
    // ----------------------------------------------------------
    public function refresh()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'summary'          => $this->_get_summary(),
            'slow_queries'     => $this->_get_slow_queries(20),
            'slow_controllers' => $this->_get_slow_controllers(10),
            'recent_requests'  => $this->_get_recent_requests(50),
            'requests_per_minute' => $this->_get_requests_per_minute(),
            'grouped_queries' => $this->_get_grouped_queries(20),
            'n1_queries'      => $this->_get_n1_queries(5),
            'query_types'     => $this->_get_query_types(),
            'response_trend' => $this->_get_response_trend(),
        ]);
    }

    // ----------------------------------------------------------
    // Clear all metrics
    // ----------------------------------------------------------
    public function clear($days = 0)
    {
        // If days=0 truncate everything, otherwise delete older than X days
        if ($days == 0) {
            $this->mdb->query('TRUNCATE TABLE metric_queries');
            $this->mdb->query('TRUNCATE TABLE metric_requests');
            $this->mdb->query('TRUNCATE TABLE metric_performance');
        } else {
            $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $this->mdb->query("DELETE FROM metric_queries     WHERE created_at < ?", [$since]);
            $this->mdb->query("DELETE FROM metric_requests    WHERE created_at < ?", [$since]);
            $this->mdb->query("DELETE FROM metric_performance WHERE created_at < ?", [$since]);
        }

        redirect('pulse');
    }

    // ----------------------------------------------------------
    // Private query helpers
    // ----------------------------------------------------------
    private function _get_summary()
    {
        $since = date('Y-m-d 00:00:00');

        // Total requests today
        $total_requests = $this->mdb->query(
            "SELECT COUNT(*) AS cnt 
            FROM metric_requests 
            WHERE created_at BETWEEN ? AND NOW()",
            [$since]
        )->row()->cnt;

        // Avg response time
        $avg_response = $this->mdb->query(
            "SELECT ROUND(AVG(response_time_ms), 2) AS avg_ms
             FROM metric_requests WHERE created_at >= ?",
            [$since]
        )->row()->avg_ms;

        // Slowest response
        $max_response = $this->mdb->query(
            "SELECT ROUND(MAX(response_time_ms), 2) AS max_ms
             FROM metric_requests WHERE created_at >= ?",
            [$since]
        )->row()->max_ms;

        // Total queries
        $total_queries = $this->mdb->query(
            "SELECT COUNT(*) AS cnt FROM metric_queries WHERE created_at >= ?",
            [$since]
        )->row()->cnt;

        // Slow queries (> 100ms)
        $slow_queries = $this->mdb->query(
            "SELECT COUNT(*) AS cnt FROM metric_queries
             WHERE created_at >= ? AND execution_ms > 100",
            [$since]
        )->row()->cnt;

        // Avg memory
        $avg_memory = $this->mdb->query(
            "SELECT ROUND(AVG(memory_peak_kb) / 1024, 2) AS avg_mb
             FROM metric_requests WHERE created_at >= ?",
            [$since]
        )->row()->avg_mb;

        return [
            'total_requests' => $total_requests,
            'avg_response'   => $avg_response   ?? 0,
            'max_response'   => $max_response    ?? 0,
            'total_queries'  => $total_queries,
            'slow_queries'   => $slow_queries,
            'avg_memory_mb'  => $avg_memory      ?? 0,
        ];
    }

    private function _get_slow_queries($limit = 20)
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $this->mdb->query(
            "SELECT query_sql, execution_ms, controller, method, request_uri, created_at
             FROM metric_queries
             WHERE created_at >= ?
             ORDER BY execution_ms DESC
             LIMIT ?",
            [$since, $limit]
        )->result_array();
    }

    private function _get_slow_controllers($limit = 10)
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $this->mdb->query(
            "SELECT controller, method,
                    ROUND(AVG(execution_ms), 2)   AS avg_ms,
                    ROUND(MAX(execution_ms), 2)   AS max_ms,
                    ROUND(AVG(memory_used_kb / 1024), 2) AS avg_memory_mb,
                    ROUND(AVG(query_count), 1)    AS avg_queries,
                    COUNT(*)                      AS hit_count
             FROM metric_performance
             WHERE created_at >= ?
             GROUP BY controller, method
             ORDER BY avg_ms DESC
             LIMIT ?",
            [$since, $limit]
        )->result_array();
    }

    private function _get_recent_requests($limit = 50)
    {
        return $this->mdb->query(
            "SELECT
                r.method,
                r.uri,
                r.controller,
                r.action,
                ROUND(r.response_time_ms, 2)        AS response_time_ms,
                r.memory_peak_kb,
                r.status_code,
                r.ip_address,
                r.username,
                r.created_at,
                r.request_id,
                COUNT(q.id)                         AS query_count,
                ROUND(AVG(q.execution_ms), 2)       AS avg_query_ms,
                ROUND(MAX(q.execution_ms), 2)       AS max_query_ms
            FROM metric_requests r
            LEFT JOIN metric_queries q ON q.request_id = r.request_id
            GROUP BY
                r.id, r.method, r.uri, r.controller, r.action,
                r.response_time_ms, r.memory_peak_kb, r.status_code,
                r.ip_address, r.username, r.created_at, r.request_id
            ORDER BY r.created_at DESC
            LIMIT ?",
            [$limit]
        )->result_array();
    }

    private function _get_requests_per_minute()
    {
        $since = date('Y-m-d H:i:s', strtotime('-60 minutes'));
        return $this->mdb->query(
            "SELECT DATE_FORMAT(created_at, '%H:%i') AS minute,
                    COUNT(*) AS count,
                    ROUND(AVG(response_time_ms), 2) AS avg_ms
             FROM metric_requests
             WHERE created_at >= ?
             GROUP BY minute
             ORDER BY minute ASC",
            [$since]
        )->result_array();
    }

    // ----------------------------------------------------------
    // Grouped queries — finds N+1 and duplicate queries
    // ----------------------------------------------------------
    private function _get_grouped_queries($limit = 20)
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $this->mdb->query(
            "SELECT
                query_sql,
                COUNT(*)                        AS run_count,
                ROUND(SUM(execution_ms), 2)     AS total_ms,
                ROUND(AVG(execution_ms), 2)     AS avg_ms,
                ROUND(MAX(execution_ms), 2)     AS max_ms,
                ROUND(MIN(execution_ms), 2)     AS min_ms,
                GROUP_CONCAT(DISTINCT controller ORDER BY controller SEPARATOR ', ') AS controllers,
                GROUP_CONCAT(DISTINCT method    ORDER BY method    SEPARATOR ', ') AS methods
            FROM metric_queries
            WHERE created_at >= ?
            GROUP BY query_sql
            ORDER BY run_count DESC, total_ms DESC
            LIMIT ?",
            [$since, $limit]
        )->result_array();
    }

    // ----------------------------------------------------------
    // N+1 detector — queries that ran many times in ONE request
    // ----------------------------------------------------------
    private function _get_n1_queries($threshold = 5)
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $this->mdb->query(
            "SELECT
                query_sql,
                request_id,
                COUNT(*)                    AS run_count,
                ROUND(SUM(execution_ms), 2) AS total_ms,
                MIN(controller)             AS controller,
                MIN(method)                 AS method,
                MIN(request_uri)            AS request_uri,
                MIN(created_at)             AS first_seen
            FROM metric_queries
            WHERE created_at >= ?
            AND request_id IS NOT NULL
            GROUP BY query_sql, request_id
            HAVING run_count >= ?
            ORDER BY run_count DESC, total_ms DESC
            LIMIT 20",
            [$since, $threshold]
        )->result_array();
    }

    // ----------------------------------------------------------
    // Query type breakdown — SELECT vs INSERT vs UPDATE etc
    // ----------------------------------------------------------
    private function _get_query_types()
    {
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $this->mdb->query(
            "SELECT
                UPPER(SUBSTRING_INDEX(TRIM(query_sql), ' ', 1)) AS query_type,
                COUNT(*)                        AS count,
                ROUND(AVG(execution_ms), 2)     AS avg_ms,
                ROUND(SUM(execution_ms), 2)     AS total_ms
            FROM metric_queries
            WHERE created_at >= ?
            GROUP BY query_type
            ORDER BY count DESC",
            [$since]
        )->result_array();
    }

    public function request_detail($request_id = '')
    {
        header('Content-Type: application/json');

        if (empty($request_id)) {
            echo json_encode(['error' => 'No request ID provided']);
            return;
        }

        // Request info
        $request = $this->mdb->query(
            "SELECT * FROM metric_requests WHERE request_id = ? LIMIT 1",
            [$request_id]
        )->row_array();

        if (empty($request)) {
            echo json_encode(['error' => 'Request not found']);
            return;
        }

        // All queries for this request
        $queries = $this->mdb->query(
            "SELECT query_sql, execution_ms, created_at
            FROM metric_queries
            WHERE request_id = ?
            ORDER BY created_at ASC",
            [$request_id]
        )->result_array();

        // Query type breakdown for this request
        $query_types = $this->mdb->query(
            "SELECT
                UPPER(SUBSTRING_INDEX(TRIM(query_sql), ' ', 1)) AS query_type,
                COUNT(*)                        AS count,
                ROUND(SUM(execution_ms), 2)     AS total_ms
            FROM metric_queries
            WHERE request_id = ?
            GROUP BY query_type
            ORDER BY count DESC",
            [$request_id]
        )->result_array();

        // Duplicate queries in this request
        $duplicates = $this->mdb->query(
            "SELECT
                query_sql,
                COUNT(*)                    AS run_count,
                ROUND(SUM(execution_ms), 2) AS total_ms,
                ROUND(AVG(execution_ms), 2) AS avg_ms
            FROM metric_queries
            WHERE request_id = ?
            GROUP BY query_sql
            HAVING run_count > 1
            ORDER BY run_count DESC",
            [$request_id]
        )->result_array();

        // Summary stats
        $total_query_ms  = array_sum(array_column($queries, 'execution_ms'));
        $response_time   = $request['response_time_ms'];
        $db_percent      = $response_time > 0
                            ? round(($total_query_ms / $response_time) * 100, 1)
                            : 0;

        echo json_encode([
            'request'      => $request,
            'queries'      => $queries,
            'query_types'  => $query_types,
            'duplicates'   => $duplicates,
            'summary'      => [
                'total_queries'   => count($queries),
                'total_query_ms'  => round($total_query_ms, 2),
                'response_time'   => $response_time,
                'db_percent'      => $db_percent,
                'memory_mb'       => round($request['memory_peak_kb'] / 1024, 2),
            ],
        ]);
    }

    private function _get_response_trend()
    {
        $since = date('Y-m-d H:i:s', strtotime('-60 minutes'));
        return $this->mdb->query(
            "SELECT
                DATE_FORMAT(created_at, '%H:%i')    AS minute,
                ROUND(AVG(response_time_ms), 2)     AS avg_ms,
                ROUND(MAX(response_time_ms), 2)     AS max_ms,
                ROUND(MIN(response_time_ms), 2)     AS min_ms,
                COUNT(*)                            AS req_count
            FROM metric_requests
            WHERE created_at >= ?
            GROUP BY minute
            ORDER BY minute ASC",
            [$since]
        )->result_array();
    }

    // ----------------------------------------------------------
    // CSV Export — last 24 hours
    // /pulse/export            → exports all 3 tables
    // /pulse/export/queries    → queries only
    // /pulse/export/requests   → requests only
    // /pulse/export/performance → performance only
    // ----------------------------------------------------------
    public function export($type = 'all')
    {
        $since     = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $filename  = 'pulse_' . $type . '_' . date('Y-m-d_His') . '.csv';

        // Set CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        switch ($type) {

            case 'queries':
                $this->_export_queries($output, $since);
                break;

            case 'requests':
                $this->_export_requests($output, $since);
                break;

            case 'performance':
                $this->_export_performance($output, $since);
                break;

            case 'all':
            default:
                // Export all 3 sections into one CSV with section headers
                fputcsv($output, ['=== SLOW QUERIES (Last 24h) ===']);
                $this->_export_queries($output, $since);

                fputcsv($output, []); // blank row
                fputcsv($output, ['=== HTTP REQUESTS (Last 24h) ===']);
                $this->_export_requests($output, $since);

                fputcsv($output, []); // blank row
                fputcsv($output, ['=== CONTROLLER PERFORMANCE (Last 24h) ===']);
                $this->_export_performance($output, $since);
                break;
        }

        fclose($output);
        exit;
    }

    // ----------------------------------------------------------
    // Export helpers
    // ----------------------------------------------------------
    private function _export_queries($output, $since)
    {
        // Summary header row
        fputcsv($output, [
            'Generated'  => 'Generated: ' . date('Y-m-d H:i:s'),
            'Period'     => 'Period: Last 24h since ' . $since,
        ]);

        // Column headers
        fputcsv($output, [
            'Query SQL',
            'Execution (ms)',
            'Controller',
            'Method',
            'Request URI',
            'Created At',
        ]);

        $rows = $this->mdb->query(
            "SELECT query_sql, execution_ms, controller, method, request_uri, created_at
            FROM metric_queries
            WHERE created_at >= ?
            ORDER BY execution_ms DESC",
            [$since]
        )->result_array();

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['query_sql'],
                $r['execution_ms'],
                $r['controller'],
                $r['method'],
                $r['request_uri'],
                $r['created_at'],
            ]);
        }

        fputcsv($output, ['Total rows: ' . count($rows)]);
    }

    private function _export_requests($output, $since)
    {
        fputcsv($output, [
            'Generated: ' . date('Y-m-d H:i:s'),
            'Period: Last 24h since ' . $since,
        ]);

        fputcsv($output, [
            'Method',
            'URI',
            'Controller',
            'Action',
            'Response Time (ms)',
            'Memory Peak (KB)',
            'Status Code',
            'IP Address',
            'Created At',
        ]);

        $rows = $this->mdb->query(
            "SELECT method, uri, controller, action, response_time_ms,
                    memory_peak_kb, status_code, ip_address, created_at
            FROM metric_requests
            WHERE created_at >= ?
            ORDER BY response_time_ms DESC",
            [$since]
        )->result_array();

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['method'],
                $r['uri'],
                $r['controller'],
                $r['action'],
                $r['response_time_ms'],
                $r['memory_peak_kb'],
                $r['status_code'],
                $r['ip_address'],
                $r['created_at'],
            ]);
        }

        fputcsv($output, ['Total rows: ' . count($rows)]);
    }

    private function _export_performance($output, $since)
    {
        fputcsv($output, [
            'Generated: ' . date('Y-m-d H:i:s'),
            'Period: Last 24h since ' . $since,
        ]);

        fputcsv($output, [
            'Controller',
            'Method',
            'Execution (ms)',
            'Memory Used (KB)',
            'Query Count',
            'Created At',
        ]);

        $rows = $this->mdb->query(
            "SELECT controller, method, execution_ms, memory_used_kb, query_count, created_at
            FROM metric_performance
            WHERE created_at >= ?
            ORDER BY execution_ms DESC",
            [$since]
        )->result_array();

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['controller'],
                $r['method'],
                $r['execution_ms'],
                $r['memory_used_kb'],
                $r['query_count'],
                $r['created_at'],
            ]);
        }

        fputcsv($output, ['Total rows: ' . count($rows)]);
    }

}