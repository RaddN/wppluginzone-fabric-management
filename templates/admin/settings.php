<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}

$fabric_types = get_option("wppluginfabric_fabric_types", array());
$colors = get_option("wppluginfabric_colors", array());
$patterns = get_option("wppluginfabric_patterns", array());
$license_key = get_option("wppluginfabric_license_key", "AZEX5Y7X5QTYLVRUQKMX");
$license_status = get_option("wppluginfabric_license_status", "inactive");
$license_info = get_option("wppluginfabric_license_info", array());
$last_license_check = get_option("wppluginfabric_last_license_check", 0);

// Check if we need to verify the license status (once every 24 hours)
$current_time = time();
if ($license_status == "active" && !empty($license_key) && ($current_time - $last_license_check > 86400)) {
    // Get license info from server to verify current status
    $info_response = wp_remote_get(
        "https://wppluginzone.com/wp-json/wc-license-manager/v1/license/{$license_key}",
        array(
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
        )
    );
    
    if (!is_wp_error($info_response)) {
        $info_body = json_decode(wp_remote_retrieve_body($info_response), true);
        if ($info_body && isset($info_body['success']) && $info_body['success']) {
            update_option("wppluginfabric_license_info", $info_body);
            
            // Update license status if it has changed on the server
            if (isset($info_body['status']) && $info_body['status'] != $license_status) {
                update_option("wppluginfabric_license_status", $info_body['status']);
                $license_status = $info_body['status'];
            }
            
            $license_info = $info_body;
        }
    }
    
    update_option("wppluginfabric_last_license_check", $current_time);
}

$updated = false;
$license_message = "";

if (isset($_POST["submit"])) {
    // Verify nonce
    if (isset($_POST["wppluginfabric_settings_nonce"]) && wp_verify_nonce($_POST["wppluginfabric_settings_nonce"], "wppluginfabric_settings")) {
        // Process fabric types
        $new_fabric_types = array();
        if (isset($_POST["fabric_types"]) && !empty($_POST["fabric_types"])) {
            $types = explode(",", sanitize_text_field($_POST["fabric_types"]));
            foreach ($types as $type) {
                $type = trim($type);
                if ($type !== "") {
                    $new_fabric_types[] = $type;
                }
            }
        }
        update_option("wppluginfabric_fabric_types", $new_fabric_types);
        
        // Process colors
        $new_colors = array();
        if (isset($_POST["colors"]) && !empty($_POST["colors"])) {
            $colors_array = explode(",", sanitize_text_field($_POST["colors"]));
            foreach ($colors_array as $color) {
                $color = trim($color);
                if ($color !== "") {
                    $new_colors[] = $color;
                }
            }
        }
        update_option("wppluginfabric_colors", $new_colors);
        
        // Process patterns
        $new_patterns = array();
        if (isset($_POST["patterns"]) && !empty($_POST["patterns"])) {
            $patterns_array = explode(",", sanitize_text_field($_POST["patterns"]));
            foreach ($patterns_array as $pattern) {
                $pattern = trim($pattern);
                if ($pattern !== "") {
                    $new_patterns[] = $pattern;
                }
            }
        }
        update_option("wppluginfabric_patterns", $new_patterns);
        
        // Process license key
        if (isset($_POST["license_key"])) {
            $new_license_key = sanitize_text_field($_POST["license_key"]);
            update_option("wppluginfabric_license_key", $new_license_key);
            $license_key = $new_license_key;
        }
        
        // Reload the updated values
        $fabric_types = get_option("wppluginfabric_fabric_types", array());
        $colors = get_option("wppluginfabric_colors", array());
        $patterns = get_option("wppluginfabric_patterns", array());
        
        $updated = true;
    }
}

// Handle license activation
if (isset($_POST["activate_license"]) && isset($_POST["wppluginfabric_license_nonce"]) && wp_verify_nonce($_POST["wppluginfabric_license_nonce"], "wppluginfabric_license")) {
    if (!empty($license_key)) {
        // Get site URL
        $site_url = get_site_url();
        
        // Make API request to activate license
        $response = wp_remote_post(
            "https://wppluginzone.com/wp-json/wc-license-manager/v1/license/{$license_key}/activate",
            array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'body' => array('site_url' => $site_url),
            )
        );
        
        if (is_wp_error($response)) {
            $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error activating license: ", "wppluginfabric") . $response->get_error_message() . '</p></div>';
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($body && isset($body['success']) && $body['success']) {
                update_option("wppluginfabric_license_status", "active");
                $license_status = "active";
                
                // Get license info
                $info_response = wp_remote_get(
                    "https://wppluginzone.com/wp-json/wc-license-manager/v1/license/{$license_key}",
                    array(
                        'method' => 'GET',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                    )
                );
                
                if (!is_wp_error($info_response)) {
                    $info_body = json_decode(wp_remote_retrieve_body($info_response), true);
                    if ($info_body && isset($info_body['success']) && $info_body['success']) {
                        update_option("wppluginfabric_license_info", $info_body);
                        $license_info = $info_body;
                    }
                }
                
                $license_message = '<div class="notice notice-success is-dismissible"><p>' . __("License activated successfully.", "wppluginfabric") . '</p></div>';
            } else {
                $error_msg = isset($body['message']) ? $body['message'] : __("Unknown error", "wppluginfabric");
                $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error activating license: ", "wppluginfabric") . $error_msg . '</p></div>';
            }
        }
    } else {
        $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Please enter a license key first.", "wppluginfabric") . '</p></div>';
    }
}

// Handle license deactivation
if (isset($_POST["deactivate_license"]) && isset($_POST["wppluginfabric_license_nonce"]) && wp_verify_nonce($_POST["wppluginfabric_license_nonce"], "wppluginfabric_license")) {
    if (!empty($license_key)) {
        // Get site URL
        $site_url = get_site_url();
        
        // Make API request to deactivate license
        $response = wp_remote_post(
            "https://wppluginzone.com/wp-json/wc-license-manager/v1/license/{$license_key}/deactivate",
            array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'body' => array('site_url' => $site_url),
            )
        );
        
        if (is_wp_error($response)) {
            $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error deactivating license: ", "wppluginfabric") . $response->get_error_message() . '</p></div>';
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($body && isset($body['success']) && $body['success']) {
                update_option("wppluginfabric_license_status", "inactive");
                update_option("wppluginfabric_license_info", array());
                $license_status = "inactive";
                $license_info = array();
                $license_message = '<div class="notice notice-success is-dismissible"><p>' . __("License deactivated successfully.", "wppluginfabric") . '</p></div>';
            } else {
                $error_msg = isset($body['message']) ? $body['message'] : __("Unknown error", "wppluginfabric");
                $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error deactivating license: ", "wppluginfabric") . $error_msg . '</p></div>';
            }
        }
    } else {
        $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("No license key found.", "wppluginfabric") . '</p></div>';
    }
}

// Handle license status check/refresh
if (isset($_POST["check_license"]) && isset($_POST["wppluginfabric_license_nonce"]) && wp_verify_nonce($_POST["wppluginfabric_license_nonce"], "wppluginfabric_license")) {
    if (!empty($license_key)) {
        // Get license info
        $info_response = wp_remote_get(
            "https://wppluginzone.com/wp-json/wc-license-manager/v1/license/{$license_key}",
            array(
                'method' => 'GET',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
            )
        );
        
        if (is_wp_error($info_response)) {
            $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error checking license: ", "wppluginfabric") . $info_response->get_error_message() . '</p></div>';
        } else {
            $info_body = json_decode(wp_remote_retrieve_body($info_response), true);
            
            if ($info_body && isset($info_body['success']) && $info_body['success']) {
                update_option("wppluginfabric_license_info", $info_body);
                
                // Update license status if it has changed
                if (isset($info_body['status'])) {
                    update_option("wppluginfabric_license_status", $info_body['status']);
                    $license_status = $info_body['status'];
                }
                
                $license_info = $info_body;
                update_option("wppluginfabric_last_license_check", time());
                
                $license_message = '<div class="notice notice-success is-dismissible"><p>' . __("License information refreshed successfully.", "wppluginfabric") . '</p></div>';
            } else {
                $error_msg = isset($info_body['message']) ? $info_body['message'] : __("Unknown error", "wppluginfabric");
                $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("Error checking license: ", "wppluginfabric") . $error_msg . '</p></div>';
            }
        }
    } else {
        $license_message = '<div class="notice notice-error is-dismissible"><p>' . __("No license key found.", "wppluginfabric") . '</p></div>';
    }
}
?>

<div class="wrap wppluginfabric-admin">
    <h1><?php _e("Fabric Management Settings", "wppluginfabric"); ?></h1>
    
    <?php if ($updated) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e("Settings updated successfully.", "wppluginfabric"); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($license_message)) {
        echo $license_message;
    } ?>
    
    <h2><?php _e("License Management", "wppluginfabric"); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field("wppluginfabric_license", "wppluginfabric_license_nonce"); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="license_key"><?php _e("License Key", "wppluginfabric"); ?></label>
                </th>
                <td>
                    <input type="text" id="license_key" name="license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>">
                    <?php if ($license_status == "inactive" || $license_status != "active") : ?>
                        <input type="submit" name="activate_license" class="button button-secondary" value="<?php _e("Activate License", "wppluginfabric"); ?>">
                    <?php else : ?>
                        <input type="submit" name="deactivate_license" class="button button-secondary" value="<?php _e("Deactivate License", "wppluginfabric"); ?>">
                    <?php endif; ?>
                    <input type="submit" name="check_license" class="button button-secondary" value="<?php _e("Refresh License", "wppluginfabric"); ?>">
                    <p class="description"><?php _e("Enter your license key to activate the plugin.", "wppluginfabric"); ?></p>
                </td>
            </tr>
            
            <?php if (!empty($license_info)) : ?>
            <tr>
                <th scope="row"><?php _e("License Information", "wppluginfabric"); ?></th>
                <td>
                    <table class="widefat fixed" style="width: auto;">
                        <tbody>
                            <tr>
                                <td><strong><?php _e("Status", "wppluginfabric"); ?></strong></td>
                                <td>
                                    <?php if (isset($license_info['status']) && $license_info['status'] == 'active'): ?>
                                        <span style="color: green; font-weight: bold;">
                                            <?php echo esc_html(ucfirst($license_info['status'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">
                                            <?php echo isset($license_info['status']) ? esc_html(ucfirst($license_info['status'])) : __("Inactive", "wppluginfabric"); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e("Product Name", "wppluginfabric"); ?></strong></td>
                                <td><?php echo isset($license_info['product_name']) ? esc_html($license_info['product_name']) : ''; ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e("Expiration Date", "wppluginfabric"); ?></strong></td>
                                <td>
                                    <?php 
                                    if (isset($license_info['expires_at'])) {
                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($license_info['expires_at'])));
                                    } 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e("Sites Allowed", "wppluginfabric"); ?></strong></td>
                                <td>
                                    <?php 
                                    if (isset($license_info['sites_active']) && isset($license_info['sites_allowed'])) {
                                        echo esc_html($license_info['sites_active'] . ' / ' . $license_info['sites_allowed']);
                                    } 
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </form>
    
    <h2><?php _e("Plugin Settings", "wppluginfabric"); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field("wppluginfabric_settings", "wppluginfabric_settings_nonce"); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="fabric_types"><?php _e("Fabric Types", "wppluginfabric"); ?></label>
                </th>
                <td>
                    <input type="text" id="fabric_types" name="fabric_types" class="large-text" value="<?php echo esc_attr(implode(", ", $fabric_types)); ?>">
                    <p class="description"><?php _e("Enter fabric types separated by commas (e.g. suit, shirt, pants).", "wppluginfabric"); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="colors"><?php _e("Colors", "wppluginfabric"); ?></label>
                </th>
                <td>
                    <input type="text" id="colors" name="colors" class="large-text" value="<?php echo esc_attr(implode(", ", $colors)); ?>">
                    <p class="description"><?php _e("Enter colors separated by commas (e.g. red, blue, green).", "wppluginfabric"); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="patterns"><?php _e("Patterns", "wppluginfabric"); ?></label>
                </th>
                <td>
                    <input type="text" id="patterns" name="patterns" class="large-text" value="<?php echo esc_attr(implode(", ", $patterns)); ?>">
                    <p class="description"><?php _e("Enter patterns separated by commas (e.g. solid, striped, checkered).", "wppluginfabric"); ?></p>
                </td>
            </tr>
        </table>
        
        
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Save Changes", "wppluginfabric"); ?>">
        </p>
    </form>

    <h2><?php _e("Shortcode Usage", "wppluginfabric"); ?></h2>
        <div class="shortcode-info">
            <p><?php _e("Use the following shortcode to display fabrics on your pages or posts:", "wppluginfabric"); ?></p>
            <code>[wppluginzonefab fabrictype="suit"]</code>
            <p><?php _e("Available parameters:", "wppluginfabric"); ?></p>
            <ul>
                <li><code>fabrictype</code> - <?php _e("Filter by fabric type (e.g. suit, shirt)", "wppluginfabric"); ?></li>
                <li><code>brand</code> - <?php _e("Filter by brand name", "wppluginfabric"); ?></li>
                <li><code>color</code> - <?php _e("Filter by color", "wppluginfabric"); ?></li>
                <li><code>pattern</code> - <?php _e("Filter by pattern", "wppluginfabric"); ?></li>
            </ul>
            <p><?php _e("Example:", "wppluginfabric"); ?> <code>[wppluginzonefab fabrictype="suit" color="blue" pattern="solid"]</code></p>
        </div>
</div>