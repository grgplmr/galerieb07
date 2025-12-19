<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$share_link = ! empty( $token )
    ? add_query_arg(
        array(
            'cg_gallery' => $post->ID,
            'cg_token'   => $token,
        ),
        home_url( '/' )
    )
    : '';
?>
<div class="cg-metabox">
    <?php if ( isset( $is_new ) && $is_new ) : ?>
        <div class="notice notice-info inline">
            <p><?php esc_html_e( 'Enregistrez la galerie pour générer un lien partageable.', 'client-galleries' ); ?></p>
        </div>
    <?php endif; ?>

    <p><strong><?php esc_html_e( 'Shortcode', 'client-galleries' ); ?>:</strong> <?php if ( ! empty( $post->ID ) ) : ?><code>[client_gallery id="<?php echo esc_attr( $post->ID ); ?>"]</code><?php else : ?><em><?php esc_html_e( 'Disponible après enregistrement.', 'client-galleries' ); ?></em><?php endif; ?></p>
    <?php if ( ! empty( $token ) ) : ?>
        <p><strong><?php esc_html_e( 'Share link', 'client-galleries' ); ?>:</strong> <a href="<?php echo esc_url( $share_link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $share_link ); ?></a></p>
    <?php endif; ?>

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
