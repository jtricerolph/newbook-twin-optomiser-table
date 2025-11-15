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
     * BMA NewBook Search instance
     */
    private $bma_search = null;

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

        // Register AJAX handlers
        add_action('wp_ajax_ntot_refresh_table', array($this, 'ajax_refresh_table'));
        add_action('wp_ajax_nopriv_ntot_refresh_table', array($this, 'ajax_refresh_table'));
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Load BMA NewBook Search class if available
        if (class_exists('BMA_NewBook_Search')) {
            $this->bma_search = new BMA_NewBook_Search();
        }
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
            'start_date' => date('Y-m-d'), // Default to today
            'days' => 14 // Default to 14 days
        ), $atts, 'twin_optomiser_table');

        // Start output buffering
        ob_start();

        ?>
        <div class="ntot-table-container">
            <div class="ntot-table-header">
                <h3>Twin Booking Optimizer</h3>
                <div class="ntot-date-picker-container">
                    <label for="ntot-start-date">Start Date:</label>
                    <input type="date"
                           id="ntot-start-date"
                           class="ntot-date-picker"
                           value="<?php echo esc_attr($atts['start_date']); ?>"
                           data-days="<?php echo esc_attr($atts['days']); ?>">
                </div>
            </div>

            <div class="ntot-table-content" id="ntot-table-content">
                <?php
                // Render the table
                echo $this->render_booking_grid($atts['start_date'], $atts['days']);
                ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * AJAX handler to refresh table
     */
    public function ajax_refresh_table() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ntot-ajax-nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d');
        $days = isset($_POST['days']) ? intval($_POST['days']) : 14;

        // Render the table HTML
        $html = $this->render_booking_grid($start_date, $days);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * Render the booking grid table
     *
     * @param string $start_date Start date in Y-m-d format
     * @param int $days Number of days to display
     * @return string HTML table
     */
    private function render_booking_grid($start_date, $days = 14) {
        // Get all rooms (ascending order, exclude overflow)
        $rooms = $this->get_filtered_rooms();

        if (empty($rooms)) {
            return '<p class="ntot-no-results">No rooms available.</p>';
        }

        // Generate date range
        $dates = array();
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
        }

        // Get all bookings for the date range
        $bookings_by_room_date = $this->get_bookings_for_date_range($dates);

        // Start rendering table
        ob_start();
        ?>
        <div class="ntot-table-wrapper">
            <table class="ntot-booking-grid">
                <thead>
                    <tr>
                        <th class="ntot-room-header">Room</th>
                        <?php foreach ($dates as $date): ?>
                            <th class="ntot-date-header">
                                <div class="ntot-date-label">
                                    <span class="ntot-day"><?php echo date('D', strtotime($date)); ?></span>
                                    <span class="ntot-date"><?php echo date('j M', strtotime($date)); ?></span>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="ntot-room-cell">
                                <?php echo esc_html($room['site_name']); ?>
                            </td>
                            <?php
                            // Track which cells have been rendered (for colspan)
                            $skip_until_index = -1;

                            foreach ($dates as $date_index => $date):
                                // Skip if this cell is covered by a previous colspan
                                if ($date_index <= $skip_until_index) {
                                    continue;
                                }

                                $booking = $bookings_by_room_date[$room['site_name']][$date] ?? null;

                                if ($booking) {
                                    // Calculate colspan
                                    $colspan = $this->calculate_colspan($booking, $date, $dates, $date_index);
                                    $skip_until_index = $date_index + $colspan - 1;

                                    // Get bed type
                                    $bed_type = $this->get_bed_type($booking);
                                    $is_twin = (strtolower($bed_type) === 'twin');

                                    // Get guest name for tooltip
                                    $guest_name = $this->get_guest_name($booking);
                                    $booking_ref = $booking['booking_id'] ?? 'N/A';

                                    // Determine cell class
                                    $cell_class = $is_twin ? 'ntot-cell-twin' : 'ntot-cell-booked';
                                    ?>
                                    <td class="ntot-booking-cell <?php echo esc_attr($cell_class); ?>"
                                        colspan="<?php echo $colspan; ?>"
                                        data-guest-name="<?php echo esc_attr($guest_name); ?>"
                                        data-booking-id="<?php echo esc_attr($booking['booking_id'] ?? ''); ?>">
                                        <div class="ntot-booking-content">
                                            <span class="ntot-booking-ref"><?php echo esc_html($booking_ref); ?></span>
                                            <span class="ntot-bed-type"><?php echo esc_html($bed_type); ?></span>
                                        </div>
                                        <div class="ntot-tooltip">
                                            <?php echo esc_html($guest_name); ?>
                                        </div>
                                    </td>
                                    <?php
                                } else {
                                    // Empty/vacant cell
                                    ?>
                                    <td class="ntot-booking-cell ntot-cell-vacant"></td>
                                    <?php
                                }
                            endforeach;
                            ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get filtered and sorted rooms (ascending, exclude overflow)
     *
     * @return array Filtered rooms
     */
    private function get_filtered_rooms() {
        if (!$this->bma_search) {
            return array();
        }

        // Fetch all sites/rooms
        $sites = $this->bma_search->fetch_sites();

        if (empty($sites)) {
            return array();
        }

        // Filter out overflow rooms
        $filtered_rooms = array_filter($sites, function($site) {
            $site_name = $site['site_name'] ?? '';
            return stripos($site_name, 'overflow') === false;
        });

        // Sort ascending by site_name (natural sort for proper numeric ordering)
        usort($filtered_rooms, function($a, $b) {
            return strnatcasecmp($a['site_name'], $b['site_name']);
        });

        return $filtered_rooms;
    }

    /**
     * Get bookings for a date range organized by room and date
     *
     * @param array $dates Array of dates in Y-m-d format
     * @return array Bookings organized by [room_name][date] = booking
     */
    private function get_bookings_for_date_range($dates) {
        if (!$this->bma_search) {
            return array();
        }

        $bookings_by_room_date = array();

        // Fetch bookings for each date
        foreach ($dates as $date) {
            $bookings = $this->bma_search->fetch_staying_bookings($date);

            if (empty($bookings)) {
                continue;
            }

            // Organize bookings by room and date
            foreach ($bookings as $booking) {
                $room_name = $booking['site_name'] ?? '';

                if (empty($room_name) || stripos($room_name, 'overflow') !== false) {
                    continue;
                }

                // Store booking for this room/date combination
                // Only store if not already set (prevents duplicate entries)
                if (!isset($bookings_by_room_date[$room_name][$date])) {
                    $bookings_by_room_date[$room_name][$date] = $booking;
                }
            }
        }

        return $bookings_by_room_date;
    }

    /**
     * Calculate colspan for a booking
     *
     * @param array $booking Booking data
     * @param string $current_date Current date being processed
     * @param array $dates All dates in the grid
     * @param int $current_index Current date index
     * @return int Colspan value
     */
    private function calculate_colspan($booking, $current_date, $dates, $current_index) {
        $arrival = $booking['booking_arrival'] ?? '';
        $departure = $booking['booking_departure'] ?? '';

        if (empty($arrival) || empty($departure)) {
            return 1;
        }

        // Extract just the date portion
        $arrival_date = date('Y-m-d', strtotime($arrival));
        $departure_date = date('Y-m-d', strtotime($departure));

        // Calculate total nights
        $total_nights = (strtotime($departure_date) - strtotime($arrival_date)) / 86400;

        if ($total_nights <= 0) {
            return 1;
        }

        // Calculate how many days this booking spans within the visible grid
        $span = 0;
        for ($i = $current_index; $i < count($dates); $i++) {
            $date = $dates[$i];

            // If date is within the booking period (arrival to departure, excluding departure day)
            if ($date >= $arrival_date && $date < $departure_date) {
                $span++;
            } else if ($date >= $departure_date) {
                break;
            }
        }

        return max(1, $span);
    }

    /**
     * Get bed type from booking custom fields
     *
     * @param array $booking Booking data
     * @return string Bed type (Twin, Double, or Unknown)
     */
    private function get_bed_type($booking) {
        $custom_fields = $booking['custom_fields'] ?? array();

        foreach ($custom_fields as $field) {
            if (($field['label'] ?? '') === 'Bed Type') {
                $bed_type = $field['value'] ?? 'Unknown';

                // Abbreviate bed types
                if (stripos($bed_type, 'twin') !== false || stripos($bed_type, 'two singles') !== false) {
                    return 'Twin';
                } elseif (stripos($bed_type, 'double') !== false) {
                    return 'Double';
                }

                return $bed_type;
            }
        }

        return 'Double'; // Default to Double if not specified
    }

    /**
     * Get guest name from booking
     *
     * @param array $booking Booking data
     * @return string Guest name
     */
    private function get_guest_name($booking) {
        $guests = $booking['guests'] ?? array();

        foreach ($guests as $guest) {
            if (($guest['primary_client'] ?? '') === '1') {
                $firstname = $guest['firstname'] ?? '';
                $lastname = $guest['lastname'] ?? '';
                return trim($firstname . ' ' . $lastname);
            }
        }

        // If no primary client found, use first guest
        if (!empty($guests)) {
            $firstname = $guests[0]['firstname'] ?? '';
            $lastname = $guests[0]['lastname'] ?? '';
            return trim($firstname . ' ' . $lastname);
        }

        return 'Unknown Guest';
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
