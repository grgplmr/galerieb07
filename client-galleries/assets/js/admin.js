jQuery(function ($) {
    const fileInput = $('#cg-upload-input');
    const queueList = $('#cg-upload-queue');
    const galleryList = $('#cg-upload-gallery');
    const progressBar = $('#cg-upload-progress-bar');
    const progressText = $('#cg-upload-progress-text');
    const progressCount = $('#cg-upload-progress-count');
    const summary = $('#cg-upload-summary');

    let queue = [];
    let isUploading = false;
    let stats = {
        total: 0,
        completed: 0,
        success: 0,
        error: 0,
    };

    fileInput.on('change', function () {
        const files = Array.from(this.files || []);
        if (!files.length) {
            return;
        }

        files.forEach((file) => enqueueFile(file));
        fileInput.val('');
        updateSummary();

        if (!isUploading) {
            uploadNext();
        }
    });

    function enqueueFile(file) {
        const id = 'cg-file-' + Date.now() + '-' + Math.random().toString(16).slice(2);
        queue.push({
            id,
            file,
            status: 'pending',
            progress: 0,
        });
        stats.total += 1;

        const item = $(
            '<li class="cg-upload-queue-item" data-id="' + id + '">' +
            '<div class="cg-upload-queue-header">' +
            '<span class="cg-upload-filename"></span>' +
            '<span class="cg-upload-status" aria-live="polite"></span>' +
            '</div>' +
            '<div class="cg-upload-progress-file" aria-hidden="true"><span></span></div>' +
            '<div class="cg-upload-error" role="alert"></div>' +
            '</li>'
        );
        item.find('.cg-upload-filename').text(file.name);
        item.find('.cg-upload-status').text(cgAdmin.strings.pending || 'Pending');
        queueList.append(item);
        updateGlobalProgress();
    }

    function uploadNext() {
        const next = queue.find((item) => item.status === 'pending');
        if (!next) {
            isUploading = false;
            updateSummary();
            return;
        }
        isUploading = true;
        uploadFile(next);
    }

    function uploadFile(item) {
        setItemStatus(item, 'uploading');
        setItemProgress(item, 0);

        const formData = new FormData();
        formData.append('action', 'cg_admin_upload_single');
        formData.append('nonce', cgAdmin.nonce);
        formData.append('gallery_id', fileInput.data('gallery'));
        formData.append('cg_file', item.file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', cgAdmin.ajaxUrl, true);
        xhr.responseType = 'json';

        xhr.upload.onprogress = function (event) {
            if (!event.lengthComputable) {
                return;
            }
            const percent = event.loaded / event.total;
            setItemProgress(item, percent);
            updateGlobalProgress();
        };

        xhr.onerror = function () {
            handleItemError(item, cgAdmin.strings.networkError || 'Network error');
            updateSummary();
            setTimeout(uploadNext, 20);
        };

        xhr.onload = function () {
            const resp = xhr.response;
            if (xhr.status !== 200 || !resp) {
                handleItemError(item, cgAdmin.strings.serverError || 'Upload failed');
                return;
            }

            if (resp.success) {
                item.progress = 1;
                setItemProgress(item, 1);
                setItemStatus(item, 'completed');
                stats.success += 1;
                stats.completed += 1;
                appendThumbnail(resp.data);
            } else {
                const message = (resp.data && resp.data.message) ? resp.data.message : (cgAdmin.strings.uploadError || 'Upload failed');
                handleItemError(item, message);
            }

            updateGlobalProgress();
            updateSummary();
            setTimeout(uploadNext, 20);
        };

        xhr.send(formData);
    }

    function handleItemError(item, message) {
        setItemStatus(item, 'error');
        setItemProgress(item, 0);
        stats.error += 1;
        stats.completed += 1;

        const row = queueList.find('[data-id="' + item.id + '"]');
        row.find('.cg-upload-error').text(message);
        updateGlobalProgress();
    }

    function setItemStatus(item, status) {
        item.status = status;
        const row = queueList.find('[data-id="' + item.id + '"]');
        const statusLabel = row.find('.cg-upload-status');
        statusLabel.removeClass().addClass('cg-upload-status').addClass('status-' + status);

        switch (status) {
            case 'uploading':
                statusLabel.text(cgAdmin.strings.uploading || 'Uploading');
                break;
            case 'completed':
                statusLabel.text(cgAdmin.strings.completed || 'Completed');
                break;
            case 'error':
                statusLabel.text(cgAdmin.strings.error || 'Error');
                break;
            default:
                statusLabel.text(cgAdmin.strings.pending || 'Pending');
        }
    }

    function setItemProgress(item, percent) {
        item.progress = percent;
        const row = queueList.find('[data-id="' + item.id + '"]');
        const bar = row.find('.cg-upload-progress-file span');
        bar.css('width', Math.min(100, Math.round(percent * 100)) + '%');
    }

    function updateGlobalProgress() {
        const current = queue.find((item) => item.status === 'uploading');
        const currentProgress = current ? current.progress : 0;
        const total = stats.total || 1;
        const percent = ((stats.completed + currentProgress) / total) * 100;

        progressBar.css('width', Math.min(100, percent) + '%');
        progressText.text(Math.round(Math.min(100, percent)) + '%');
        progressCount.text(stats.completed + ' / ' + stats.total);
    }

    function updateSummary() {
        if (!stats.total) {
            summary.text('');
            return;
        }
        summary.text(
            (cgAdmin.strings.summary || 'Uploads:') + ' ' +
            stats.success + ' ' + (cgAdmin.strings.done || 'completed') + ', ' +
            stats.error + ' ' + (cgAdmin.strings.failed || 'failed')
        );
    }

    function appendThumbnail(data) {
        if (!data || (!data.thumb_url && !data.full_url)) {
            return;
        }

        const src = data.thumb_url || data.full_url;
        const img = $('<img />', {
            src: src,
            alt: data.filename || '',
            loading: 'lazy',
        });
        galleryList.append(img);
    }
});
