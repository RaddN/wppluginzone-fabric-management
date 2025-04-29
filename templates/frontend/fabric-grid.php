<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}
?>

<?php if (empty($fabrics_by_brand)) : ?>
    <div class="no-fabrics-found">
        <p><?php _e("No fabrics found matching your criteria.", "wppluginfabric"); ?></p>
    </div>
<?php else : ?>
    <?php foreach ($fabrics_by_brand as $brand_id => $brand_data) : ?>
        <div class="fabric-brand-section">
            <h3 class="brand-title"><?php echo esc_html($brand_data["brand"]->name); ?></h3>
            <div class="fabric-grid-items">
                <?php foreach ($brand_data["fabrics"] as $fabric) : ?>
                    <div class="fabric-item">
                        <div class="fabric-thumbnail">
                            <?php if ($fabric->thumbnail_id) : ?>
                                <?php echo wp_get_attachment_image($fabric->thumbnail_id, "medium"); ?>
                            <?php else : ?>
                                <div class="no-image"><?php _e("No Image", "wppluginfabric"); ?></div>
                            <?php endif; ?>
                        </div>
                        <h4 class="fabric-title"><?php echo esc_html($fabric->title); ?></h4>
                        <div class="fabric-description">
                            <?php echo wpautop($fabric->description); ?>
                        </div>
                        <div class="fabric-meta">
                            <span class="fabric-color"><?php echo esc_html(ucfirst($fabric->color)); ?></span>
                            <span class="fabric-pattern"><?php echo esc_html(ucfirst($fabric->pattern)); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>