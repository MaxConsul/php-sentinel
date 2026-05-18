<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Metrics_tracker {

    protected $CI;
    protected $metrics_db;
    protected $slow_query_threshold_ms = 100; // flag queries slower than 100ms
    protected $current_controller = '';
    protected $current_method     = '';
    protected $request_start_time;
    protected $request_id = '';
    protected $username    = ''; 

    public function __construct()
    {
        $this->CI =& get_instance();

        // -------------------------------------------------------
        // Connect to metrics_db using a SEPARATE DB connection
        // so it never interferes with your app's DB connection
        // -------------------------------------------------------
        $this->metrics_db = $this->CI->load->database('metrics_db', TRUE);

        // Capture request start time (microtime for accuracy)
        $this->request_start_time = microtime(TRUE);

        // Detect current controller and method
        $this->current_controller = $this->CI->router->fetch_class();
        $this->current_method     = $this->CI->router->fetch_method();
        $this->request_id = md5(uniqid('', TRUE));
        // Grab username from session if available
        $this->username = $this->_get_session_username();
        log_message('debug', 'Metrics_tracker: initialized');
    }

    // -------------------------------------------------------
    // Called after every query — logs it to metric_queries
    // -------------------------------------------------------
    public function log_query($sql, $execution_ms, $bindings = NULL)
    {
        try {
            $data = [
                'query_sql'    => substr($sql, 0, 5000),
                'bindings'     => $bindings ? json_encode($bindings) : NULL,
                'execution_ms' => $execution_ms,
                'controller'   => $this->current_controller,
                'method'       => $this->current_method,
                'request_uri'  => isset($_SERVER['REQUEST_URI'])
                                    ? substr($_SERVER['REQUEST_URI'], 0, 500)
                                    : '',
                'request_id'   => $this->request_id,
                'username'     => $this->username,   // ← new
                'created_at'   => date('Y-m-d H:i:s'),
            ];

            $this->metrics_db->insert('metric_queries', $data);
        }
        catch (Exception $e) {
            log_message('error', 'Metrics_tracker::log_query - ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // Called at end of request — logs to metric_requests
    // -------------------------------------------------------
    public function log_request($status_code = 200, $request_id = '')
    {
        try {
            $response_time_ms = (microtime(TRUE) - $this->request_start_time) * 1000;
            $memory_peak_kb   = round(memory_get_peak_usage(TRUE) / 1024);

            $data = [
                'method'           => isset($_SERVER['REQUEST_METHOD'])
                                        ? $_SERVER['REQUEST_METHOD'] : 'CLI',
                'uri'              => isset($_SERVER['REQUEST_URI'])
                                        ? substr($_SERVER['REQUEST_URI'], 0, 500)
                                        : '',
                'controller'       => $this->current_controller,
                'action'           => $this->current_method,
                'response_time_ms' => round($response_time_ms, 2),
                'memory_peak_kb'   => $memory_peak_kb,
                'status_code'      => $status_code,
                'ip_address'       => $this->get_ip(),
                'request_id'       => $request_id,
                'username'         => $this->username,   // ← new
                'created_at'       => date('Y-m-d H:i:s'),
            ];

            $this->metrics_db->insert('metric_requests', $data);
        }
        catch (Exception $e) {
            log_message('error', 'Metrics_tracker::log_request - ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // Called at end of request — logs to metric_performance
    // -------------------------------------------------------
    public function log_performance($query_count = 0)
    {
        try {
            $execution_ms   = (microtime(TRUE) - $this->request_start_time) * 1000;
            $memory_used_kb = round(memory_get_peak_usage(TRUE) / 1024);

            $data = [
                'controller'     => $this->current_controller,
                'method'         => $this->current_method,
                'execution_ms'   => round($execution_ms, 2),
                'memory_used_kb' => $memory_used_kb,
                'query_count'    => $query_count,
                'username'       => $this->username,   // ← new
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            $this->metrics_db->insert('metric_performance', $data);
        }
        catch (Exception $e) {
            log_message('error', 'Metrics_tracker::log_performance - ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // Helper — get real client IP
    // -------------------------------------------------------
    protected function get_ip()
    {
        foreach ([
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

    protected function _get_session_username()
    {
        try {
            // Make sure session is loaded
            if (isset($this->CI->session)) {
                $username = $this->CI->session->userdata('username');
                return $username ? substr($username, 0, 100) : 'guest';
            }
        } catch (Exception $e) {
            log_message('error', 'Metrics_tracker::_get_session_username - ' . $e->getMessage());
        }
        return 'guest'; // not logged in
    }

    // -------------------------------------------------------
    // Getters used by the DB hook
    // -------------------------------------------------------
    public function get_slow_threshold()   { return $this->slow_query_threshold_ms; }
    public function get_start_time()       { return $this->request_start_time; }
    public function get_metrics_db()       { return $this->metrics_db; }
    public function get_request_id() { return $this->request_id; }
    public function get_username() { return $this->username; }
}