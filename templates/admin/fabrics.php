<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}

// Get filter parameters
$fabric_types = $this->get_fabric_types();
$brands = $this->get_brands();
$colors = $this->get_colors();
$patterns = $this->get_patterns();

$filter_type = isset($_GET["filter_type"]) ? sanitize_text_field($_GET["filter_type"]) : "";
$filter_brand = isset($_GET["filter_brand"]) ? intval($_GET["filter_brand"]) : 0;
$filter_color = isset($_GET["filter_color"]) ? sanitize_text_field($_GET["filter_color"]) : "";
$filter_pattern = isset($_GET["filter_pattern"]) ? sanitize_text_field($_GET["filter_pattern"]) : "";

// Set up filter args
$filter_args = array(
    'fabric_type' => $filter_type,
    'brand_id' => $filter_brand,
    'color' => $filter_color,
    'pattern' => $filter_pattern
);

// Get filtered fabrics
$fabrics = $this->get_fabrics($filter_args);
?>

<div class="wrap wppluginfabric-admin">
    <h1 class="wp-heading-inline"><?php _e("All Fabrics", "wppluginfabric"); ?></h1>
    <a href="<?php echo admin_url("admin.php?page=wppluginfabric-add-fabric"); ?>" class="page-title-action"><?php _e("Add New", "wppluginfabric"); ?></a>
    <hr class="wp-header-end">
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: flex ;">
                <input type="hidden" name="page" value="wppluginfabric">
                
                <select name="filter_type">
                    <option value=""><?php _e("All Fabric Types", "wppluginfabric"); ?></option>
                    <?php foreach ($fabric_types as $type) : ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_type, $type); ?>><?php echo esc_html(ucfirst($type)); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="filter_brand">
                    <option value="0"><?php _e("All Brands", "wppluginfabric"); ?></option>
                    <?php foreach ($brands as $brand) : ?>
                        <option value="<?php echo esc_attr($brand->id); ?>" <?php selected($filter_brand, $brand->id); ?>><?php echo esc_html($brand->name); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="filter_color">
                    <option value=""><?php _e("All Colors", "wppluginfabric"); ?></option>
                    <?php foreach ($colors as $color) : ?>
                        <option value="<?php echo esc_attr($color); ?>" <?php selected($filter_color, $color); ?>><?php echo esc_html(ucfirst($color)); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="filter_pattern">
                    <option value=""><?php _e("All Patterns", "wppluginfabric"); ?></option>
                    <?php foreach ($patterns as $pattern) : ?>
                        <option value="<?php echo esc_attr($pattern); ?>" <?php selected($filter_pattern, $pattern); ?>><?php echo esc_html(ucfirst($pattern)); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="submit" class="button" value="<?php _e("Filter", "wppluginfabric"); ?>">
                
                <?php if ($filter_type || $filter_brand || $filter_color || $filter_pattern) : ?>
                    <a href="<?php echo admin_url("admin.php?page=wppluginfabric"); ?>" class="button"><?php _e("Reset", "wppluginfabric"); ?></a>
                <?php endif; ?>
            </form>
        </div>
        <br class="clear">
    </div>
    
    <?php if (empty($fabrics)) : ?>
        <div class="notice notice-info">
            <p><?php _e("No fabrics found. Why not add one?", "wppluginfabric"); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e("Thumbnail", "wppluginfabric"); ?></th>
                    <th><?php _e("Title", "wppluginfabric"); ?></th>
                    <th><?php _e("Type", "wppluginfabric"); ?></th>
                    <th><?php _e("Brand", "wppluginfabric"); ?></th>
                    <th><?php _e("Color", "wppluginfabric"); ?></th>
                    <th><?php _e("Pattern", "wppluginfabric"); ?></th>
                    <th><?php _e("Actions", "wppluginfabric"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fabrics as $fabric) : ?>
                    <tr>
                        <td>
                            <?php if ($fabric->thumbnail_id) : ?>
                                <?php echo wp_get_attachment_image($fabric->thumbnail_id, 'thumbnail'); ?>
                            <?php else : ?>
                                <div class="no-image"><?php _e('No Image', 'wppluginfabric'); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($fabric->title); ?></strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=wppluginfabric-add-fabric&fabric_id=' . $fabric->id); ?>"><?php _e('Edit', 'wppluginfabric'); ?></a> | 
                                </span>
                                <span class="delete">
                                    <a href="#" class="delete-fabric" data-id="<?php echo $fabric->id; ?>"><?php _e('Delete', 'wppluginfabric'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td><?php echo esc_html(ucfirst($fabric->fabric_type)); ?></td>
                        <td><?php echo esc_html($fabric->brand_name); ?></td>
                        <td><?php echo esc_html(ucfirst($fabric->color)); ?></td>
                        <td><?php echo esc_html(ucfirst($fabric->pattern)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=wppluginfabric-add-fabric&fabric_id=' . $fabric->id); ?>" class="button"><?php _e('Edit', 'wppluginfabric'); ?></a>
                            <a href="#" class="button delete-fabric" data-id="<?php echo $fabric->id; ?>"><?php _e('Delete', 'wppluginfabric'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-fabric').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e("Are you sure you want to delete this fabric?", "wppluginfabric"); ?>')) {
            return;
        }
        
        var fabricId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wppluginfabric_delete_fabric',
                fabric_id: fabricId,
                nonce: wppluginfabric.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });
});
</script>