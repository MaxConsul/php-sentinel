<?php
defined('BASEPATH') OR exit('No direct script access allowed');
log_message('error', 'MY_DB_mysqli_driver FILE LOADED');
/**
 * MY_DB_mysqli_driver
 *
 * Extends CI3's mysqli driver to intercept every query
 * and forward execution time to Metrics_tracker.
 *
 * Works for ALL query styles:
 *   ->query()         raw SQL
 *   ->select/get()    active record
 *   ->insert()        active record
 *   ->update()        active record
 *   ->delete()        active record
 */
class MY_DB_mysqli_driver extends CI_DB_mysqli_driver {

    /**
     * Override the core _execute() method.
     * Every single query — regardless of style — passes through here.
     */
    protected function _execute($sql)
    {
        log_message('error', 'MY_DB_mysqli_driver::_execute FIRED - ' . substr($sql, 0, 100));
        
        // Skip tracking queries made BY the metrics library itself
        // to avoid infinite loop
        if ($this->database === 'metrics_db') {
            return parent::_execute($sql);
        }

        // --- Time the query ---
        $start  = microtime(TRUE);
        $result = parent::_execute($sql);
        $end    = microtime(TRUE);

        $execution_ms = round(($end - $start) * 1000, 4);

        // --- Forward to Metrics_tracker (only if it's loaded) ---
        $this->_send_to_tracker($sql, $execution_ms);

        return $result;
    }

    /**
     * Send query data to Metrics_tracker safely.
     * Wrapped in try/catch so a metrics failure NEVER breaks your app.
     */
    protected function _send_to_tracker($sql, $execution_ms)
    {
        try {
            $CI =& get_instance();

            // Make sure the tracker library is loaded
            if ( ! isset($CI->metrics_tracker)) {
                return;
            }

            // Log ALL queries (you can filter slow-only in the dashboard)
            $CI->metrics_tracker->log_query($sql, $execution_ms);

        } catch (Exception $e) {
            log_message('error', 'MY_DB_mysqli_driver::_send_to_tracker - ' . $e->getMessage());
        }
    }
}