<?php
$images = $data['images'] ?? [];
$selection = $data['selection'] ?? [];
$email = $data['email'] ?? '';
$gallery = $data['gallery'] ?? null;
$cookie_key = $data['cookieKey'] ?? '';
$max = (int) cg_get_option('stars_max', 5);
?>
<div class="cg-gallery-wrapper">
    <p><?php printf(esc_html__('Gallery for: %s', 'client-galleries'), esc_html($gallery ? $gallery->post_title : '')); ?></p>
    <p><?php printf(esc_html__('Logged as: %s', 'client-galleries'), esc_html($email)); ?></p>
    <div class="cg-gallery-grid">
        <?php foreach ($images as $index => $image) :
            $image_id = (int) $image->ID;
            $current = isset($selection[$image_id]) ? (int) $selection[$image_id] : 0;
            $src = wp_get_attachment_image_src($image_id, 'large');
            $full = wp_get_attachment_image_src($image_id, 'full');
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            ?>
            <div class="cg-gallery-item" data-id="<?php echo esc_attr($image_id); ?>" data-index="<?php echo esc_attr($index); ?>" data-full="<?php echo esc_url($full ? $full[0] : ($src[0] ?? '')); ?>">
                <?php if ($src) : ?>
                    <img src="<?php echo esc_url($src[0]); ?>" loading="lazy" alt="<?php echo esc_attr($alt); ?>" />
                <?php endif; ?>
                <div class="cg-stars" data-rating="<?php echo esc_attr($current); ?>" aria-label="<?php esc_attr_e('Rate this image', 'client-galleries'); ?>">
                    <?php for ($i = 1; $i <= $max; $i++) : ?>
                        <span class="cg-star <?php echo $i <= $current ? 'active' : ''; ?>" data-value="<?php echo esc_attr($i); ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="cg-submit-area">
        <button id="cg-submit-selection" data-label="<?php esc_attr_e('Send my selection', 'client-galleries'); ?>" data-loading="<?php esc_attr_e('Sending...', 'client-galleries'); ?>"><?php esc_html_e('Send my selection', 'client-galleries'); ?></button>
        <div id="cg-selection-message"></div>
    </div>
</div>
<?php include CG_PLUGIN_DIR . 'templates/front/thank-you.php'; ?>
