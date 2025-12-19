jQuery(function ($) {
    const config = window.cgAdmin || {};
    const strings = config.strings || {};

    const fileInput = $('#cg-upload-input');
    const startButton = $('#cg-upload-start');
    const queueList = $('#cg-upload-queue');
    const previewList = $('#cg-upload-list');
    const globalBar = $('#cg-upload-global-progress .cg-upload-progress-bar');
    const progressText = $('#cg-upload-progress-text');
    const progressCount = $('#cg-upload-progress-count');
    const statusMessage = $('#cg-upload-status');

    const state = {
        items: [],
        uploading: false,
    };

    fileInput.on('change', function () {
        const files = Array.from(this.files || []);
        if (!files.length) {
            return;
        }
        resetQueueIfFinished();
        addFilesToQueue(files);
        this.value = '';
        updateGlobalProgress();
        if (!state.uploading) {
            startUploads('auto');
        }
    });

    startButton.on('click', function (event) {
        event.preventDefault();
        startUploads('manual');
    });

    function addFilesToQueue(files) {
        files.forEach((file) => {
            const item = {
                id: 'cg-file-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                file,
                status: 'pending',
                progress: 0,
            };
            state.items.push(item);
            renderQueueItem(item);
        });
        updateStats();
    }

    function renderQueueItem(item) {
        const itemEl = $(
            '<li class="cg-upload-queue-item" data-id="' + item.id + '">' +
                '<div class="cg-upload-queue-header">' +
                    '<span class="cg-upload-filename"></span>' +
                    '<span class="cg-upload-status" aria-live="polite"></span>' +
                '</div>' +
                '<div class="cg-upload-progress-file" aria-hidden="true"><span></span></div>' +
                '<div class="cg-upload-error" role="alert"></div>' +
            '</li>'
        );
        itemEl.find('.cg-upload-filename').text(item.file.name);
        itemEl.find('.cg-upload-status').text(strings.pending || 'Pending');
        queueList.append(itemEl);
    }

    function startUploads(source) {
        if (state.uploading) {
            return;
        }
        if (!state.items.some((item) => item.status === 'pending')) {
            setStatus(strings.noFiles || 'Select files first.');
            return;
        }
        setStatus(source === 'manual' ? strings.start || 'Starting upload...' : '');
        state.uploading = true;
        startButton.prop('disabled', true);
        uploadNext();
    }

    function uploadNext() {
        const next = state.items.find((item) => item.status === 'pending');
        if (!next) {
            finalizeAll();
            return;
        }
        uploadFile(next);
    }

    function uploadFile(item) {
        setItemStatus(item, 'uploading');
        setItemProgress(item, 0);
        updateGlobalProgress();

        const formData = new FormData();
        formData.append('action', 'cg_admin_upload_images');
        formData.append('nonce', config.nonce || '');
        formData.append('gallery_id', getGalleryId());
        formData.append('cg_files[0]', item.file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', getAjaxUrl(), true);
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
            handleItemError(item, strings.networkError || 'Network error');
            finalizeFile();
        };

        xhr.onload = function () {
            let resp = xhr.response;
            if (!resp && xhr.responseText) {
                try {
                    resp = JSON.parse(xhr.responseText);
                } catch (e) {
                    resp = null;
                }
            }

            if (xhr.status !== 200 || !resp) {
                handleItemError(item, strings.serverError || 'Upload failed');
                finalizeFile();
                return;
            }

            if (resp.success && resp.data && Array.isArray(resp.data.attachments)) {
                setItemProgress(item, 1);
                setItemStatus(item, 'completed');
                appendAttachments(resp.data.attachments);
            } else {
                const message = resp.data && resp.data.message ? resp.data.message : (strings.serverError || 'Upload failed');
                handleItemError(item, message);
            }

            finalizeFile();
        };

        xhr.send(formData);

        function finalizeFile() {
            updateStats();
            updateGlobalProgress();
            setTimeout(uploadNext, 30);
        }
    }

    function handleItemError(item, message) {
        setItemStatus(item, 'error');
        setItemProgress(item, 0);

        const row = queueList.find('[data-id="' + item.id + '"]');
        row.find('.cg-upload-error').text(message);
        setStatus(message);
    }

    function setItemStatus(item, status) {
        item.status = status;
        const row = queueList.find('[data-id="' + item.id + '"]');
        const statusLabel = row.find('.cg-upload-status');
        statusLabel.removeClass().addClass('cg-upload-status').addClass('status-' + status);

        switch (status) {
            case 'uploading':
                statusLabel.text(strings.uploading || 'Uploading');
                break;
            case 'completed':
                statusLabel.text(strings.completed || 'Completed');
                break;
            case 'error':
                statusLabel.text(strings.error || 'Error');
                break;
            default:
                statusLabel.text(strings.pending || 'Pending');
        }
    }

    function setItemProgress(item, percent) {
        item.progress = percent;
        const row = queueList.find('[data-id="' + item.id + '"]');
        const bar = row.find('.cg-upload-progress-file span');
        bar.css('width', Math.min(100, Math.round(percent * 100)) + '%');
    }

    function updateGlobalProgress() {
        if (!state.items.length) {
            progressText.text('0%');
            progressCount.text('0 / 0');
            globalBar.css('width', '0%');
            return;
        }

        const total = state.items.length;
        const completed = state.items.filter((item) => item.status === 'completed' || item.status === 'error').length;
        const current = state.items.find((item) => item.status === 'uploading');
        const currentProgress = current ? current.progress : 0;
        const percent = Math.min(100, ((completed + currentProgress) / total) * 100);

        globalBar.css('width', percent + '%');
        progressText.text(Math.round(percent) + '%');
        progressCount.text(completed + ' / ' + total);
    }

    function updateStats() {
        const successCount = state.items.filter((item) => item.status === 'completed').length;
        const errorCount = state.items.filter((item) => item.status === 'error').length;
        if (!state.items.length) {
            setStatus('');
            return;
        }
        const summary = (strings.summary || 'Uploads:') + ' ' + successCount + ' completed, ' + errorCount + ' failed';
        setStatus(summary);
    }

    function finalizeAll() {
        state.uploading = false;
        startButton.prop('disabled', false);
        setStatus(strings.completed || 'Uploads finished');
    }

    function resetQueueIfFinished() {
        if (!state.items.length) {
            return;
        }
        const allDone = state.items.every((item) => item.status === 'completed' || item.status === 'error');
        if (allDone && !state.uploading) {
            state.items = [];
            queueList.empty();
            updateGlobalProgress();
            setStatus('');
        }
    }

    function appendAttachments(attachments) {
        attachments.forEach((item) => {
            if (!item || !item.url) {
                return;
            }
            const img = $('<img />', { src: item.url, loading: 'lazy' });
            previewList.prepend(img);
        });
    }

    function setStatus(message) {
        if (!statusMessage.length) {
            return;
        }
        statusMessage.text(message || '');
    }

    function getAjaxUrl() {
        const url = config.ajaxUrl || config.ajax_url || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
        if (!url) {
            console.warn('[CG] Missing ajax URL for upload');
        }
        return url;
    }

    function getGalleryId() {
        const fromConfig = config.galleryId || config.gallery_id || config.post_id;
        const fromInput = fileInput.data('gallery');
        return fromInput || fromConfig || '';
    }
});
