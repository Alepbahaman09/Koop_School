const TOAST_DURATION = 4000;
const BANNER_WIDTH = 1200;
const BANNER_HEIGHT = 520;
const MAX_BANNER_FILE_SIZE = 5 * 1024 * 1024;
const BANNER_CLEANUP_INTERVAL = 5000;

function dismissToast(toast) {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-8px)';
    window.setTimeout(() => toast.remove(), 300);
}

window.showToast = function (message, type = 'success', target = null) {
    const container = target ?? document.querySelector('[data-toast-container]');
    const template = document.querySelector(`[data-toast-template="${type}"]`);

    if (! container || ! template) {
        return;
    }

    const toast = template.content.firstElementChild.cloneNode(true);
    toast.querySelector('[data-toast-message]').textContent = message;
    toast.querySelector('[data-toast-close]').addEventListener('click', () => dismissToast(toast));

    container.appendChild(toast);
    window.setTimeout(() => dismissToast(toast), TOAST_DURATION);
};

function setupBannerImageEditor(editor) {
    const input = editor.querySelector('[data-banner-image-input]');
    const currentPreview = editor.querySelector('[data-banner-current-preview]');
    const cropper = editor.querySelector('[data-banner-cropper]');
    const canvas = editor.querySelector('[data-banner-canvas]');
    const zoomInput = editor.querySelector('[data-banner-zoom]');
    const context = canvas.getContext('2d');

    context.imageSmoothingEnabled = true;
    context.imageSmoothingQuality = 'high';
    const form = editor.closest('form');

    let image = null;
    let sourceName = 'banner';
    let baseScale = 1;
    let scale = 1;
    let offsetX = 0;
    let offsetY = 0;
    let dragging = false;
    let pointerX = 0;
    let pointerY = 0;

    function keepImageInsideCanvas() {
        const minimumX = BANNER_WIDTH - image.width * scale;
        const minimumY = BANNER_HEIGHT - image.height * scale;
        offsetX = Math.min(0, Math.max(minimumX, offsetX));
        offsetY = Math.min(0, Math.max(minimumY, offsetY));
    }

    function drawCrop() {
        if (! image) {
            return;
        }

        keepImageInsideCanvas();
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, BANNER_WIDTH, BANNER_HEIGHT);
        context.drawImage(
            image,
            offsetX,
            offsetY,
            image.width * scale,
            image.height * scale,
        );
    }

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (! file) {
            return;
        }

        if (! file.type.startsWith('image/')) {
            input.value = '';
            window.showToast('Please choose a valid image file.', 'error');
            return;
        }

        if (file.size > MAX_BANNER_FILE_SIZE) {
            input.value = '';
            window.showToast('The banner image must be 5 MB or smaller.', 'error');
            return;
        }

        sourceName = file.name.replace(/\.[^.]+$/, '') || 'banner';
        const objectUrl = URL.createObjectURL(file);
        const selectedImage = new Image();

        selectedImage.addEventListener('load', () => {
            URL.revokeObjectURL(objectUrl);
            image = selectedImage;
            baseScale = Math.max(
                BANNER_WIDTH / image.width,
                BANNER_HEIGHT / image.height,
            );
            scale = baseScale;
            offsetX = (BANNER_WIDTH - image.width * scale) / 2;
            offsetY = (BANNER_HEIGHT - image.height * scale) / 2;
            zoomInput.value = '1';
            currentPreview.classList.add('hidden');
            cropper.classList.remove('hidden');
            drawCrop();
        });

        selectedImage.addEventListener('error', () => {
            URL.revokeObjectURL(objectUrl);
            input.value = '';
            window.showToast('This image could not be opened.', 'error');
        });

        selectedImage.src = objectUrl;
    });

    zoomInput.addEventListener('input', () => {
        if (! image) {
            return;
        }

        const imageCenterX = (BANNER_WIDTH / 2 - offsetX) / scale;
        const imageCenterY = (BANNER_HEIGHT / 2 - offsetY) / scale;
        scale = baseScale * Number(zoomInput.value);
        offsetX = BANNER_WIDTH / 2 - imageCenterX * scale;
        offsetY = BANNER_HEIGHT / 2 - imageCenterY * scale;
        drawCrop();
    });

    canvas.addEventListener('pointerdown', (event) => {
        if (! image) {
            return;
        }

        dragging = true;
        pointerX = event.clientX;
        pointerY = event.clientY;
        canvas.setPointerCapture(event.pointerId);
    });

    canvas.addEventListener('pointermove', (event) => {
        if (! dragging || ! image) {
            return;
        }

        const canvasScale = BANNER_WIDTH / canvas.getBoundingClientRect().width;
        offsetX += (event.clientX - pointerX) * canvasScale;
        offsetY += (event.clientY - pointerY) * canvasScale;
        pointerX = event.clientX;
        pointerY = event.clientY;
        drawCrop();
    });

    function stopDragging(event) {
        dragging = false;
        if (canvas.hasPointerCapture(event.pointerId)) {
            canvas.releasePointerCapture(event.pointerId);
        }
    }

    canvas.addEventListener('pointerup', stopDragging);
    canvas.addEventListener('pointercancel', stopDragging);

    form.addEventListener('submit', (event) => {
        if (! image) {
            return;
        }

        event.preventDefault();
        const submitButton = event.submitter;
        const submitButtonText = submitButton?.textContent;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Preparing image...';
        }

        canvas.toBlob((blob) => {
            if (! blob) {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = submitButtonText;
                }
                window.showToast('The banner image could not be prepared.', 'error');
                return;
            }

            const croppedFile = new File([blob], `${sourceName}-banner.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            input.files = transfer.files;
            HTMLFormElement.prototype.submit.call(form);
        }, 'image/jpeg', 0.96);
    });
}

function setupExpiredBannerCleanup(bannerList) {
    const cleanupUrl = bannerList.dataset.cleanupUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let cleanupInProgress = false;

    if (! cleanupUrl || ! csrfToken) {
        return;
    }

    async function removeExpiredBanners() {
        if (cleanupInProgress) {
            return;
        }

        cleanupInProgress = true;

        try {
            const response = await fetch(cleanupUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            if (! response.ok) {
                return;
            }

            const { deleted_ids: deletedIds = [] } = await response.json();
            const deletedIdSet = new Set(deletedIds.map(String));

            bannerList.querySelectorAll('[data-banner-id]').forEach((banner) => {
                if (deletedIdSet.has(banner.dataset.bannerId)) {
                    banner.remove();
                }
            });

            if (! bannerList.querySelector('[data-banner-id]')) {
                bannerList.querySelector('[data-banner-empty-state]')?.classList.remove('hidden');
            }

            if (deletedIds.length > 0) {
                window.showToast(
                    `${deletedIds.length} expired banner${deletedIds.length === 1 ? '' : 's'} deleted automatically.`,
                    'success',
                );
            }
        } catch (error) {
            console.error('Expired banners could not be checked.', error);
        } finally {
            cleanupInProgress = false;
        }
    }

    window.setInterval(removeExpiredBanners, BANNER_CLEANUP_INTERVAL);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-banner-image-editor]').forEach(setupBannerImageEditor);
    document.querySelectorAll('[data-banner-list]').forEach(setupExpiredBannerCleanup);

    document.querySelectorAll('[data-initial-toast]').forEach((alert) => {
        const target = alert.dataset.toastTarget
            ? document.querySelector(alert.dataset.toastTarget)
            : null;

        window.showToast(alert.dataset.message, alert.dataset.type, target);
        alert.remove();
    });
});
