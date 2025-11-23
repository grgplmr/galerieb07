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
            window.location.reload();
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

    $('.cg-gallery-item').each(function () {
        const item = $(this);
        const imageId = parseInt(item.data('id'), 10);
        const current = cgFront.selection && cgFront.selection[imageId] ? cgFront.selection[imageId] : 0;
        renderStars(item.find('.cg-stars'), current, cgFront.starsMax);
    });

    $(document).on('click', '.cg-star', function () {
        const star = $(this);
        const imageId = parseInt(star.closest('.cg-gallery-item').data('id'), 10);
        const rating = parseInt(star.data('value'), 10);
        renderStars(star.parent(), rating, cgFront.starsMax);

        $.post(cgFront.ajaxUrl, {
            action: 'cg_save_rating',
            nonce: cgFront.nonce,
            gallery_id: cgFront.galleryId,
            image_id: imageId,
            rating: rating,
            email: cgFront.email
        });
    });

    $('#cg-submit-selection').on('click', function (e) {
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
});
