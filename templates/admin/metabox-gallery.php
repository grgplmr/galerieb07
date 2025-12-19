<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$share_link = add_query_arg(
    array(
        'cg_gallery' => $post->ID,
        'cg_token'   => $token,
    ),
    home_url( '/' )
);
?>
<div class="cg-metabox">
    <p><strong><?php esc_html_e( 'Shortcode', 'client-galleries' ); ?>:</strong> <code>[client_gallery id="<?php echo esc_attr( $post->ID ); ?>"]</code></p>
    <p><strong><?php esc_html_e( 'Share link', 'client-galleries' ); ?>:</strong> <a href="<?php echo esc_url( $share_link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $share_link ); ?></a></p>

    <div class="cg-upload">
        <label for="cg-upload-input"><?php esc_html_e( 'Select images', 'client-galleries' ); ?></label>
        <input type="file" id="cg-upload-input" multiple accept="image/*" />
        <button type="button" class="button button-primary" id="cg-start-upload"><?php esc_html_e( 'Start upload', 'client-galleries' ); ?></button>
        <div id="cg-upload-status"></div>
        <div id="cg-upload-progress" class="cg-progress"><span class="cg-progress-bar"></span></div>
        <ul id="cg-upload-queue"></ul>
    </div>

    <div class="cg-gallery-preview" id="cg-gallery-preview"></div>
</div>
