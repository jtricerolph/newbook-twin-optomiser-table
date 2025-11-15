<?php
/**
 * Plugin Name: Newbook Twin Optomiser Table
 * Plugin URI: https://yourwebsite.com
 * Description: Displays optimized twin booking data using shortcodes. Requires Booking Match API plugin.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: newbook-twin-optomiser-table
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NTOT_VERSION', '1.0.0');
define('NTOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NTOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NTOT_REQUIRED_BMA_VERSION', '1.0.0'); // Minimum required Booking Match API version

/**
 * Check if Booking Match API plugin is active
 */
function ntot_check_dependencies() {
    // Check if Booking Match API is active
    if (!class_exists('Booking_Match_API')) {
        add_action('admin_notices', 'ntot_missing_dependency_notice');
        return false;
    }

    // Check version if available
    if (defined('BMA_VERSION') && version_compare(BMA_VERSION, NTOT_REQUIRED_BMA_VERSION, '<')) {
        add_action('admin_notices', 'ntot_version_mismatch_notice');
        return false;
    }

    return true;
}

/**
 * Display admin notice when Booking Match API is missing
 */
function ntot_missing_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Newbook Twin Optomiser Table</strong> requires the
            <strong>Booking Match API</strong> plugin to be installed and activated.
        </p>
    </div>
    <?php
}

/**
 * Display admin notice when Booking Match API version is too old
 */
function ntot_version_mismatch_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Newbook Twin Optomiser Table</strong> requires
            <strong>Booking Match API</strong> version <?php echo NTOT_REQUIRED_BMA_VERSION; ?> or higher.
            Current version: <?php echo defined('BMA_VERSION') ? BMA_VERSION : 'unknown'; ?>
        </p>
    </div>
    <?php
}

/**
 * Main plugin class
 */
class Newbook_Twin_Optomiser_Table {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Check dependencies before initializing
        if (!ntot_check_dependencies()) {
            return;
        }

        // Initialize plugin
        $this->init();
    }

    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();

        // Register shortcodes
        add_action('init', array($this, 'register_shortcodes'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Include additional files here as needed
        // require_once NTOT_PLUGIN_DIR . 'includes/class-ntot-table.php';
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('twin_optomiser_table', array($this, 'render_table_shortcode'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with our shortcode
        if (!is_singular()) {
            return;
        }

        global $post;
        if (!has_shortcode($post->post_content, 'twin_optomiser_table')) {
            return;
        }

        wp_enqueue_style(
            'ntot-table-style',
            NTOT_PLUGIN_URL . 'assets/css/table-style.css',
            array(),
            NTOT_VERSION
        );

        wp_enqueue_script(
            'ntot-table-script',
            NTOT_PLUGIN_URL . 'assets/js/table-script.js',
            array('jquery'),
            NTOT_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('ntot-table-script', 'ntotData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ntot-ajax-nonce')
        ));
    }

    /**
     * Render the twin optomiser table shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_table_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'property_id' => '',
            'date_from' => '',
            'date_to' => '',
            'max_results' => 10
        ), $atts, 'twin_optomiser_table');

        // Start output buffering
        ob_start();

        ?>
        <div class="ntot-table-container" data-property-id="<?php echo esc_attr($atts['property_id']); ?>">
            <div class="ntot-table-header">
                <h3>Twin Booking Optimiser</h3>
            </div>

            <div class="ntot-table-content">
                <?php
                // Get data from Booking Match API
                $data = $this->get_twin_data($atts);

                if (!empty($data)) {
                    echo $this->render_table($data);
                } else {
                    echo '<p class="ntot-no-results">No twin booking data available.</p>';
                }
                ?>
            </div>

            <div class="ntot-table-footer">
                <button class="ntot-refresh-btn">Refresh Data</button>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get twin booking data from Booking Match API
     *
     * @param array $params Query parameters
     * @return array Twin booking data
     */
    private function get_twin_data($params) {
        // TODO: Integrate with Booking Match API to fetch actual data
        // This is a placeholder that should be replaced with actual API calls

        // Example: Use the Booking_Match_API class methods
        // $bma_instance = Booking_Match_API::get_instance();
        // $data = $bma_instance->get_twin_bookings($params);

        // For now, return empty array
        return array();
    }

    /**
     * Render the data table HTML
     *
     * @param array $data Table data
     * @return string HTML table
     */
    private function render_table($data) {
        ob_start();
        ?>
        <table class="ntot-data-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Guest Name</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Room Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo esc_html($row['booking_id'] ?? ''); ?></td>
                    <td><?php echo esc_html($row['guest_name'] ?? ''); ?></td>
                    <td><?php echo esc_html($row['check_in'] ?? ''); ?></td>
                    <td><?php echo esc_html($row['check_out'] ?? ''); ?></td>
                    <td><?php echo esc_html($row['room_type'] ?? ''); ?></td>
                    <td><?php echo esc_html($row['status'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function ntot_init() {
    return Newbook_Twin_Optomiser_Table::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'ntot_init');
