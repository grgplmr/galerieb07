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
        lastErrorMessage: '',
    };

    addDebugInfo();

    fileInput.on('change', function () {
        const files = Array.from(this.files || []);
        if (!files.length) {
            return;
        }
        resetQueueIfFinished();
        addFilesToQueue(files);
        this.value = '';
        updateGlobalProgress();
        updateSelectionStatus();
    });

    startButton.on('click', function (event) {
        event.preventDefault();
        startUploads();
    });

    function addFilesToQueue(files) {
        files.forEach((file) => {
            const item = {
                id: 'cg-file-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                file,
                status: 'pending',
                progress: 0,
                attempts: 0,
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

    function startUploads() {
        if (state.uploading) {
            return;
        }

        const pendingItems = state.items.filter((item) => item.status === 'pending');
        state.lastErrorMessage = '';

        if (!pendingItems.length) {
            setStatus(strings.noFiles || 'Select files first.');
            return;
        }

        setStatus(strings.start || 'Starting upload...');
        state.uploading = true;
        startButton.prop('disabled', true);

        processUploadsSequentially(pendingItems).finally(() => {
            state.uploading = false;
            startButton.prop('disabled', false);
            updateStats();
            updateSelectionStatus();
        });
    }

    function processUploadsSequentially(items) {
        return items.reduce((promise, item) => {
            return promise.then(() => uploadFile(item));
        }, Promise.resolve());
    }

    function uploadFile(item) {
        return new Promise((resolve) => {
            setItemStatus(item, 'uploading');
            setItemProgress(item, 0);
            updateGlobalProgress();

            const formData = new FormData();
            formData.append('action', 'cg_admin_upload_images');
            formData.append('nonce', config.nonce || '');
            formData.append('gallery_id', getGalleryId());
            formData.append('file', item.file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', getAjaxUrl(), true);
            xhr.responseType = 'json';
            item.attempts = (item.attempts || 0) + 1;

            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable) {
                    return;
                }
                const percent = event.loaded / event.total;
                setItemProgress(item, percent);
                updateGlobalProgress();
            };

            xhr.onerror = function () {
                const message = formatErrorMessage(item, { status: xhr.status || 0, responseText: xhr.responseText }, strings.networkError || 'Network error');
                handleItemError(item, message, { status: xhr.status || 0, responseText: xhr.responseText });
                finalizeFile();
            };

            xhr.onload = function () {
                const parsed = parseResponse(xhr);
                if (xhr.status !== 200) {
                    const message = formatErrorMessage(
                        item,
                        {
                            status: xhr.status,
                            responseText: parsed.rawText,
                            message: parsed.message,
                            code: parsed.code,
                        },
                        strings.serverError || 'Upload failed'
                    );
                    handleItemError(item, message, {
                        status: xhr.status,
                        responseText: parsed.rawText,
                        code: parsed.code,
                    });
                    finalizeFile();
                    return;
                }

                if (parsed.payload && parsed.payload.success && parsed.payload.data && Array.isArray(parsed.payload.data.attachments)) {
                    setItemProgress(item, 1);
                    setItemStatus(item, 'completed');
                    appendAttachments(parsed.payload.data.attachments);
                } else {
                    const message = formatErrorMessage(
                        item,
                        {
                            status: xhr.status,
                            responseText: parsed.rawText,
                            message: parsed.message || (parsed.payload && parsed.payload.data ? parsed.payload.data.message : ''),
                            code: parsed.code,
                        },
                        strings.serverError || 'Upload failed'
                    );
                    handleItemError(item, message, {
                        status: xhr.status,
                        responseText: parsed.rawText,
                        code: parsed.code,
                    });
                }

                finalizeFile();
            };

            xhr.send(formData);

            function finalizeFile() {
                updateStats();
                updateGlobalProgress();
                resolve();
            }
        });
    }

    function handleItemError(item, message, detail = {}) {
        setItemStatus(item, 'error');
        setItemProgress(item, 0);

        const row = queueList.find('[data-id="' + item.id + '"]');
        row.find('.cg-upload-error').text(message);
        state.lastErrorMessage = message;
        logErrorDetail(item, detail, message);
        updateStats();
    }

    function clearItemError(item) {
        const row = queueList.find('[data-id="' + item.id + '"]');
        row.find('.cg-upload-error').text('');
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
        const total = state.items.length;
        const pendingCount = state.items.filter((item) => item.status === 'pending').length;
        if (errorCount === 0) {
            state.lastErrorMessage = '';
        }
        if (!total) {
            setStatus('');
            return;
        }
        if (!state.uploading && pendingCount === total) {
            setStatus(formatReadyMessage(pendingCount));
            return;
        }
        const summary = formatSummary(successCount, errorCount, total);
        const message = state.lastErrorMessage ? summary + ' — ' + state.lastErrorMessage : summary;
        setStatus(message);
    }

    function updateSelectionStatus() {
        if (state.uploading) {
            return;
        }
        const pendingCount = state.items.filter((item) => item.status === 'pending').length;
        if (!state.items.length) {
            setStatus('');
            return;
        }
        if (pendingCount) {
            setStatus(formatReadyMessage(pendingCount));
            return;
        }
        updateStats();
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

    function parseResponse(xhr) {
        let payload = xhr.response;
        let rawText = xhr.responseText || '';
        if (!payload && rawText) {
            try {
                payload = JSON.parse(rawText);
            } catch (e) {
                payload = null;
            }
        }
        const message =
            payload && payload.data && payload.data.message
                ? payload.data.message
                : payload && payload.message
                    ? payload.message
                    : '';
        const code =
            payload && payload.data && payload.data.code
                ? payload.data.code
                : payload && payload.code
                    ? payload.code
                    : '';
        return { payload, rawText, message, code };
    }

    function formatSummary(successCount, errorCount, total) {
        const template = strings.summaryFormat;
        if (template && template.indexOf('%1$s') !== -1) {
            return template.replace('%1$s', successCount).replace('%2$s', total).replace('%3$s', errorCount);
        }
        const label = strings.summary || 'Uploads';
        return label + ': ' + successCount + '/' + total + ' successful - ' + errorCount + ' errors';
    }

    function formatReadyMessage(count) {
        const template = strings.ready || '%s files ready. Click Start upload.';
        return template.replace('%s', count);
    }

    function formatErrorMessage(item, detail = {}, fallback = '') {
        const fileLabel = strings.fileLabel || 'File';
        const httpLabel = strings.httpStatus || 'HTTP status';
        const responseLabel = strings.responseLabel || 'Response';
        const parts = [];
        if (item && item.file && item.file.name) {
            parts.push(fileLabel + ': ' + item.file.name);
        }
        if (detail.status) {
            parts.push(httpLabel + ': ' + detail.status);
        }
        if (detail.code) {
            parts.push('Code: ' + detail.code);
        }
        const baseMessage = detail.message || fallback || '';
        if (baseMessage) {
            parts.push(baseMessage);
        }
        const responseText = detail.responseText || '';
        const responseSnippet = responseText && responseText !== baseMessage ? responseLabel + ': ' + trimResponse(responseText) : '';
        const limitHint = detectLimitHint(responseText || baseMessage);
        if (responseSnippet) {
            parts.push(responseSnippet);
        }
        if (limitHint) {
            parts.push(limitHint);
        }
        return parts.filter(Boolean).join(' — ');
    }

    function detectLimitHint(text) {
        if (!text) {
            return '';
        }
        const lower = text.toLowerCase();
        if (lower.includes('413') || lower.includes('request entity too large') || lower.includes('upload_max_filesize') || lower.includes('post_max_size')) {
            const limitMessage = strings.limitHelp || 'Limite serveur atteinte: vérifier upload_max_filesize, post_max_size, max_file_uploads';
            if (config.maxFileUploads) {
                return limitMessage + ' (max_file_uploads: ' + config.maxFileUploads + ')';
            }
            return limitMessage;
        }
        return '';
    }

    function trimResponse(text) {
        if (!text) {
            return '';
        }
        return text.length > 250 ? text.slice(0, 250) + '…' : text;
    }

    function logErrorDetail(item, detail, message) {
        const info = {
            fileName: item && item.file ? item.file.name : '',
            status: detail.status || 0,
            code: detail.code || '',
            responseText: detail.responseText || '',
            message: message || detail.message || '',
        };
        // eslint-disable-next-line no-console
        console.error('[CG upload] Upload failed', info);
    }

    function addDebugInfo() {
        if (!statusMessage.length || !config.maxFileUploads) {
            return;
        }
        const debugText = strings.maxFileUploads || ('max_file_uploads: ' + config.maxFileUploads);
        if ($('#cg-upload-debug-info').length) {
            return;
        }
        $('<p id="cg-upload-debug-info" class="cg-upload-debug" aria-live="polite"></p>').text(debugText).insertAfter(statusMessage);
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
