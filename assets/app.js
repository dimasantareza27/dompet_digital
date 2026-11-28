(function () {
    /* Global UI Overlay & Menu Helpers */
    window.__nbToggle = function (id) {
        ['nb-notif-popover', 'nb-context-menu'].forEach(function (other) {
            if (other !== id) {
                var o = document.getElementById(other);
                if (o) o.classList.add('hidden');
            }
        });
        var el = document.getElementById(id);
        if (el) el.classList.toggle('hidden');
    };

    window.__nbMarkAllRead = function () {
        document.querySelectorAll('.js-nb-unread').forEach(function (d) {
            d.classList.add('hidden');
        });
        var b = document.getElementById('nb-bell-badge');
        if (b) b.textContent = '0';
    };

    window.__nbOpenModal = function () {
        var m = document.getElementById('nb-confirm-modal');
        var c = document.getElementById('nb-context-menu');
        if (c) c.classList.add('hidden');
        if (m) {
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
    };

    window.__nbCloseModal = function () {
        var m = document.getElementById('nb-confirm-modal');
        if (m) {
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
        document.body.style.overflow = '';
    };

    var confirmationModal = null;
    var confirmationAction = null;

    function ensureConfirmationModal() {
        if (confirmationModal) return confirmationModal;

        confirmationModal = document.createElement('div');
        confirmationModal.id = 'nb-confirm-modal';
        confirmationModal.className = 'hidden modal-backdrop';
        confirmationModal.setAttribute('role', 'presentation');
        confirmationModal.innerHTML =
            '<div data-component-id="modal-destructive" data-component-type="modal" data-component-variant="destructive" role="dialog" aria-modal="true" aria-labelledby="nb-confirm-title" class="modal-dialog nb-shadow-lg">' +
                '<div class="modal-body">' +
                    '<div class="modal-icon"><span class="material-symbols-outlined icon-24" aria-hidden="true">warning</span></div>' +
                    '<div class="modal-text">' +
                        '<h3 id="nb-confirm-title" class="font-headline text-headline-md">Konfirmasi Tindakan</h3>' +
                        '<p id="nb-confirm-message" class="font-body text-body-md text-muted"></p>' +
                    '</div>' +
                '</div>' +
                '<div class="modal-actions">' +
                    '<button type="button" id="nb-confirm-cancel" class="btn btn--card nb-shadow nb-press font-label text-label-lg">Batal</button>' +
                    '<button type="button" id="nb-confirm-accept" class="btn btn--error nb-shadow nb-press font-label text-label-lg">' +
                        '<span class="material-symbols-outlined icon-18" aria-hidden="true">delete_forever</span>' +
                        '<span class="font-label text-label-lg">Lanjutkan</span>' +
                    '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(confirmationModal);

        var cancelButton = document.getElementById('nb-confirm-cancel');
        var acceptButton = document.getElementById('nb-confirm-accept');

        cancelButton.addEventListener('click', window.__nbCloseModal);
        acceptButton.addEventListener('click', function () {
            var action = confirmationAction;
            window.__nbCloseModal();
            confirmationAction = null;
            if (typeof action === 'function') action();
        });

        confirmationModal.addEventListener('click', function (event) {
            if (event.target === confirmationModal) window.__nbCloseModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && confirmationModal && confirmationModal.classList.contains('flex')) {
                window.__nbCloseModal();
            }
        });

        return confirmationModal;
    }

    window.__nbConfirm = function (message, action) {
        var modal = ensureConfirmationModal();
        var messageElement = document.getElementById('nb-confirm-message');
        confirmationAction = action;
        messageElement.textContent = message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        document.getElementById('nb-confirm-cancel').focus();
    };

    function decodeConfirmationText(value) {
        var textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
    }

    var requiredFieldsModal = null;
    var firstMissingField = null;

    function closeRequiredFieldsModal() {
        if (!requiredFieldsModal) return;
        requiredFieldsModal.classList.add('hidden');
        requiredFieldsModal.classList.remove('flex');
        document.body.style.overflow = '';
        if (firstMissingField && firstMissingField.isConnected) firstMissingField.focus();
        firstMissingField = null;
    }

    function showRequiredFieldsModal(field) {
        if (requiredFieldsModal && requiredFieldsModal.classList.contains('flex')) return;

        var form = field.form;
        var missingFields = form
            ? Array.prototype.filter.call(form.elements, function (control) {
                return control.validity && control.validity.valueMissing;
            })
            : [field];
        firstMissingField = missingFields[0] || field;

        var labels = missingFields.map(function (control) {
            var label = control.labels && control.labels.length
                ? control.labels[0].textContent.trim()
                : (control.getAttribute('aria-label') || control.name);
            return label || 'Bidang ini';
        }).filter(function (label, index, list) {
            return list.indexOf(label) === index;
        });

        if (!requiredFieldsModal) {
            requiredFieldsModal = document.createElement('div');
            requiredFieldsModal.id = 'nb-required-fields-modal';
            requiredFieldsModal.className = 'hidden modal-backdrop';
            requiredFieldsModal.setAttribute('role', 'presentation');
            requiredFieldsModal.innerHTML =
                '<div role="alertdialog" aria-modal="true" aria-labelledby="nb-required-fields-title" aria-describedby="nb-required-fields-message" class="modal-dialog nb-shadow-lg">' +
                    '<div class="modal-body">' +
                        '<div class="modal-icon modal-icon--warning"><span class="material-symbols-outlined icon-24" aria-hidden="true">warning</span></div>' +
                        '<div class="modal-text">' +
                            '<h3 id="nb-required-fields-title" class="font-headline text-headline-md">Bidang wajib belum diisi</h3>' +
                            '<p id="nb-required-fields-message" class="font-body text-body-md text-muted"></p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="modal-actions">' +
                        '<button type="button" id="nb-required-fields-close" class="btn btn--primary nb-shadow nb-press font-label text-label-lg">Mengerti</button>' +
                    '</div>' +
                '</div>';
            document.body.appendChild(requiredFieldsModal);

            document.getElementById('nb-required-fields-close').addEventListener('click', closeRequiredFieldsModal);
            requiredFieldsModal.addEventListener('click', function (event) {
                if (event.target === requiredFieldsModal) closeRequiredFieldsModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && requiredFieldsModal.classList.contains('flex')) {
                    closeRequiredFieldsModal();
                }
            });
        }

        document.getElementById('nb-required-fields-message').textContent = labels.length
            ? 'Mohon isi bidang berikut: ' + labels.join(', ') + '.'
            : 'Mohon lengkapi semua bidang wajib sebelum melanjutkan.';
        requiredFieldsModal.classList.remove('hidden');
        requiredFieldsModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        window.setTimeout(function () {
            if (requiredFieldsModal.classList.contains('flex')) {
                document.getElementById('nb-required-fields-close').focus();
            }
        }, 0);
    }

    document.addEventListener('invalid', function (event) {
        var field = event.target;
        if (!field.validity || !field.validity.valueMissing) return;
        event.preventDefault();
        showRequiredFieldsModal(field);
    }, true);

    document.querySelectorAll('[onclick]').forEach(function (element) {
        var onclick = element.getAttribute('onclick') || '';
        if (!/\bconfirm\s*\(/.test(onclick)) return;

        var match = onclick.match(/confirm\s*\(\s*(?:'([^']*)'|"([^"]*)"|`([^`]*)`)/);
        var message = match ? (match[1] || match[2] || match[3]) : 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        message = decodeConfirmationText(message);
        element.removeAttribute('onclick');

        element.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (element.tagName === 'A') {
                var href = element.getAttribute('href');
                window.__nbConfirm(message, function () {
                    if (href) window.location.assign(href);
                });
                return;
            }

            var form = element.form;
            if (form) {
                window.__nbConfirm(message, function () {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(element);
                    } else {
                        form.submit();
                    }
                });
            }
        });
    });

    window.__nbOpenDrawer = function () {
        var d = document.getElementById('nb-slide-drawer');
        var c = document.getElementById('nb-context-menu');
        if (c) c.classList.add('hidden');
        if (d) {
            d.classList.remove('hidden');
            d.classList.add('flex');
        }
    };

    window.__nbCloseDrawer = function () {
        var d = document.getElementById('nb-slide-drawer');
        if (d) {
            d.classList.add('hidden');
            d.classList.remove('flex');
        }
    };

    function scheduleToastDismissal(toast) {
        window.setTimeout(function () {
            if (!toast.isConnected) return;
            toast.classList.add('toast-dismiss');
            window.setTimeout(function () {
                toast.remove();
            }, 250);
        }, 3000);
    }

    document.querySelectorAll('.toast-fixed').forEach(scheduleToastDismissal);

    window.__nbToast = function (variant, msg) {
        var stack = document.getElementById('nb-toast-stack');
        if (!stack) return;
        stack.classList.remove('hidden');
        stack.classList.add('flex');
        var colors = variant === 'error'
            ? 'bg-error'
            : (variant === 'warning' ? 'bg-warning' : 'bg-secondary');
        var item = document.createElement('div');
        item.className = 'toast-item nb-shadow ' + colors;
        item.innerHTML =
            '<span class="font-label text-label-lg">' + msg + '</span>' +
            '<button type="button" onclick="this.parentElement.remove()" class="toast-close-btn">' +
            '<span class="material-symbols-outlined icon-16">close</span></button>';
        stack.appendChild(item);
        scheduleToastDismissal(item);
    };

    /* Page-Specific Helpers (guarded by element existence) */
    var filterJenis = document.getElementById('filter-jenis');
    var txTbody = document.getElementById('tx-tbody');
    if (filterJenis && txTbody) {
        filterJenis.addEventListener('change', function () {
            var val = filterJenis.value;
            txTbody.querySelectorAll('tr').forEach(function (row) {
                if (!row.hasAttribute('data-jenis')) return;
                row.style.display = (!val || row.getAttribute('data-jenis') === val) ? '' : 'none';
            });
        });
    }

    var searchUser = document.getElementById('search-user');
    var userTbody = document.getElementById('user-tbody');
    if (searchUser && userTbody) {
        searchUser.addEventListener('input', function () {
            var q = searchUser.value.trim().toLowerCase();
            userTbody.querySelectorAll('tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }

    var togglePwdBtn = document.getElementById('toggle-password');
    var passwordInput = document.getElementById('password');
    if (togglePwdBtn && passwordInput) {
        togglePwdBtn.addEventListener('click', function () {
            var isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            var icon = togglePwdBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = isHidden ? 'visibility_off' : 'visibility';
            }
            togglePwdBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
        });
    }
})();
