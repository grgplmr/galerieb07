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

    function renderStars(container, rating, max) {
        container.find('.cg-star').each(function () {
            const starValue = parseInt($(this).data('value'), 10);
            $(this).toggleClass('active', starValue <= rating);
        });
    }

    function initStars() {
        $('.cg-gallery-item').each(function () {
            const item = $(this);
            const ratingAttr = parseInt(item.find('.cg-stars').data('rating'), 10);
            const imageId = parseInt(item.data('id'), 10);
            const current = !isNaN(ratingAttr)
                ? ratingAttr
                : (cgFront.selection && cgFront.selection[imageId] ? cgFront.selection[imageId] : 0);
            renderStars(item.find('.cg-stars'), current || 0, cgFront.starsMax);
            item.find('.cg-stars').attr('data-rating', current || 0);
        });
    }

    initStars();

    $(document).on('click', '.cg-star', function () {
        const star = $(this);
        const imageId = parseInt(star.closest('.cg-gallery-item').data('id'), 10);
        const rating = parseInt(star.data('value'), 10);
        renderStars(star.parent(), rating, cgFront.starsMax);
        star.parent().attr('data-rating', rating);

        $.post(cgFront.ajaxUrl, {
            action: 'cg_save_rating',
            nonce: cgFront.nonce,
            gallery_id: cgFront.galleryId,
            image_id: imageId,
            rating: rating,
            email: cgFront.email
        });
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
        currentIndex: 0,
        items: [],
    };

    function createLightbox() {
        if (lightbox.overlay) return;
        const overlay = $('<div class="cg-lightbox-overlay" tabindex="-1"></div>');
        const content = $('<div class="cg-lightbox-content"></div>');
        const closeBtn = $('<button class="cg-lightbox-close" aria-label="Close">×</button>');
        const prevBtn = $('<button class="cg-lightbox-nav cg-lightbox-prev" aria-label="Previous">‹</button>');
        const nextBtn = $('<button class="cg-lightbox-nav cg-lightbox-next" aria-label="Next">›</button>');
        const img = $('<img class="cg-lightbox-image" alt="" />');

        content.append(closeBtn, prevBtn, img, nextBtn);
        overlay.append(content);
        $('body').append(overlay);

        overlay.on('click', function (e) {
            if ($(e.target).is('.cg-lightbox-overlay, .cg-lightbox-close')) {
                hideLightbox();
            }
        });

        prevBtn.on('click', function (e) {
            e.preventDefault();
            showImage(lightbox.currentIndex - 1);
        });

        nextBtn.on('click', function (e) {
            e.preventDefault();
            showImage(lightbox.currentIndex + 1);
        });

        $(document).on('keydown', function (e) {
            if (!lightbox.overlay || !lightbox.overlay.is(':visible')) {
                return;
            }
            if (e.key === 'Escape') {
                hideLightbox();
            } else if (e.key === 'ArrowLeft') {
                showImage(lightbox.currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                showImage(lightbox.currentIndex + 1);
            }
        });

        lightbox.overlay = overlay;
        lightbox.image = img;
    }

    function collectItems() {
        lightbox.items = [];
        $('.cg-gallery-item').each(function (index) {
            const full = $(this).data('full');
            if (full) {
                lightbox.items.push(full);
                $(this).attr('data-index', index);
            }
        });
    }

    function showImage(index) {
        if (!lightbox.items.length) return;
        if (index < 0) {
            index = lightbox.items.length - 1;
        }
        if (index >= lightbox.items.length) {
            index = 0;
        }
        lightbox.currentIndex = index;
        lightbox.image.attr('src', lightbox.items[index]);
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
        showImage(index);
    });
});
