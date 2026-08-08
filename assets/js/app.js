/**
 * ULK Attendance System - shared front-end helpers
 * Loaded on every authenticated page (see includes/footer.php).
 */
(function () {
    'use strict';

    /* ---- CSRF token from <meta name="csrf-token"> ---- */
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var CSRF_TOKEN = csrf ? csrf.getAttribute('content') : '';

    /* ---- Mobile nav toggle ---- */
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('navToggle');
        var nav = document.getElementById('mobileNav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('hidden');
            });
        }
    });

    /**
     * api() - small fetch() wrapper that talks to the JSON endpoints.
     *  url     : relative api path, e.g. 'api/attendance.php'
     *  params  : object merged into the query string (GET) or JSON body (POST)
     *  options : { method, body } for low-level control
     */
    window.api = function (url, params) {
        var opts = params || {};
        var method = (opts.method || 'POST').toUpperCase();
        var qs = new URLSearchParams();
        for (var k in opts.query || {}) {
            if (Object.prototype.hasOwnProperty.call(opts.query, k)) qs.set(k, opts.query[k]);
        }
        var full = url + (qs.toString() ? '?' + qs.toString() : '');

        var headers = { 'X-CSRF-Token': CSRF_TOKEN };
        var body;
        if (method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(opts.body || {});
        }

        return fetch(full, { method: method, headers: headers, body: body })
            .then(function (resp) {
                return resp.json().then(function (json) {
                    if (!resp.ok && !json.ok) throw new Error(json.error || 'Request failed (' + resp.status + ')');
                    return json;
                });
            })
            .catch(function (err) {
                toast(err.message || 'Network error', 'error');
                throw err;
            });
    };

    /* ---- Toast notifications ---- */
    window.toast = function (message, type) {
        var wrap = document.getElementById('toast-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'toast-wrap';
            document.body.appendChild(wrap);
        }
        var colors = {
            success: 'bg-emerald-600 text-white',
            error: 'bg-rose-600 text-white',
            info: 'bg-slate-800 text-white',
        };
        var el = document.createElement('div');
        el.className = 'rounded-lg px-4 py-3 text-sm font-medium shadow-lg ' + (colors[type] || colors.info);
        el.textContent = message;
        wrap.appendChild(el);
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity .3s';
            setTimeout(function () { el.remove(); }, 320);
        }, 3200);
    };

    /* ---- Loading overlay ---- */
    var cover = null;
    window.showLoading = function (on) {
        if (!cover) {
            cover = document.createElement('div');
            cover.className = 'loading-cover';
            cover.innerHTML = '<div class="rounded-xl bg-white px-6 py-4 shadow-xl text-sm font-medium text-slate-700">Loading…</div>';
            document.body.appendChild(cover);
        }
        cover.classList.toggle('active', !!on);
    };

    /* ---- Status helpers for rendering badges ---- */
    window.buildBadge = function (status) {
        var map = {
            Present: 'badge badge-present',
            Absent: 'badge badge-absent',
            Late: 'badge badge-late',
            Excused: 'badge badge-excused',
            Open: 'badge badge-open',
            Closed: 'badge badge-closed',
        };
        return '<span class="' + (map[status] || 'badge') + '">' + status + '</span>';
    };

    window.esc = function (value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };
})();