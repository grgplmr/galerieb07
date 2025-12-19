<?php
$attachments = get_children([
    'post_parent'    => $data['post_id'],
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'orderby'        => 'menu_order ID',
    'order'          => 'ASC',
]);
?>
<div class="cg-meta-row">
    <p><?php esc_html_e('Share this gallery using the shortcode or direct link.', 'client-galleries'); ?></p>
    <p><strong><?php esc_html_e('Shortcode:', 'client-galleries'); ?></strong> <code><?php echo esc_html($data['shortcode']); ?></code></p>
    <p><strong><?php esc_html_e('Link:', 'client-galleries'); ?></strong> <a href="<?php echo esc_url($data['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($data['link']); ?></a></p>
</div>
<div class="cg-meta-row cg-upload-area">
    <label for="cg-upload-input"><strong><?php esc_html_e('Upload images', 'client-galleries'); ?></strong></label><br />
    <input type="file" id="cg-upload-input" data-gallery="<?php echo esc_attr($data['post_id']); ?>" multiple accept="image/*" />
    <div class="cg-upload-progress-wrapper">
        <div class="cg-upload-progress-bar-container" aria-hidden="true">
            <div id="cg-upload-progress-bar" class="cg-upload-progress-bar"></div>
        </div>
        <div class="cg-upload-progress-meta">
            <span id="cg-upload-progress-text">0%</span>
            <span id="cg-upload-progress-count">0 / 0</span>
            <span id="cg-upload-summary"></span>
        </div>
    </div>
    <ul id="cg-upload-queue" class="cg-upload-queue" aria-live="polite" aria-label="<?php esc_attr_e('Upload queue', 'client-galleries'); ?>"></ul>
    <h4><?php esc_html_e('Images in this gallery', 'client-galleries'); ?></h4>
    <div id="cg-upload-gallery" class="cg-upload-list">
        <?php foreach ($attachments as $attachment) :
            $thumb = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
            if ($thumb) : ?>
                <img src="<?php echo esc_url($thumb[0]); ?>" alt="" />
            <?php endif;
        endforeach; ?>
    </div>
</div>
