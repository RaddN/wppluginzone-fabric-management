<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}

// Get all brands
$brands = $this->get_brands();
?>

<div class="wrap wppluginfabric-admin">
    <h1 class="wp-heading-inline"><?php _e("Brands", "wppluginfabric"); ?></h1>
    
    <hr class="wp-header-end">
    
    <div class="brand-form-container">
        <h2><?php _e("Add New Brand", "wppluginfabric"); ?></h2>
        <form id="brand-form" class="brand-form">
            <input type="hidden" id="brand_id" name="id" value="0">
            <div class="form-field">
                <label for="brand_name"><?php _e("Brand Name", "wppluginfabric"); ?> <span class="required">*</span></label>
                <input type="text" id="brand_name" name="name" value="" required>
            </div>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Add Brand", "wppluginfabric"); ?>">
                <button type="button" id="cancel-edit" class="button" style="display:none;"><?php _e("Cancel", "wppluginfabric"); ?></button>
            </p>
        </form>
    </div>
    
    <div class="brand-list-container">
        <h2><?php _e("All Brands", "wppluginfabric"); ?></h2>
        
        <?php if (empty($brands)) : ?>
            <div class="notice notice-info">
                <p><?php _e("No brands found. Please add a brand using the form on the left.", "wppluginfabric"); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e("Brand Name", "wppluginfabric"); ?></th>
                        <th><?php _e("Fabric Count", "wppluginfabric"); ?></th>
                        <th><?php _e("Actions", "wppluginfabric"); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $brand) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($brand->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="#" class="edit-brand" data-id="<?php echo $brand->id; ?>" data-name="<?php echo esc_attr($brand->name); ?>"><?php _e("Edit", "wppluginfabric"); ?></a> |
                                    </span>
                                    <span class="delete">
                                        <a href="#" class="delete-brand" data-id="<?php echo $brand->id; ?>"><?php _e("Delete", "wppluginfabric"); ?></a>
                                    </span>
                                </div>
                            </td>
                            <td><?php echo esc_html($brand->fabric_count); ?></td>
                            <td>
                                <a href="#" class="button edit-brand" data-id="<?php echo $brand->id; ?>" data-name="<?php echo esc_attr($brand->name); ?>"><?php _e("Edit", "wppluginfabric"); ?></a>
                                <a href="#" class="button delete-brand" data-id="<?php echo $brand->id; ?>"><?php _e("Delete", "wppluginfabric"); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Edit brand
    $(".edit-brand").on("click", function(e) {
        e.preventDefault();
        
        var brandId = $(this).data("id");
        var brandName = $(this).data("name");
        
        $("#brand_id").val(brandId);
        $("#brand_name").val(brandName);
        $("#submit").val("<?php _e("Update Brand", "wppluginfabric"); ?>");
        $("#cancel-edit").show();
    });
    
    // Cancel edit
    $("#cancel-edit").on("click", function(e) {
        e.preventDefault();
        
        $("#brand_id").val(0);
        $("#brand_name").val("");
        $("#submit").val("<?php _e("Add Brand", "wppluginfabric"); ?>");
        $(this).hide();
    });
    
    // Delete brand
    $(".delete-brand").on("click", function(e) {
        e.preventDefault();
        
        if (!confirm("<?php _e("Are you sure you want to delete this brand? All fabrics associated with this brand will be orphaned.", "wppluginfabric"); ?>")) {
            return;
        }
        
        var brandId = $(this).data("id");
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: "wppluginfabric_delete_brand",
                brand_id: brandId,
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
    
    // Form submission
    $("#brand-form").on("submit", function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        formData.push({name: "action", value: "wppluginfabric_save_brand"});
        formData.push({name: "nonce", value: wppluginfabric.nonce});
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
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