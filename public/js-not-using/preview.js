(function () {
    function $(s, r) { return (r || document).querySelector(s); }
    function $all(s, r) { return Array.from((r || document).querySelectorAll(s)); }

    var btn = $('#btnPreviewEmail');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var modal = $('#previewModal');
        var raw = $('#rawHtmlContent');
        var iframe = $('#previewIframe');

        if (iframe && raw) {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html><head><meta charset="utf-8"></head><body>' + raw.value + '</body></html>');
            doc.close();
        }
        modal.style.display = 'block';
        $('#previewBackdrop').style.display = 'block';
    });

    // close modal
    $all('[data-close="previewModal"]').forEach(function (el) {
        el.addEventListener('click', closePreview);
    });
    function closePreview() {
        $('#previewModal').style.display = 'none';
        $('#previewBackdrop').style.display = 'none';
    }

    // ESC closes
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closePreview();
    });

    // Tabs
    var tabRendered = $('#tabRendered');
    var tabRaw = $('#tabRaw');
    if (tabRendered && tabRaw) {
        tabRendered.addEventListener('click', function () {
            $('#rawBox').style.display = 'none';
            $('#renderBox').style.display = 'block';
            tabRendered.classList.add('active'); tabRaw.classList.remove('active');
        });
        tabRaw.addEventListener('click', function () {
            $('#renderBox').style.display = 'none';
            $('#rawBox').style.display = 'block';
            tabRaw.classList.add('active'); tabRendered.classList.remove('active');
        });
    }

    // Send Test Email
    var form = $('#testSendForm');
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            var btnSend = $('#btnSendTest');
            btnSend.disabled = true; btnSend.textContent = 'Sending...';
            try {
                const res = await fetch('send_test', { method: 'POST', body: fd, credentials: 'same-origin' });
                const data = await res.json();
                alert(data.ok ? '? Test email sent!' : '? Error: ' + (data.error || 'Unknown'));
            } catch (err) {
                alert('? Network error');
            } finally {
                btnSend.disabled = false; btnSend.textContent = 'Send Test';
            }
        });
    }
})();
