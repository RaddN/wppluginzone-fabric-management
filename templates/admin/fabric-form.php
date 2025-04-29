<?php // Exit if accessed directly
if (!defined("ABSPATH")) { exit; }

$fabric_id = isset($_GET["fabric_id"]) ? intval($_GET["fabric_id"]) : 0;
$fabric = null;
if ($fabric_id > 0) {
    $fabric = $this->get_fabric($fabric_id);
}
$is_edit = !empty($fabric);
$page_title = $is_edit ? __("Edit Fabric", "wppluginfabric") : __("Add New Fabric", "wppluginfabric");
$fabric_types = $this->get_fabric_types();
$brands = $this->get_brands();
$colors = $this->get_colors();
$patterns = $this->get_patterns();
?>

<div class="wrap wppluginfabric-admin">
    <h1 class="wp-heading-inline"><?php echo $page_title; ?></h1>
    <hr class="wp-header-end">
    
    <form id="fabric-form" class="fabric-form">
        <?php wp_nonce_field("wppluginfabric_nonce", "fabric_nonce"); ?>
        <?php if ($is_edit) : ?>
            <input type="hidden" name="id" value="<?php echo $fabric->id; ?>">
        <?php endif; ?>
        
        <div class="form-container">
            <!-- Left Column -->
            <div class="form-column left-column">
                <div class="form-field">
                    <label for="title"><?php _e("Fabric Title", "wppluginfabric"); ?> <span class="required">*</span></label>
                    <input style="width: 100%;max-width: 100%;" type="text" id="title" name="title" value="<?php echo $is_edit ? esc_attr($fabric->title) : ""; ?>" required>
                </div>
                
                <div class="form-field">
                    <label for="description"><?php _e("Description", "wppluginfabric"); ?></label>
                    <?php 
                    $content = $is_edit ? $fabric->description : "";
                    $editor_id = "description";
                    $settings = array(
                        "textarea_name" => "description",
                        "textarea_rows" => 6,
                        "media_buttons" => false,
                        "tinymce" => array(
                            'toolbar1' => 'bold,italic,underline,bullist,numlist',
                            'toolbar2' => '',
                        ),
                    );
                    wp_editor($content, $editor_id, $settings);
                    ?>
                </div>
                
                <div class="color-pattern-grid">
                    <div class="form-field">
                        <label><?php _e("Color", "wppluginfabric"); ?> <span class="required">*</span></label>
                        <div class="checkbox-container" id="color-container">
                            <?php foreach ($colors as $color) : ?>
                            <label class="checkbox-label">
                                <input type="radio" name="color" value="<?php echo esc_attr($color); ?>" <?php echo ($is_edit && $fabric->color === $color) ? "checked" : ""; ?> required>
                                <?php echo esc_html(ucfirst($color)); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button-link add-new-item" data-target="color"><?php _e("+ Add New Color", "wppluginfabric"); ?></button>
                    </div>
                    
                    <div class="form-field">
                        <label><?php _e("Pattern", "wppluginfabric"); ?> <span class="required">*</span></label>
                        <div class="checkbox-container" id="pattern-container">
                            <?php foreach ($patterns as $pattern) : ?>
                            <label class="checkbox-label">
                                <input type="radio" name="pattern" value="<?php echo esc_attr($pattern); ?>" <?php echo ($is_edit && $fabric->pattern === $pattern) ? "checked" : ""; ?> required>
                                <?php echo esc_html(ucfirst($pattern)); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button-link add-new-item" data-target="pattern"><?php _e("+ Add New Pattern", "wppluginfabric"); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="form-column right-column">
                <div class="action-panel">
                    <div class="action-title"><?php _e("Actions", "wppluginfabric"); ?></div>
                    <div class="action-buttons">
                        <button type="submit" id="submit" class="button button-primary"><?php echo $is_edit ? __("Update", "wppluginfabric") : __("Add", "wppluginfabric"); ?></button>
                        <a href="<?php echo admin_url("admin.php?page=wppluginfabric"); ?>" class="button"><?php _e("Cancel", "wppluginfabric"); ?></a>
                    </div>
                </div>
                
                <div class="form-field">
                    <label for="thumbnail"><?php _e("Fabric Thumbnail", "wppluginfabric"); ?></label>
                    <div class="thumbnail-container">
                        <div class="thumbnail-preview">
                            <?php if ($is_edit && $fabric->thumbnail_id) : ?>
                                <?php echo wp_get_attachment_image($fabric->thumbnail_id, "thumbnail"); ?>
                            <?php else : ?>
                                <div class="no-image"><?php _e("No image", "wppluginfabric"); ?></div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="thumbnail_id" name="thumbnail_id" value="<?php echo $is_edit ? esc_attr($fabric->thumbnail_id) : ""; ?>">
                        <div class="image-buttons">
                            <button type="button" class="button select-image"><?php _e("Select", "wppluginfabric"); ?></button>
                            <button type="button" class="button remove-image" <?php echo (!$is_edit || !$fabric->thumbnail_id) ? "style=\"display:none;\"" : ""; ?>><?php _e("Remove", "wppluginfabric"); ?></button>
                        </div>
                    </div>
                </div>
                
                <div class="form-field">
                    <label><?php _e("Fabric Type", "wppluginfabric"); ?> <span class="required">*</span></label>
                    <div class="checkbox-grid checkbox-container" id="fabric-type-container">
                        <?php foreach ($fabric_types as $type) : ?>
                        <label class="checkbox-label">
                            <input type="radio" name="fabric_type" value="<?php echo esc_attr($type); ?>" <?php echo ($is_edit && $fabric->fabric_type === $type) ? "checked" : ""; ?> required>
                            <?php echo esc_html(ucfirst($type)); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button-link add-new-item" data-target="fabric_type"><?php _e("+ Add New Type", "wppluginfabric"); ?></button>
                </div>
                
                <div class="form-field">
                    <label><?php _e("Brand", "wppluginfabric"); ?> <span class="required">*</span></label>
                    <div class="checkbox-grid checkbox-container" id="brand-container">
                        <?php foreach ($brands as $brand) : ?>
                        <label class="checkbox-label">
                            <input type="radio" name="brand_id" value="<?php echo esc_attr($brand->id); ?>" <?php echo ($is_edit && $fabric->brand_id == $brand->id) ? "checked" : ""; ?> required>
                            <?php echo esc_html($brand->name); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button-link add-new-item" data-target="brand"><?php _e("+ Add New Brand", "wppluginfabric"); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal for adding new items -->
<div id="add-new-item-modal" class="wppluginfabric-modal">
    <div class="wppluginfabric-modal-content">
        <span class="close">&times;</span>
        <h3 id="modal-title"></h3>
        <form id="add-new-item-form">
            <div class="form-field">
                <label for="new-item-name"><?php _e("Name", "wppluginfabric"); ?></label>
                <input type="text" id="new-item-name" name="new-item-name" required autofocus>
                <input type="hidden" id="item-type" name="item-type">
            </div>
            <div class="submit">
                <button type="submit" class="button button-primary"><?php _e("Add", "wppluginfabric"); ?></button>
                <button type="button" class="button modal-cancel"><?php _e("Cancel", "wppluginfabric"); ?></button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Base styles */
    .form-container {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }
    
    .form-column {
        flex: 1;
    }
    
    .left-column {
        flex-basis: 70%;
    }
    
    .right-column {
        flex-basis: 30%;
    }
    
    .form-field {
        margin-bottom: 20px;
    }
    
    .form-field label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #444;
    }
    
    .form-field input[type="text"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
        background-color: #fafafa;
    }
    
    .required {
        color: #d63638;
    }
    
    /* Color and Pattern grid layout */
    .color-pattern-grid {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    
    .color-pattern-grid .form-field {
        flex: 1;
    }
    
    /* Action panel */
    .action-panel {
        background: #f8f8f8;
        border: 1px solid #eee;
        border-radius: 3px;
        padding: 15px;
        margin-bottom: 25px;
    }
    
    .action-title {
        font-weight: 500;
        margin-bottom: 10px;
        color: #555;
        font-size: 14px;
        text-transform: uppercase;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    /* Checkbox container styles */
    /* .checkbox-container, .checkbox-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 5px;
    } */
    
    .checkbox-container {
        max-height: 120px;
        overflow-y: auto;
        border: 1px solid #eee;
        padding: 10px;
        border-radius: 3px;
        background-color: #fafafa;
    }
    
    .checkbox-grid {
        gap: 10px;
    }
    
    .checkbox-grid .checkbox-label {
        flex-basis: calc(50% - 5px);
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        margin-bottom: 0;
        font-weight: normal;
        font-size: 13px;
    }
    
    .checkbox-label input {
        margin-right: 5px;
    }
    
    /* Thumbnail styles */
    .thumbnail-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .thumbnail-preview {
        width: 100%;
        height: 80px;
        border: 1px dashed #ddd;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #fafafa;
    }
    
    .thumbnail-preview img {
        max-width: 100%;
        max-height: 100%;
    }
    
    .no-image {
        color: #999;
        font-size: 12px;
    }
    
    .image-buttons {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    /* Button styles */
    .button-link {
        background: none;
        border: none;
        color: #2271b1;
        padding: 0;
        cursor: pointer;
        text-decoration: none;
        font-size: 12px;
        text-align: left;
    }
    
    .button-link:hover {
        color: #135e96;
        text-decoration: underline;
    }
    
    /* Modal styles */
    .wppluginfabric-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.3);
    }
    
    .wppluginfabric-modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 3px;
        width: 320px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 22px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover, .close:focus {
        color: #555;
    }
    
    #add-new-item-form .submit {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Media uploader
    var mediaUploader;
    
    // Select image
    $(".select-image").on("click", function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: "<?php _e("Select Fabric Image", "wppluginfabric"); ?>",
            button: {
                text: "<?php _e("Use this image", "wppluginfabric"); ?>"
            },
            multiple: false
        });
        
        mediaUploader.on("select", function() {
            var attachment = mediaUploader.state().get("selection").first().toJSON();
            $("#thumbnail_id").val(attachment.id);
            $(".thumbnail-preview").html("<img src=\"" + attachment.url + "\">");
            $(".remove-image").show();
        });
        
        mediaUploader.open();
    });
    
    // Remove image
    $(".remove-image").on("click", function(e) {
        e.preventDefault();
        $("#thumbnail_id").val("");
        $(".thumbnail-preview").html("<div class=\"no-image\"><?php _e("No image", "wppluginfabric"); ?></div>");
        $(this).hide();
    });
    
    // Fast form submission
    $("#fabric-form").on("submit", function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        formData.push({name: "action", value: "wppluginfabric_save_fabric"});
        formData.push({name: "nonce", value: wppluginfabric.nonce});
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: formData,
            beforeSend: function() {
                $("#submit").prop("disabled", true).text("<?php _e("Saving...", "wppluginfabric"); ?>");
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = "<?php echo admin_url("admin.php?page=wppluginfabric"); ?>";
                } else {
                    alert(response.data);
                    $("#submit").prop("disabled", false).text("<?php echo $is_edit ? __("Update", "wppluginfabric") : __("Add", "wppluginfabric"); ?>");
                }
            },
            error: function() {
                alert("<?php _e("An error occurred. Please try again.", "wppluginfabric"); ?>");
                $("#submit").prop("disabled", false).text("<?php echo $is_edit ? __("Update", "wppluginfabric") : __("Add", "wppluginfabric"); ?>");
            }
        });
    });
    
    // Fast modal functionality
    var modal = $("#add-new-item-modal");
    
    // Open modal when Add New button is clicked
    $(".add-new-item").on("click", function() {
        var target = $(this).data("target");
        var titleText = "<?php _e("Add New ", "wppluginfabric"); ?>";
        
        switch(target) {
            case "fabric_type":
                titleText += "<?php _e("Fabric Type", "wppluginfabric"); ?>";
                break;
            case "brand":
                titleText += "<?php _e("Brand", "wppluginfabric"); ?>";
                break;
            case "color":
                titleText += "<?php _e("Color", "wppluginfabric"); ?>";
                break;
            case "pattern":
                titleText += "<?php _e("Pattern", "wppluginfabric"); ?>";
                break;
        }
        
        $("#modal-title").text(titleText);
        $("#item-type").val(target);
        $("#new-item-name").val("").focus();
        modal.css("display", "block");
    });
    
    // Close modal
    $(".close, .modal-cancel").on("click", function() {
        modal.css("display", "none");
    });
    
    // When the user clicks anywhere outside of the modal, close it
    $(window).on("click", function(event) {
        if (event.target == modal[0]) {
            modal.css("display", "none");
        }
    });
    
    // Fast add new item
    $("#add-new-item-form").on("submit", function(e) {
        e.preventDefault();
        
        var itemType = $("#item-type").val();
        var itemName = $("#new-item-name").val();
        
        if (!itemName) {
            return;
        }
        
        var data = {
            action: 'wppluginfabric_add_' + itemType,
            nonce: wppluginfabric.nonce,
            name: itemName
        };
        
        $.ajax({
            url: ajaxurl,
            type: "POST",
            data: data,
            beforeSend: function() {
                $("#add-new-item-form button[type=submit]").prop("disabled", true).text("<?php _e("Adding...", "wppluginfabric"); ?>");
            },
            success: function(response) {
                if (response.success) {
                    // Add new option to the appropriate container
                    var container, html;
                    
                    if (itemType === "brand") {
                        container = $("#brand-container");
                        html = '<label class="checkbox-label"><input type="radio" name="brand_id" value="' + 
                               response.data.id + '" checked required>' + itemName + '</label>';
                    } else {
                        container = $("#" + itemType + "-container");
                        html = '<label class="checkbox-label"><input type="radio" name="' + itemType + 
                               '" value="' + itemName + '" checked required>' + 
                               itemName.charAt(0).toUpperCase() + itemName.slice(1) + '</label>';
                    }
                    
                    container.append(html);
                    
                    // Close the modal and reset the form
                    modal.css("display", "none");
                    $("#new-item-name").val("");
                    $("#add-new-item-form button[type=submit]").prop("disabled", false).text("<?php _e("Add", "wppluginfabric"); ?>");
                } else {
                    alert(response.data);
                    $("#add-new-item-form button[type=submit]").prop("disabled", false).text("<?php _e("Add", "wppluginfabric"); ?>");
                }
            },
            error: function() {
                alert("<?php _e("An error occurred", "wppluginfabric"); ?>");
                $("#add-new-item-form button[type=submit]").prop("disabled", false).text("<?php _e("Add", "wppluginfabric"); ?>");
            }
        });
    });
    
    // Speed optimization - prevent unnecessary reflows
    $(window).on('load', function() {
        setTimeout(function() {
            $('body').addClass('wppluginfabric-loaded');
        }, 100);
    });
});
</script>