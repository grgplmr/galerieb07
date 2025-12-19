(function ($) {
    function initGallery(wrapper) {
        const galleryId = parseInt(wrapper.data('gallery-id'), 10);
        const token = wrapper.data('token');
        const varName = wrapper.data('var');
        const data = window[varName];
        if (!data) return;
        let currentEmail = localStorage.getItem('cg_email_' + galleryId) || '';
        let ratings = {};
        const grid = wrapper.find('#cg-grid');
        const gate = wrapper.find('.cg-email-gate');
        const galleryWrapper = wrapper.find('.cg-gallery-wrapper');
        const submitStatus = wrapper.find('.cg-submit-status');

        function renderStars(container, attachmentId) {
            container.empty();
            const current = ratings[attachmentId] ? parseInt(ratings[attachmentId], 10) : 0;
            for (let i = 1; i <= 5; i++) {
                const star = $('<span class="cg-star">★</span>');
                if (i <= current) {
                    star.addClass('cg-star-on');
                }
                (function (score) {
                    star.on('click', function (e) {
                        e.stopPropagation();
                        setRating(attachmentId, score);
                    });
                })(i);
                container.append(star);
            }
        }

        function buildGrid() {
            grid.empty();
            data.images.forEach(function (img, index) {
                const tile = $('<div class="cg-tile"></div>').attr('data-id', img.id).attr('data-index', index);
                const imageEl = $('<img />').attr('src', img.thumb || img.full).attr('alt', img.title);
                const stars = $('<div class="cg-stars"></div>');
                renderStars(stars, img.id);
                tile.append(imageEl).append(stars);
                tile.on('click', function () {
                    openLightbox(index);
                });
                grid.append(tile);
            });
        }

        function fetchRatings() {
            return $.ajax({
                url: data.ajaxUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    action: 'cg_front_get_ratings',
                    gallery_id: galleryId,
                    email: currentEmail,
                    nonce: data.nonce,
                    token: token
                }
            }).done(function (response) {
                if (response.success) {
                    ratings = response.data.ratings || {};
                    refreshStars();
                }
            });
        }

        function refreshStars() {
            grid.find('.cg-tile').each(function () {
                const attachmentId = parseInt($(this).data('id'), 10);
                renderStars($(this).find('.cg-stars'), attachmentId);
            });
            updateLightboxStars();
        }

        function setRating(attachmentId, rating) {
            $.ajax({
                url: data.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'cg_front_set_rating',
                    gallery_id: galleryId,
                    attachment_id: attachmentId,
                    rating: rating,
                    email: currentEmail,
                    nonce: data.nonce,
                    token: token
                }
            }).done(function (response) {
                if (response.success) {
                    ratings[attachmentId] = rating;
                    refreshStars();
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Error');
                }
            }).fail(function (xhr) {
                alert('Error ' + xhr.status + ': ' + (xhr.responseText || ''));
            });
        }

        function requireEmail() {
            gate.show();
            galleryWrapper.hide();
        }

        function allowGallery() {
            gate.hide();
            galleryWrapper.show();
            wrapper.find('.cg-email-display').text(currentEmail);
            buildGrid();
            fetchRatings();
        }

        gate.find('.cg-email-submit').on('click', function () {
            const emailVal = gate.find('#cg-email-input').val();
            if (!emailVal) {
                alert('Email requis');
                return;
            }
            currentEmail = emailVal.trim();
            localStorage.setItem('cg_email_' + galleryId, currentEmail);
            allowGallery();
        });

        wrapper.find('.cg-change-email').on('click', function () {
            localStorage.removeItem('cg_email_' + galleryId);
            currentEmail = '';
            gate.find('#cg-email-input').val('');
            requireEmail();
        });

        wrapper.find('.cg-submit-selection').on('click', function () {
            submitStatus.text('...');
            $.ajax({
                url: data.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'cg_front_submit_selection',
                    gallery_id: galleryId,
                    email: currentEmail,
                    ratings: ratings,
                    nonce: data.nonce,
                    token: token
                }
            }).done(function (response) {
                if (response.success) {
                    submitStatus.text(data.strings.submitSuccess);
                } else {
                    submitStatus.text(response.data && response.data.message ? response.data.message : 'Erreur');
                }
            }).fail(function (xhr) {
                submitStatus.text('Erreur ' + xhr.status + ': ' + (xhr.responseText || ''));
            });
        });

        let lightboxIndex = 0;
        const lightbox = wrapper.find('.cg-lightbox');

        function openLightbox(index) {
            lightboxIndex = index;
            const img = data.images[lightboxIndex];
            if (!img) return;
            lightbox.find('.cg-lightbox-image').attr('src', img.full || img.thumb).attr('alt', img.title);
            lightbox.show();
            updateLightboxStars();
        }

        function closeLightbox() {
            lightbox.hide();
        }

        function nextImage() {
            lightboxIndex = (lightboxIndex + 1) % data.images.length;
            openLightbox(lightboxIndex);
        }

        function prevImage() {
            lightboxIndex = (lightboxIndex - 1 + data.images.length) % data.images.length;
            openLightbox(lightboxIndex);
        }

        function updateLightboxStars() {
            const container = lightbox.find('.cg-lightbox-rating');
            const current = data.images[lightboxIndex];
            if (!current) return;
            renderStars(container, current.id);
        }

        lightbox.find('.cg-lightbox-close').on('click', closeLightbox);
        lightbox.find('.cg-lightbox-next').on('click', nextImage);
        lightbox.find('.cg-lightbox-prev').on('click', prevImage);
        $(document).on('keydown', function (e) {
            if (lightbox.is(':visible')) {
                if (e.key === 'ArrowRight') nextImage();
                if (e.key === 'ArrowLeft') prevImage();
                if (e.key === 'Escape') closeLightbox();
            }
        });

        if (currentEmail) {
            allowGallery();
        } else {
            requireEmail();
        }
    }

    $(document).ready(function () {
        $('.cg-front').each(function () {
            initGallery($(this));
        });
    });
})(jQuery);

// Validation: Step 7 email gate et front prêts
// Validation: Step 8 étoiles persistantes
// Validation: Step 9 lightbox synchronisée
// Validation: Step 10 soumission et email
