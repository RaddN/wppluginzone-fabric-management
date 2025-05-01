<?php

/**
 * Plugin Name: wppluginzone Fabric Management
 * Description: A WordPress plugin to manage fabric inventory with filtering options
 * Version: 1.0.1
 * Author: WP Plugin Zone
 * Text Domain: wppluginfabric
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WP_Plugin_Zone_Fabric
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Setup plugin constants
        $this->define_constants();

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Define plugin constants
     */
    private function define_constants()
    {
        define('WPPLUGINFABRIC_VERSION', '1.0.1');
        define('WPPLUGINFABRIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('WPPLUGINFABRIC_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables
        $this->create_tables();

        // Add default options
        $this->add_default_options();
    }

    /**
     * Create custom database tables
     */
    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Fabrics table
        $table_fabrics = $wpdb->prefix . 'wppluginfabric_fabrics';
        $sql_fabrics = "CREATE TABLE $table_fabrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            thumbnail_id bigint(20) NOT NULL,
            fabric_type varchar(100) NOT NULL,
            brand_id bigint(20) NOT NULL,
            color varchar(100) NOT NULL,
            pattern varchar(100) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Brands table
        $table_brands = $wpdb->prefix . 'wppluginfabric_brands';
        $sql_brands = "CREATE TABLE $table_brands (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_fabrics);
        dbDelta($sql_brands);
    }

    /**
     * Add default options
     */
    private function add_default_options()
    {
        $default_options = array(
            'wppluginfabric_fabric_types' => array('suit', 'shirt', 'pants', 'dress', 'casual'),
            'wppluginfabric_colors' => array('red', 'blue', 'green', 'black', 'white', 'yellow', 'purple', 'orange', 'brown', 'gray'),
            'wppluginfabric_patterns' => array('solid', 'striped', 'checkered', 'floral', 'polka dot', 'paisley', 'geometric')
        );

        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                update_option($option_name, $option_value);
            }
        }
    }

    /**
     * Initialize plugin
     */
    /**
     * Initialize plugin
     */
    public function init()
    {

        $license_status = get_option("wppluginfabric_license_status", "inactive");
        $last_license_check = get_option("wppluginfabric_last_license_check", 0);
        $license_key = get_option("wppluginfabric_license_key", "AZEX5Y7X5QTYLVRUQKMX");
        $current_time = time();
        if ($license_status == "active" && !empty($license_key) && ($current_time - $last_license_check > 86400)) {
            update_option("wppluginfabric_last_license_check", $current_time);
        }
        // Register scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
        if ($license_status == "active" && !empty($license_key)) {            
            add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
            // Register shortcode
            add_shortcode('wppluginzonefab', array($this, 'fabric_shortcode'));
        }
        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // Register AJAX handlers
        add_action('wp_ajax_wppluginfabric_save_fabric', array($this, 'ajax_save_fabric'));
        add_action('wp_ajax_wppluginfabric_delete_fabric', array($this, 'ajax_delete_fabric'));
        add_action('wp_ajax_wppluginfabric_save_brand', array($this, 'ajax_save_brand'));
        add_action('wp_ajax_wppluginfabric_delete_brand', array($this, 'ajax_delete_brand'));

        // New AJAX handlers for adding items
        add_action('wp_ajax_wppluginfabric_add_fabric_type', array($this, 'ajax_add_fabric_type'));
        add_action('wp_ajax_wppluginfabric_add_color', array($this, 'ajax_add_color'));
        add_action('wp_ajax_wppluginfabric_add_pattern', array($this, 'ajax_add_pattern'));
        add_action('wp_ajax_wppluginfabric_add_brand', array($this, 'ajax_add_brand'));

        // Frontend AJAX handlers
        add_action('wp_ajax_wppluginfabric_filter_fabrics', array($this, 'ajax_filter_fabrics'));
        add_action('wp_ajax_nopriv_wppluginfabric_filter_fabrics', array($this, 'ajax_filter_fabrics'));
    }

    /**
     * Register scripts and styles
     */
    public function register_scripts($hook)
    {
        // Only include on plugin pages
        if (strpos($hook, 'wppluginfabric') === false) {
            return;
        }

        // Admin styles
        wp_enqueue_style('wppluginfabric-admin', WPPLUGINFABRIC_PLUGIN_URL . 'assets/css/admin.css', array(), WPPLUGINFABRIC_VERSION);

        // WordPress media uploader
        wp_enqueue_media();

        // Admin scripts
        wp_enqueue_script('wppluginfabric-admin', WPPLUGINFABRIC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WPPLUGINFABRIC_VERSION, true);

        // Localize script
        wp_localize_script('wppluginfabric-admin', 'wppluginfabric', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wppluginfabric_nonce')
        ));
    }

    /**
     * Register frontend scripts and styles
     */
    public function register_frontend_scripts()
    {
        // Frontend styles
        wp_enqueue_style('wppluginfabric-frontend', WPPLUGINFABRIC_PLUGIN_URL . 'assets/css/frontend.css', array(), WPPLUGINFABRIC_VERSION);

        // Frontend scripts
        wp_enqueue_script('wppluginfabric-frontend', WPPLUGINFABRIC_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WPPLUGINFABRIC_VERSION, true);

        // Localize script
        wp_localize_script('wppluginfabric-frontend', 'wppluginfabric', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wppluginfabric_frontend_nonce')
        ));
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu()
    {
        // Main menu
        add_menu_page(
            __('Fabric Management', 'wppluginfabric'),
            __('Fabric Management', 'wppluginfabric'),
            'manage_options',
            'wppluginfabric',
            array($this, 'admin_page_fabrics'),
            'dashicons-layout',
            30
        );

        // Fabrics submenu
        add_submenu_page(
            'wppluginfabric',
            __('All Fabrics', 'wppluginfabric'),
            __('All Fabrics', 'wppluginfabric'),
            'manage_options',
            'wppluginfabric',
            array($this, 'admin_page_fabrics')
        );

        // Add new fabric submenu
        add_submenu_page(
            'wppluginfabric',
            __('Add New Fabric', 'wppluginfabric'),
            __('Add New Fabric', 'wppluginfabric'),
            'manage_options',
            'wppluginfabric-add-fabric',
            array($this, 'admin_page_add_fabric')
        );

        // Brands submenu
        add_submenu_page(
            'wppluginfabric',
            __('Brands', 'wppluginfabric'),
            __('Brands', 'wppluginfabric'),
            'manage_options',
            'wppluginfabric-brands',
            array($this, 'admin_page_brands')
        );

        // Settings submenu
        add_submenu_page(
            'wppluginfabric',
            __('Settings', 'wppluginfabric'),
            __('Settings', 'wppluginfabric'),
            'manage_options',
            'wppluginfabric-settings',
            array($this, 'admin_page_settings')
        );
    }

    /**
     * Admin page: All Fabrics
     */
    public function admin_page_fabrics()
    {
        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/admin/fabrics.php';
    }

    /**
     * Admin page: Add/Edit Fabric
     */
    public function admin_page_add_fabric()
    {
        $fabric_id = isset($_GET['fabric_id']) ? intval($_GET['fabric_id']) : 0;

        if ($fabric_id > 0) {
            // Edit mode
            $fabric = $this->get_fabric($fabric_id);
            if (!$fabric) {
                wp_redirect(admin_url('admin.php?page=wppluginfabric'));
                exit;
            }
        }

        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/admin/fabric-form.php';
    }

    /**
     * Admin page: Brands
     */
    public function admin_page_brands()
    {
        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/admin/brands.php';
    }

    /**
     * Admin page: Settings
     */
    public function admin_page_settings()
    {
        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Get fabric by ID
     */
    public function get_fabric($fabric_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wppluginfabric_fabrics';
        $fabric = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $fabric_id));

        return $fabric;
    }

    /**
     * Get all fabrics with optional filtering
     */
    public function get_fabrics($args = array())
    {
        global $wpdb;

        $default_args = array(
            'fabric_type' => '',
            'brand_id' => 0,
            'color' => '',
            'pattern' => '',
            'per_page' => -1,
            'page' => 1,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $default_args);

        $table_fabrics = $wpdb->prefix . 'wppluginfabric_fabrics';
        $table_brands = $wpdb->prefix . 'wppluginfabric_brands';

        $sql = "SELECT f.*, b.name as brand_name 
                FROM $table_fabrics f
                LEFT JOIN $table_brands b ON f.brand_id = b.id
                WHERE 1=1";

        $sql_args = array();

        if (!empty($args['fabric_type'])) {
            $sql .= " AND f.fabric_type = %s";
            $sql_args[] = $args['fabric_type'];
        }

        if (!empty($args['brand_id'])) {
            $sql .= " AND f.brand_id = %d";
            $sql_args[] = $args['brand_id'];
        }

        if (!empty($args['color'])) {
            $sql .= " AND f.color = %s";
            $sql_args[] = $args['color'];
        }

        if (!empty($args['pattern'])) {
            $sql .= " AND f.pattern = %s";
            $sql_args[] = $args['pattern'];
        }

        // Order
        $sql .= " ORDER BY f." . sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        // Limit
        if ($args['per_page'] > 0) {
            $offset = ($args['page'] - 1) * $args['per_page'];
            $sql .= " LIMIT %d, %d";
            $sql_args[] = $offset;
            $sql_args[] = $args['per_page'];
        }

        if (!empty($sql_args)) {
            $sql = $wpdb->prepare($sql, $sql_args);
        }

        $fabrics = $wpdb->get_results($sql);

        return $fabrics;
    }

    /**
     * Get all brands
     */
    public function get_brands($brand_id = 0)
    {
        global $wpdb;

        $table_brands = $wpdb->prefix . 'wppluginfabric_brands';
        $table_fabrics = $wpdb->prefix . 'wppluginfabric_fabrics';

        if ($brand_id === 0) {
            $sql = "SELECT b.*, COUNT(f.id) as fabric_count 
                FROM $table_brands b
                LEFT JOIN $table_fabrics f ON b.id = f.brand_id
                GROUP BY b.id
                ORDER BY b.name ASC";
        } else {
            $sql = $wpdb->prepare(
                "SELECT b.*, COUNT(f.id) as fabric_count 
                FROM $table_brands b
                LEFT JOIN $table_fabrics f ON b.id = f.brand_id
                WHERE b.id = %d
                GROUP BY b.id
                ORDER BY b.name ASC",
                $brand_id
            );
        }

        $brands = $wpdb->get_results($sql);

        return $brands;
    }

    /**
     * Get fabrics grouped by brand
     */
    public function get_fabrics_by_brand($args = array())
    {
        global $wpdb;

        $brands = $this->get_brands($args['brand_id']);
        $results = array();

        foreach ($brands as $brand) {

            $args['brand_id'] = $brand->id;

            $fabrics = $this->get_fabrics($args);

            if (!empty($fabrics)) {
                $results[$brand->id] = array(
                    'brand' => $brand,
                    'fabrics' => $fabrics
                );
            }
        }

        return $results;
    }

    /**
     * Get fabric types
     */
    public function get_fabric_types()
    {
        return get_option('wppluginfabric_fabric_types', array());
    }

    /**
     * Get colors
     */
    public function get_colors()
    {
        return get_option('wppluginfabric_colors', array());
    }

    /**
     * Get patterns
     */
    public function get_patterns()
    {
        return get_option('wppluginfabric_patterns', array());
    }

    /**
     * Save fabric
     */
    public function save_fabric($data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wppluginfabric_fabrics';
        $now = current_time('mysql');

        $fabric_data = array(
            'title' => sanitize_text_field($data['title']),
            'description' => wp_kses_post($data['description']),
            'thumbnail_id' => intval($data['thumbnail_id']),
            'fabric_type' => sanitize_text_field($data['fabric_type']),
            'brand_id' => intval($data['brand_id']),
            'color' => sanitize_text_field($data['color']),
            'pattern' => sanitize_text_field($data['pattern']),
            'updated_at' => $now
        );

        if (isset($data['id']) && intval($data['id']) > 0) {
            // Update existing fabric
            $wpdb->update(
                $table,
                $fabric_data,
                array('id' => intval($data['id']))
            );

            return intval($data['id']);
        } else {
            // Insert new fabric
            $fabric_data['created_at'] = $now;

            $wpdb->insert(
                $table,
                $fabric_data
            );

            return $wpdb->insert_id;
        }
    }

    /**
     * Delete fabric
     */
    public function delete_fabric($fabric_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wppluginfabric_fabrics';

        return $wpdb->delete(
            $table,
            array('id' => intval($fabric_id))
        );
    }

    /**
     * Save brand
     */
    public function save_brand($data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wppluginfabric_brands';
        $now = current_time('mysql');

        $brand_data = array(
            'name' => sanitize_text_field($data['name']),
            'updated_at' => $now
        );

        if (isset($data['id']) && intval($data['id']) > 0) {
            // Update existing brand
            $wpdb->update(
                $table,
                $brand_data,
                array('id' => intval($data['id']))
            );

            return intval($data['id']);
        } else {
            // Insert new brand
            $brand_data['created_at'] = $now;

            $wpdb->insert(
                $table,
                $brand_data
            );

            return $wpdb->insert_id;
        }
    }

    /**
     * Delete brand
     */
    public function delete_brand($brand_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wppluginfabric_brands';

        return $wpdb->delete(
            $table,
            array('id' => intval($brand_id))
        );
    }

    /**
     * AJAX: Save fabric
     */
    public function ajax_save_fabric()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['title']) || empty($_POST['fabric_type']) || empty($_POST['brand_id'])) {
            wp_send_json_error('Missing required fields');
        }

        $fabric_id = $this->save_fabric($_POST);

        if ($fabric_id) {
            wp_send_json_success(array(
                'fabric_id' => $fabric_id,
                'message' => __('Fabric saved successfully', 'wppluginfabric')
            ));
        } else {
            wp_send_json_error('Failed to save fabric');
        }
    }

    /**
     * AJAX: Delete fabric
     */
    public function ajax_delete_fabric()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['fabric_id'])) {
            wp_send_json_error('Missing fabric ID');
        }

        $result = $this->delete_fabric($_POST['fabric_id']);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Fabric deleted successfully', 'wppluginfabric')
            ));
        } else {
            wp_send_json_error('Failed to delete fabric');
        }
    }

    /**
     * AJAX: Save brand
     */
    public function ajax_save_brand()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['name'])) {
            wp_send_json_error('Missing brand name');
        }

        $brand_id = $this->save_brand($_POST);

        if ($brand_id) {
            wp_send_json_success(array(
                'brand_id' => $brand_id,
                'message' => __('Brand saved successfully', 'wppluginfabric')
            ));
        } else {
            wp_send_json_error('Failed to save brand');
        }
    }

    /**
     * AJAX: Delete brand
     */
    public function ajax_delete_brand()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['brand_id'])) {
            wp_send_json_error('Missing brand ID');
        }

        $result = $this->delete_brand($_POST['brand_id']);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Brand deleted successfully', 'wppluginfabric')
            ));
        } else {
            wp_send_json_error('Failed to delete brand');
        }
    }

    /**
     * AJAX: Filter fabrics
     */
    public function ajax_filter_fabrics()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_frontend_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $args = array(
            'fabric_type' => isset($_POST['fabric_type']) ? sanitize_text_field($_POST['fabric_type']) : '',
            'brand_id' => isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0,
            'color' => isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '',
            'pattern' => isset($_POST['pattern']) ? sanitize_text_field($_POST['pattern']) : ''
        );

        $fabrics_by_brand = $this->get_fabrics_by_brand($args);

        ob_start();
        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/frontend/fabric-grid.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    /**
     * Shortcode: [wppluginzonefab]
     */
    public function fabric_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'fabrictype' => '',
            'brand' => '',
            'color' => '',
            'pattern' => ''
        ), $atts, 'wppluginzonefab');

        $args = array(
            'fabric_type' => $atts['fabrictype'],
            'brand_id' => 0
        );

        $fabrics_by_brand = $this->get_fabrics_by_brand($args);
        $all_brands = $this->get_brands();
        $all_colors = $this->get_colors();
        $all_patterns = $this->get_patterns();

        ob_start();
        include WPPLUGINFABRIC_PLUGIN_DIR . 'templates/frontend/fabric-display.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Add new fabric type
     */
    public function ajax_add_fabric_type()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['name'])) {
            wp_send_json_error('Missing fabric type name');
        }

        $new_type = sanitize_text_field($_POST['name']);
        $fabric_types = $this->get_fabric_types();

        // Check if type already exists
        if (in_array($new_type, $fabric_types)) {
            wp_send_json_error('This fabric type already exists');
        }

        // Add new type
        $fabric_types[] = $new_type;
        update_option('wppluginfabric_fabric_types', $fabric_types);

        wp_send_json_success(array(
            'message' => __('Fabric type added successfully', 'wppluginfabric')
        ));
    }

    /**
     * AJAX: Add new color
     */
    public function ajax_add_color()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['name'])) {
            wp_send_json_error('Missing color name');
        }

        $new_color = sanitize_text_field($_POST['name']);
        $colors = $this->get_colors();

        // Check if color already exists
        if (in_array($new_color, $colors)) {
            wp_send_json_error('This color already exists');
        }

        // Add new color
        $colors[] = $new_color;
        update_option('wppluginfabric_colors', $colors);

        wp_send_json_success(array(
            'message' => __('Color added successfully', 'wppluginfabric')
        ));
    }

    /**
     * AJAX: Add new pattern
     */
    public function ajax_add_pattern()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['name'])) {
            wp_send_json_error('Missing pattern name');
        }

        $new_pattern = sanitize_text_field($_POST['name']);
        $patterns = $this->get_patterns();

        // Check if pattern already exists
        if (in_array($new_pattern, $patterns)) {
            wp_send_json_error('This pattern already exists');
        }

        // Add new pattern
        $patterns[] = $new_pattern;
        update_option('wppluginfabric_patterns', $patterns);

        wp_send_json_success(array(
            'message' => __('Pattern added successfully', 'wppluginfabric')
        ));
    }

    /**
     * AJAX: Add new brand
     */
    public function ajax_add_brand()
    {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppluginfabric_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate data
        if (empty($_POST['name'])) {
            wp_send_json_error('Missing brand name');
        }

        $brand_data = array(
            'name' => sanitize_text_field($_POST['name'])
        );

        $brand_id = $this->save_brand($brand_data);

        if ($brand_id) {
            wp_send_json_success(array(
                'id' => $brand_id,
                'message' => __('Brand added successfully', 'wppluginfabric')
            ));
        } else {
            wp_send_json_error('Failed to add brand');
        }
    }
}

// Initialize plugin
$wp_plugin_zone_fabric = new WP_Plugin_Zone_Fabric();
