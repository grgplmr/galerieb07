<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$var_name = 'cgGalleryData_' . $data['galleryId'];
?>
<div class="cg-front" data-gallery-id="<?php echo esc_attr( $data['galleryId'] ); ?>" data-token="<?php echo esc_attr( $data['token'] ); ?>" data-var="<?php echo esc_attr( $var_name ); ?>">
    <div class="cg-email-gate" data-state="closed">
        <p><?php esc_html_e( 'Enter your email to view your gallery', 'client-galleries' ); ?></p>
        <input type="email" id="cg-email-input" placeholder="email@example.com" />
        <button type="button" class="cg-email-submit"><?php esc_html_e( 'Access gallery', 'client-galleries' ); ?></button>
    </div>

    <div class="cg-gallery-wrapper" style="display:none;">
        <div class="cg-gallery-actions">
            <span class="cg-email-display"></span>
            <button type="button" class="cg-change-email"><?php esc_html_e( 'Change email', 'client-galleries' ); ?></button>
            <button type="button" class="cg-submit-selection"><?php esc_html_e( 'Send selection', 'client-galleries' ); ?></button>
            <span class="cg-submit-status"></span>
        </div>
        <div class="cg-grid" id="cg-grid"></div>
    </div>

    <div class="cg-lightbox" style="display:none;">
        <div class="cg-lightbox-overlay"></div>
        <div class="cg-lightbox-content">
            <button class="cg-lightbox-close" aria-label="Close">&times;</button>
            <button class="cg-lightbox-prev" aria-label="Previous">&#10094;</button>
            <img src="" alt="" class="cg-lightbox-image" />
            <button class="cg-lightbox-next" aria-label="Next">&#10095;</button>
            <div class="cg-lightbox-rating"></div>
        </div>
    </div>
</div>
