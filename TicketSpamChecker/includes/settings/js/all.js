setTimeout(() => {
    const successMessage = document.getElementById('success-message');
    if (successMessage) successMessage.remove();
}, 5000);

const uploadArea = document.getElementById('uploadArea');
const faviconInput = document.getElementById('faviconInput');
const faviconPreview = document.getElementById('faviconPreview');
const uploadText = document.getElementById('uploadText');

if (uploadArea && !faviconPreview) {
    uploadArea.addEventListener('click', () => {
        faviconInput.click();
    });

    faviconInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            const formData = new FormData();
            formData.append('favicon', e.target.files[0]);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    });
}

function removeFavicon(event) {
    event.preventDefault();
    fetch(window.location.href + '?remove_favicon=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
}