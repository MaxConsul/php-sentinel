<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Metrics_hook {

    public function capture()
    {
        try {
            $CI =& get_instance();

            if ( ! isset($CI->metrics_tracker)) {
                return;
            }

            $query_count = 0;

            if (isset($CI->db)) {
                $queries     = $CI->db->queries;
                $qtimes      = $CI->db->query_times;
                $query_count = count($queries);

                foreach ($queries as $i => $sql) {
                    $execution_ms = isset($qtimes[$i])
                        ? round($qtimes[$i] * 1000, 4)
                        : 0;
                    $CI->metrics_tracker->log_query($sql, $execution_ms);
                }
            }

            // Pass request_id so requests and queries are linked
            $request_id = $CI->metrics_tracker->get_request_id();

            $CI->metrics_tracker->log_request(http_response_code(), $request_id);
            $CI->metrics_tracker->log_performance($query_count);

        } catch (Exception $e) {
            log_message('error', 'Metrics_hook::capture - ' . $e->getMessage());
        }
    }
}