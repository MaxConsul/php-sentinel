<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pulse_cleanup
 *
 * Run via CLI / cron only — not accessible via browser.
 *
 * Usage:
 *   php /var/www/html/php-sentinel/index.php pulse_cleanup run
 *   php /var/www/html/php-sentinel/index.php pulse_cleanup run 14
 */
class Pulse_cleanup extends CI_Controller {

    protected $mdb;

    public function __construct()
    {
        parent::__construct();

        // Block browser access — CLI only
        if ( ! $this->input->is_cli_request()) {
            show_error('This controller can only be run via CLI.', 403);
        }

        $this->mdb = $this->load->database('metrics_db', TRUE);
    }

    // ----------------------------------------------------------
    // Main cleanup command
    // php index.php pulse_cleanup run
    // php index.php pulse_cleanup run 14   ← keep 14 days
    // ----------------------------------------------------------
    public function run($days = 7)
    {
        $days  = (int) $days;
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        echo "PHP Sentinel — Cleanup\n";
        echo "======================\n";
        echo "Deleting records older than {$days} days (before {$since})\n\n";

        $tables = [
            'metric_queries',
            'metric_requests',
            'metric_performance',
        ];

        $total_deleted = 0;

        foreach ($tables as $table) {
            $this->mdb->query(
                "DELETE FROM {$table} WHERE created_at < ?",
                [$since]
            );
            $deleted = $this->mdb->affected_rows();
            $total_deleted += $deleted;
            echo "  {$table}: deleted {$deleted} rows\n";
        }

        echo "\nTotal deleted: {$total_deleted} rows\n";
        echo "Done at " . date('Y-m-d H:i:s') . "\n";
    }

    // ----------------------------------------------------------
    // Show current table sizes
    // php index.php pulse_cleanup stats
    // ----------------------------------------------------------
    public function stats()
    {
        echo "PHP Sentinel — Table Stats\n";
        echo "==========================\n\n";

        $tables = ['metric_queries', 'metric_requests', 'metric_performance'];

        foreach ($tables as $table) {
            $count = $this->mdb->query(
                "SELECT COUNT(*) AS cnt FROM {$table}"
            )->row()->cnt;

            $size = $this->mdb->query(
                "SELECT
                    ROUND((data_length + index_length) / 1024, 2) AS size_kb
                 FROM information_schema.tables
                 WHERE table_schema = 'metrics_db'
                   AND table_name   = ?",
                [$table]
            )->row();

            $size_kb = $size ? $size->size_kb : 'N/A';

            echo "  {$table}\n";
            echo "    Rows:  {$count}\n";
            echo "    Size:  {$size_kb} KB\n\n";
        }

        // Oldest record
        $oldest = $this->mdb->query(
            "SELECT MIN(created_at) AS oldest FROM metric_requests"
        )->row()->oldest;

        echo "  Oldest record: " . ($oldest ?? 'none') . "\n";
        echo "  Current time:  " . date('Y-m-d H:i:s') . "\n";
    }
}