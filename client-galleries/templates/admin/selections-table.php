<div class="wrap">
    <h1><?php esc_html_e('Client Selections', 'client-galleries'); ?></h1>
    <form method="get">
        <input type="hidden" name="post_type" value="client_gallery" />
        <input type="hidden" name="page" value="cg-selections" />
        <label for="cg-gallery-id"><?php esc_html_e('Choose gallery', 'client-galleries'); ?></label>
        <select name="gallery_id" id="cg-gallery-id" onchange="this.form.submit()">
            <option value="0"><?php esc_html_e('Select a gallery', 'client-galleries'); ?></option>
            <?php foreach ($data['galleries'] as $gallery) : ?>
                <option value="<?php echo esc_attr($gallery->ID); ?>" <?php selected($data['gallery_id'], $gallery->ID); ?>><?php echo esc_html($gallery->post_title); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($data['gallery_id']) : ?>
        <p>
            <a class="button" href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=cg_export_csv&gallery_id=' . $data['gallery_id']), 'cg_export_csv'); ?>"><?php esc_html_e('Export CSV', 'client-galleries'); ?></a>
        </p>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email', 'client-galleries'); ?></th>
                    <th><?php esc_html_e('Image ID', 'client-galleries'); ?></th>
                    <th><?php esc_html_e('Filename', 'client-galleries'); ?></th>
                    <th><?php esc_html_e('Rating', 'client-galleries'); ?></th>
                    <th><?php esc_html_e('Updated', 'client-galleries'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['rows']) : foreach ($data['rows'] as $row) :
                    $file = get_attached_file((int) $row['image_id']);
                    ?>
                    <tr>
                        <td><?php echo esc_html($row['email_raw']); ?></td>
                        <td><?php echo esc_html($row['image_id']); ?></td>
                        <td><?php echo esc_html($file ? basename($file) : ''); ?></td>
                        <td><?php echo esc_html($row['rating']); ?></td>
                        <td><?php echo esc_html($row['updated_at']); ?></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5"><?php esc_html_e('No selections yet.', 'client-galleries'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
