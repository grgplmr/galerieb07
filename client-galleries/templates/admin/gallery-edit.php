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
    <div id="cg-upload-list" class="cg-upload-list">
        <?php foreach ($attachments as $attachment) :
            $thumb = wp_get_attachment_image_src($attachment->ID, 'thumbnail');
            if ($thumb) : ?>
                <img src="<?php echo esc_url($thumb[0]); ?>" alt="" />
            <?php endif;
        endforeach; ?>
    </div>
</div>
