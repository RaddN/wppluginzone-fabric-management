<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}

// Extract available options from the currently displayed fabrics
$available_brands = [];
$available_colors = [];
$available_patterns = [];

// Process current fabrics to determine available filter options
foreach ($fabrics_by_brand as $brand_group) {
    // Add brand to available brands
    $available_brands[$brand_group['brand']->id] = $brand_group['brand'];
    
    // Process each fabric to find available options
    foreach ($brand_group['fabrics'] as $fabric) {
        if (!empty($fabric->color)) {
            $available_colors[$fabric->color] = $fabric->color;
        }
        
        if (!empty($fabric->pattern)) {
            $available_patterns[$fabric->pattern] = $fabric->pattern;
        }
    }
}

// Sort the available options
ksort($available_colors);
ksort($available_patterns);
?>

<div class="wppluginfabric-container">
    <div class="wppluginfabric-filters">
        <form id="fabric-filter-form" class="fabric-filter-form">
            <?php if (!empty($available_brands)) : ?>
                <div class="filter-field">
                    <label for="filter-brand"><?php _e("Brand", "wppluginfabric"); ?></label>
                    <select id="filter-brand" name="brand_id">
                        <option value=""><?php _e("All Brands", "wppluginfabric"); ?></option>
                        <?php foreach ($available_brands as $brand) : ?>
                            <option value="<?php echo esc_attr($brand->id); ?>"><?php echo esc_html($brand->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($available_colors)) : ?>
                <div class="filter-field">
                    <label for="filter-color"><?php _e("Color", "wppluginfabric"); ?></label>
                    <select id="filter-color" name="color">
                        <option value=""><?php _e("All Colors", "wppluginfabric"); ?></option>
                        <?php foreach ($available_colors as $color) : ?>
                            <option value="<?php echo esc_attr($color); ?>"><?php echo esc_html(ucfirst($color)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($available_patterns)) : ?>
                <div class="filter-field">
                    <label for="filter-pattern"><?php _e("Pattern", "wppluginfabric"); ?></label>
                    <select id="filter-pattern" name="pattern">
                        <option value=""><?php _e("All Patterns", "wppluginfabric"); ?></option>
                        <?php foreach ($available_patterns as $pattern) : ?>
                            <option value="<?php echo esc_attr($pattern); ?>"><?php echo esc_html(ucfirst($pattern)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="filter-field">
                <button type="submit" class="button"><?php _e("Apply Filters", "wppluginfabric"); ?></button>
            </div>
            
            <input type="hidden" name="fabric_type" value="<?php echo esc_attr($args["fabric_type"]); ?>">
            <input type="hidden" name="action" value="wppluginfabric_filter_fabrics">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce("wppluginfabric_frontend_nonce"); ?>">
        </form>
    </div>
    
    <div id="fabric-grid" class="fabric-grid">
        <?php include WPPLUGINFABRIC_PLUGIN_DIR . "templates/frontend/fabric-grid.php"; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $("#fabric-filter-form").on("submit", function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: wppluginfabric.ajax_url,
            type: "POST",
            data: formData,
            beforeSend: function() {
                $("#fabric-grid").addClass("loading");
            },
            success: function(response) {
                if (response.success) {
                    $("#fabric-grid").html(response.data.html);
                }
            },
            complete: function() {
                $("#fabric-grid").removeClass("loading");
            }
        });
    });
});
</script>