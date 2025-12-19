jQuery(function ($) {
    const config = window.cgAdmin || window.CG_ADMIN || {};
    const strings = config.strings || {};
    config.strings = strings;
    window.cgAdmin = config;
    console.log('[CG] admin.js loaded', config);

    const fileInput = $('#cg-upload-input');
    const startButton = $('#cg-upload-start');
    const queueList = $('#cg-upload-queue');
    const galleryList = $('#cg-gallery-previews');
    const globalBar = $('#cg-upload-global-progress .cg-upload-progress-bar');
    const progressText = $('#cg-upload-progress-text');
    const progressCount = $('#cg-upload-progress-count');
    const summary = $('#cg-upload-summary');
    const statusMessage = $('#cg-upload-status');

    const state = {
        queue: [],
        uploading: false,
    };

    fileInput.on('change', function () {
        const files = Array.from(this.files || []);
        if (!files.length) {
            return;
        }

        addFilesToQueue(files);
        fileInput.val('');
        updateStats();
        updateGlobalProgress();
        if (!state.uploading) {
            startUploads();
        }
    });

    startButton.on('click', function () {
        startUploads();
    });

    document.addEventListener('click', function (event) {
        const btn = event.target.closest('#cg-upload-start');
        if (!btn) {
            return;
        }
        event.preventDefault();
        console.log('[CG] Start upload clicked');
        if (statusMessage.length) {
            statusMessage.text('Start upload clicked');
        }
        sendPing();
        startUploads();
    });

    function startUploads() {
        if (state.uploading) {
            return;
        }
        if (!state.queue.some((item) => item.status === 'pending')) {
            return;
        }
        state.uploading = true;
        startButton.prop('disabled', true);
        uploadNext();
    }

    function addFilesToQueue(files) {
        files.forEach((file) => {
            const id = 'cg-file-' + Date.now() + '-' + Math.random().toString(16).slice(2);
            const item = {
                id,
                file,
                status: 'pending',
                progress: 0,
            };
            state.queue.push(item);
            renderQueueItem(item);
        });
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
        itemEl.find('.cg-upload-status').text(strings.queued || strings.pending || 'Pending');
        queueList.append(itemEl);
    }

    function uploadNext() {
        const next = state.queue.find((item) => item.status === 'pending');
        if (!next) {
            state.uploading = false;
            startButton.prop('disabled', false);
            updateStats();
            updateGlobalProgress();
            return;
        }
        uploadFile(next);
    }

    function uploadFile(item) {
        setItemStatus(item, 'uploading');
        setItemProgress(item, 0);
        updateGlobalProgress();

        const formData = new FormData();
        formData.append('action', 'cg_admin_upload_single');
        formData.append('_ajax_nonce', config.nonce_upload);
        formData.append('gallery_id', config.gallery_id);
        formData.append('file', item.file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', config.ajax_url, true);
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
            finalizeUpload();
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
                finalizeUpload();
                return;
            }

            if (resp.success) {
                setItemProgress(item, 1);
                setItemStatus(item, 'completed');
                appendThumbnail(resp.data);
            } else {
                const message = (resp.data && resp.data.message) ? resp.data.message : (strings.uploadError || 'Upload failed');
                handleItemError(item, message);
            }

            finalizeUpload();
        };

        xhr.send(formData);
    }

    function finalizeUpload() {
        updateStats();
        updateGlobalProgress();
        setTimeout(uploadNext, 30);
    }

    function handleItemError(item, message) {
        setItemStatus(item, 'error');
        setItemProgress(item, 0);

        const row = queueList.find('[data-id="' + item.id + '"]');
        row.find('.cg-upload-error').text(message);
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
                statusLabel.text(strings.queued || strings.pending || 'Pending');
        }
    }

    function setItemProgress(item, percent) {
        item.progress = percent;
        const row = queueList.find('[data-id="' + item.id + '"]');
        const bar = row.find('.cg-upload-progress-file span');
        bar.css('width', Math.min(100, Math.round(percent * 100)) + '%');
    }

    function updateGlobalProgress() {
        const total = state.queue.length || 1;
        const completedPortion = state.queue.reduce((carry, item) => {
            if (item.status === 'completed' || item.status === 'error') {
                return carry + 1;
            }
            if (item.status === 'uploading') {
                return carry + item.progress;
            }
            return carry;
        }, 0);

        const percent = Math.min(100, (completedPortion / total) * 100);
        const finished = state.queue.filter((item) => item.status === 'completed' || item.status === 'error').length;

        globalBar.css('width', percent + '%');
        progressText.text(Math.round(percent) + '%');
        progressCount.text(finished + ' / ' + state.queue.length);
    }

    function updateStats() {
        if (!state.queue.length) {
            summary.text('');
            progressText.text('0%');
            progressCount.text('0 / 0');
            globalBar.css('width', '0%');
            return;
        }

        const successCount = state.queue.filter((item) => item.status === 'completed').length;
        const errorCount = state.queue.filter((item) => item.status === 'error').length;
        summary.text(
            (strings.summary || 'Uploads:') + ' ' +
            successCount + ' ' + (strings.done || 'completed') + ', ' +
            errorCount + ' ' + (strings.failed || 'failed')
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
        galleryList.prepend(img);
    }

    function sendPing() {
        const payload = new FormData();
        payload.append('action', 'cg_admin_ping');
        payload.append('nonce', config.nonce || '');
        payload.append('post_id', config.post_id || 0);

        const url = config.ajax_url || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
        if (!url) {
            console.warn('[CG] Missing ajax URL for ping');
            return;
        }

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            body: payload,
        }).then((response) => response.json())
            .then((data) => {
                console.log('[CG] Ping response', data);
                if (statusMessage.length) {
                    statusMessage.text(data && data.success ? 'Ping OK' : 'Ping failed');
                }
            })
            .catch((error) => {
                console.error('[CG] Ping error', error);
                if (statusMessage.length) {
                    statusMessage.text('Ping error');
                }
            });
    }
});
