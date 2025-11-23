<div class="cg-email-gate">
    <h3><?php esc_html_e('Please enter your email to view the gallery', 'client-galleries'); ?></h3>
    <form id="cg-email-form">
        <label for="cg-email-input" class="screen-reader-text"><?php esc_html_e('Email', 'client-galleries'); ?></label>
        <input type="email" id="cg-email-input" required placeholder="<?php esc_attr_e('your@email.com', 'client-galleries'); ?>" />
        <button type="submit"><?php esc_html_e('Access gallery', 'client-galleries'); ?></button>
    </form>
</div>
