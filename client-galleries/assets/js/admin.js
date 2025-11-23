jQuery(function ($) {
    const fileInput = $('#cg-upload-input');
    const list = $('#cg-upload-list');

    fileInput.on('change', function () {
        if (!this.files.length) {
            return;
        }
        const formData = new FormData();
        formData.append('action', 'cg_admin_upload_images');
        formData.append('nonce', cgAdmin.nonce);
        formData.append('gallery_id', fileInput.data('gallery'));
        for (let i = 0; i < this.files.length; i++) {
            formData.append('cg_files[' + i + ']', this.files[i]);
        }

        list.text('Uploading...');
        $.ajax({
            url: cgAdmin.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (resp) {
                list.empty();
                if (resp.success && resp.data.attachments) {
                    resp.data.attachments.forEach(function (item) {
                        const img = $('<img />', { src: item.url, loading: 'lazy' });
                        list.append(img);
                    });
                } else {
                    list.text(resp.data || 'Upload failed');
                }
            },
            error: function () {
                list.text('Upload failed');
            }
        });
    });
});
