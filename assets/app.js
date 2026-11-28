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
    };

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
