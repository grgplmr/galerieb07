jQuery(function ($) {
    if (typeof cgFront === 'undefined') {
        return;
    }

    function setCookie(name, value) {
        const d = new Date();
        d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/';
    }

    const emailForm = $('#cg-email-form');
    if (emailForm.length) {
        emailForm.on('submit', function (e) {
            e.preventDefault();
            const emailInput = $('#cg-email-input');
            const email = emailInput.val();
            if (!email) {
                emailInput.focus();
                return;
            }
            setCookie(cgFront.cookieKey, email);
            renderGallery(email);
        });
    }

    if (cgFront.email) {
        setCookie('cg_email_' + cgFront.galleryId, cgFront.email);
    }

    let ratingsByImageId = Object.assign({}, cgFront.selection || {});
    let currentIndex = 0;
    let items = [];

    function getRatingValue(value) {
        const parsed = parseInt(value, 10);
        return isNaN(parsed) ? 0 : parsed;
    }

    function renderStars(container, rating, max) {
        container.find('.cg-star').each(function () {
            const starValue = parseInt($(this).data('value'), 10);
            $(this).toggleClass('active', starValue <= rating);
        });
    }

    function buildStars(container) {
        container.empty();
        for (let i = 1; i <= cgFront.starsMax; i++) {
            container.append('<span class="cg-star" data-value="' + i + '">★</span>');
        }
    }

    function updateGridStars(imageId, rating) {
        const item = $('.cg-gallery-item[data-id="' + imageId + '"]');
        if (!item.length) return;

        const stars = item.find('.cg-stars');
        stars.attr('data-rating', rating);
        item.attr('data-rating', rating);
        renderStars(stars, rating, cgFront.starsMax);
    }

    function updateLightboxRatingDisplay(imageId, rating) {
        if (!lightbox.stars || !lightbox.stars.length || !lightbox.el) return;
        const currentId = parseInt(lightbox.el.dataset.imageId, 10);
        if (currentId !== imageId) return;

        lightbox.stars.attr('data-rating', rating);
        renderStars(lightbox.stars, rating, cgFront.starsMax);
    }

    function handleRatingSuccess(imageId, rating) {
        ratingsByImageId[imageId] = rating;
        updateGridStars(imageId, rating);
        updateLightboxRatingDisplay(imageId, rating);

        const itemIndex = items.findIndex((item) => item.image_id === imageId);
        if (itemIndex !== -1) {
            items[itemIndex].rating = rating;
        }
    }

    function sendRating(imageId, rating) {
        $.post(cgFront.ajaxUrl, {
            action: 'cg_save_rating',
            nonce: cgFront.nonce,
            gallery_id: cgFront.galleryId,
            image_id: imageId,
            rating: rating,
            email: cgFront.email
        }).done(function () {
            handleRatingSuccess(imageId, rating);
        });
    }

    function initStars() {
        $('.cg-gallery-item').each(function () {
            const item = $(this);
            const ratingAttr = getRatingValue(item.find('.cg-stars').data('rating'));
            const imageId = parseInt(item.data('id'), 10);
            const stored = getRatingValue(ratingsByImageId[imageId]);
            const current = ratingAttr || stored;
            renderStars(item.find('.cg-stars'), current || 0, cgFront.starsMax);
            item.find('.cg-stars').attr('data-rating', current || 0);
            item.attr('data-rating', current || 0);
            ratingsByImageId[imageId] = current || 0;
        });
    }

    initStars();

    $(document).on('click', '.cg-gallery-item .cg-star', function () {
        const star = $(this);
        const imageId = parseInt(star.closest('.cg-gallery-item').data('id'), 10);
        const rating = parseInt(star.data('value'), 10);
        if (!imageId || !rating) return;

        sendRating(imageId, rating);
    });

    $(document).on('click', '#cg-submit-selection', function (e) {
        e.preventDefault();
        const btn = $(this);
        btn.prop('disabled', true).text(btn.data('loading'));
        $('#cg-selection-message').text('');
        $.post(cgFront.ajaxUrl, {
            action: 'cg_submit_selection',
            nonce: cgFront.nonce,
            gallery_id: cgFront.galleryId,
            email: cgFront.email
        }).done(function (resp) {
            if (resp.success) {
                $('#cg-selection-message').text(resp.data.message);
            } else {
                $('#cg-selection-message').text(resp.data || 'Error');
            }
        }).fail(function () {
            $('#cg-selection-message').text('Error');
        }).always(function () {
            btn.prop('disabled', false).text(btn.data('label'));
        });
    });

    function renderGallery(email) {
        const container = $('#cg-gallery-container');
        const formButton = $('#cg-email-form button');
        if (formButton.length) {
            formButton.prop('disabled', true);
        }

        $.post(cgFront.ajaxUrl, {
            action: 'cg_render_gallery',
            nonce: cgFront.nonce,
            gallery_id: cgFront.galleryId,
            email: email,
            cookie_key: cgFront.cookieKey
        }).done(function (resp) {
            if (resp.success && resp.data && resp.data.html) {
                cgFront.email = resp.data.email;
                cgFront.selection = resp.data.selection || {};
                Object.keys(ratingsByImageId).forEach(function (key) { delete ratingsByImageId[key]; });
                Object.assign(ratingsByImageId, cgFront.selection);
                container.replaceWith(resp.data.html);
                initStars();
                setCookie(cgFront.cookieKey, resp.data.email);
            } else {
                window.location.reload();
            }
        }).fail(function () {
            window.location.reload();
        });
    }

    // Lightbox
    const lightbox = {
        overlay: null,
        image: null,
        stars: null,
        el: null,
    };

    function createLightbox() {
        if (lightbox.overlay) return;
        const overlay = $('<div class="cg-lightbox-overlay" tabindex="-1"></div>');
        const content = $('<div class="cg-lightbox-content"></div>');
        const closeBtn = $('<button class="cg-lightbox-close" aria-label="Close">×</button>');
        const prevBtn = $('<button class="cg-lightbox-nav cg-lightbox-prev" aria-label="Previous">‹</button>');
        const nextBtn = $('<button class="cg-lightbox-nav cg-lightbox-next" aria-label="Next">›</button>');
        const img = $('<img class="cg-lightbox-image" alt="" />');
        const stars = $('<div class="cg-lightbox-stars" data-rating="0"></div>');
        buildStars(stars);

        const mediaContainer = $('<div class="cg-lightbox-media"></div>');
        mediaContainer.append(img, stars);

        content.append(closeBtn, prevBtn, mediaContainer, nextBtn);
        overlay.append(content);
        $('body').append(overlay);

        overlay.on('click', function (e) {
            if ($(e.target).is('.cg-lightbox-overlay, .cg-lightbox-close')) {
                hideLightbox();
            }
        });

        prevBtn.on('click', function (e) {
            e.preventDefault();
            renderLightbox(currentIndex - 1);
        });

        nextBtn.on('click', function (e) {
            e.preventDefault();
            renderLightbox(currentIndex + 1);
        });

        $(document).on('keydown', function (e) {
            if (!lightbox.overlay || !lightbox.overlay.is(':visible')) {
                return;
            }
            if (e.key === 'Escape') {
                hideLightbox();
            } else if (e.key === 'ArrowLeft') {
                renderLightbox(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                renderLightbox(currentIndex + 1);
            }
        });

        lightbox.overlay = overlay;
        lightbox.image = img;
        lightbox.stars = stars;
        lightbox.el = overlay[0];
    }

    function collectItems() {
        items = [];
        $('.cg-gallery-item').each(function () {
            const item = $(this);
            const full = item.data('full');
            const imageId = parseInt(item.data('id'), 10);
            const itemRating = getRatingValue(item.data('rating')) || getRatingValue(item.find('.cg-stars').data('rating'));
            const currentRating = itemRating || getRatingValue(ratingsByImageId[imageId]);
            if (full && imageId) {
                const itemIndex = items.length;
                items.push({
                    full_url: full,
                    image_id: imageId,
                    rating: currentRating,
                });
                item.attr('data-index', itemIndex);
            }
        });
    }

    function renderLightbox(index) {
        if (!items.length || !lightbox.overlay) return;
        if (index < 0) {
            index = items.length - 1;
        }
        if (index >= items.length) {
            index = 0;
        }
        currentIndex = index;
        const currentItem = items[currentIndex];
        if (!currentItem) return;

        const rating = getRatingValue(currentItem.rating) || getRatingValue(ratingsByImageId[currentItem.image_id]);
        currentItem.rating = rating;
        lightbox.image.attr('src', currentItem.full_url);
        lightbox.stars.attr('data-rating', rating || 0);
        lightbox.el.dataset.imageId = currentItem.image_id;
        renderStars(lightbox.stars, rating || 0, cgFront.starsMax);
        lightbox.overlay.css('display', 'flex').show().focus();
    }

    function hideLightbox() {
        if (lightbox.overlay) {
            lightbox.overlay.hide();
        }
    }

    $(document).on('click', '.cg-gallery-item img', function (e) {
        e.preventDefault();
        createLightbox();
        collectItems();
        const parent = $(this).closest('.cg-gallery-item');
        const index = parseInt(parent.data('index'), 10) || 0;
        renderLightbox(index);
    });

    $(document).on('click', '.cg-lightbox-stars .cg-star', function () {
        if (!lightbox.el) return;
        const star = $(this);
        const imageId = parseInt(lightbox.el.dataset.imageId, 10);
        const rating = parseInt(star.data('value'), 10);
        if (!imageId || !rating) return;

        sendRating(imageId, rating);
    });
});
