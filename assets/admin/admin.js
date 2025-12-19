(function ($) {
    let queue = [];
    let uploading = false;
    let uploadedCount = 0;

    function renderExisting() {
        if (!cgAdminData.existing) return;
        const container = $('#cg-gallery-preview');
        cgAdminData.existing.forEach((item) => {
            const thumb = $('<div class="cg-thumb"></div>');
            thumb.append($('<img />').attr('src', item.thumb).attr('alt', item.name));
            thumb.append($('<span></span>').text(item.name));
            container.append(thumb);
        });
    }

    function updateQueueStatus() {
        const status = queue.length ? queue.length + ' ' + cgAdminData.strings.ready : '';
        $('#cg-upload-status').text(status);
    }

    function addToQueue(files) {
        for (let i = 0; i < files.length; i++) {
            queue.push({ file: files[i], status: 'pending' });
            $('#cg-upload-queue').append('<li>' + files[i].name + ' (' + Math.round(files[i].size / 1024) + 'kb)</li>');
        }
        updateQueueStatus();
    }

    function appendPreview(data) {
        const container = $('#cg-gallery-preview');
        const thumb = $('<div class="cg-thumb"></div>');
        thumb.append($('<img />').attr('src', data.thumb_url).attr('alt', data.filename));
        thumb.append($('<span></span>').text(data.filename));
        container.append(thumb);
    }

    function showError(message, xhr) {
        let detail = message;
        if (xhr) {
            detail += ' (status ' + xhr.status + ') ' + (xhr.responseText || '');
        }
        $('<div class="notice notice-error"><p>' + detail + '</p></div>').insertAfter('#cg-upload-status');
    }

    function setProgress(percent) {
        $('#cg-upload-progress .cg-progress-bar').css('width', percent + '%');
    }

    function uploadNext() {
        if (uploading || queue.length === 0) {
            return;
        }
        uploading = true;
        const item = queue.shift();
        const formData = new FormData();
        formData.append('action', 'cg_admin_upload_single');
        formData.append('nonce', cgAdminData.nonce);
        formData.append('gallery_id', cgAdminData.gallery.id);
        formData.append('file', item.file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', cgAdminData.ajaxUrl, true);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((uploadedCount / (uploadedCount + queue.length + 1) + (e.loaded / e.total) / (uploadedCount + queue.length + 1)) * 100);
                setProgress(percent);
            }
        };

        xhr.onload = function () {
            uploading = false;
            let response = {};
            try {
                response = JSON.parse(xhr.responseText);
            } catch (err) {
                showError(cgAdminData.strings.uploadError, xhr);
                processNext();
                return;
            }

            if (xhr.status !== 200 || !response.success) {
                const message = response.data && response.data.message ? response.data.message : cgAdminData.strings.uploadError;
                showError(message, xhr);
            } else {
                appendPreview(response.data);
                uploadedCount++;
            }
            processNext();
        };

        xhr.onerror = function () {
            uploading = false;
            showError(cgAdminData.strings.uploadError, xhr);
            processNext();
        };

        xhr.send(formData);
    }

    function processNext() {
        updateQueueStatus();
        if (queue.length) {
            uploadNext();
        } else {
            setProgress(100);
        }
    }

    $(document).ready(function () {
        renderExisting();

        $('#cg-upload-input').on('change', function (e) {
            const files = e.target.files;
            if (!files || !files.length) return;
            addToQueue(files);
        });

        $('#cg-start-upload').on('click', function () {
            if (!queue.length) {
                alert(cgAdminData.strings.startUpload);
                return;
            }
            uploadNext();
        });
    });
})(jQuery);
