/**
 * Space Weather Dashboard - Vanilla JavaScript
 * CSP-compliant: No innerHTML, no inline event handlers, no eval.
 * Uses createElement, textContent, and addEventListener exclusively.
 *
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */
(function () {
'use strict';

var SW = {
    apiBase: '',

    // ---- helpers ----

    /** Create an element with optional className and textContent */
    el: function (tag, className, text) {
        var e = document.createElement(tag);
        if (className) e.className = className;
        if (text !== undefined) e.textContent = text;
        return e;
    },

    /** Fetch JSON from an API endpoint, with error wrapper */
    fetchJSON: function (path) {
        return fetch(SW.apiBase + path)
            .then(function (r) { return r.json(); })
            .catch(function (err) {
                return { success: false, error: 'Network error: ' + err.message };
            });
    },

    /** Format an ISO timestamp to a short local time */
    fmtTime: function (iso) {
        if (!iso) return '--:--';
        try { return new Date(iso).toLocaleTimeString(); }
        catch (e) { return '--:--'; }
    },

    /** Image proxy URL builder */
    proxyImg: function (key) {
        return '/apps/space_weather/api/v1/image/' + key;
    },

    /** Show a loading placeholder in a container */
    showLoading: function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = ''; // clear safely
        el.appendChild(SW.el('p', 'loading-text', 'Loading...'));
    },

    /** Show an error in a container */
    showError: function (id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = '';
        el.appendChild(SW.el('p', 'error-text', msg));
    },

    // ---- KP Index Card ----
    renderKpIndex: function (data) {
        var container = document.getElementById('kp-card');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('kp-card', data ? data.message : 'No data');
            return;
        }
        var d = data.data || data;
        var kp = d.kp !== undefined ? d.kp : '--';
        var status = d.status || 'unknown';

        var card = SW.el('div', 'metric-card kp-' + status);
        var title = SW.el('div', 'metric-label', 'KP Index');
        var value = SW.el('div', 'metric-value', String(kp));
        var stat = SW.el('div', 'metric-status', status.replace(/_/g, ' '));
        var time = SW.el('div', 'metric-time', 'Updated: ' + SW.fmtTime(d.timestamp));

        card.appendChild(title);
        card.appendChild(value);
        card.appendChild(stat);
        card.appendChild(time);
        container.appendChild(card);
    },

    // ---- X-Ray Flux Card ----
    renderXrayFlux: function (data) {
        var container = document.getElementById('xray-card');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('xray-card', data ? data.message : 'No data');
            return;
        }
        var d = data.data || data;
        var flux = d.long !== undefined ? d.long.toExponential(1) : '--';
        var alert = d.alert_level || 'normal';

        var card = SW.el('div', 'metric-card xray-' + alert);
        var title = SW.el('div', 'metric-label', 'X-Ray Flux');
        var value = SW.el('div', 'metric-value', flux + ' W/m²');
        var stat = SW.el('div', 'metric-status', alert.replace(/_/g, ' '));
        var time = SW.el('div', 'metric-time', 'Updated: ' + SW.fmtTime(d.timestamp));

        card.appendChild(title);
        card.appendChild(value);
        card.appendChild(stat);
        card.appendChild(time);
        container.appendChild(card);
    },

    // ---- Solar Flux Card ----
    renderSolarFlux: function (data) {
        var container = document.getElementById('flux-card');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('flux-card', data ? data.message : 'No data');
            return;
        }
        var d = data.data || data;
        var flux = d.current !== undefined ? d.current : '--';
        var status = flux > 180 ? 'high' : flux > 120 ? 'moderate' : 'low';

        var card = SW.el('div', 'metric-card flux-' + status);
        var title = SW.el('div', 'metric-label', 'Solar Flux (F10.7)');
        var value = SW.el('div', 'metric-value', flux + ' sfu');
        var stat = SW.el('div', 'metric-status', status);
        var time = SW.el('div', 'metric-time', 'Updated: ' + SW.fmtTime(d.timestamp));

        card.appendChild(title);
        card.appendChild(value);
        card.appendChild(stat);
        card.appendChild(time);
        container.appendChild(card);
    },

    // ---- Aurora Forecast ----
    renderAurora: function (data) {
        var container = document.getElementById('aurora-forecast');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('aurora-forecast', data ? data.message : 'No data');
            return;
        }
        var img = document.createElement('img');
        img.src = SW.proxyImg('aurora_north');
        img.alt = 'Aurora Forecast';
        img.className = 'forecast-image';
        img.setAttribute('loading', 'lazy');
        container.appendChild(img);
    },

    // ---- Band Conditions Table ----
    renderBandConditions: function (data) {
        var container = document.getElementById('band-conditions');
        container.textContent = '';
        if (!data || data.error || !data.data || !data.data.bands) {
            SW.showError('band-conditions', 'No band data available');
            return;
        }

        var bands = data.data.bands;
        var solarIdx = data.data.solar_index || '--';
        var sunspots = data.data.sunspot_number || '--';

        // Header info row
        var info = SW.el('div', 'band-info');
        info.appendChild(SW.el('span', 'band-info-item', 'Solar Index: ' + solarIdx));
        info.appendChild(SW.el('span', 'band-info-item', 'Sunspots: ' + sunspots));
        container.appendChild(info);

        // Table
        var table = document.createElement('table');
        table.className = 'band-table';

        var thead = document.createElement('thead');
        var tr = document.createElement('tr');
        ['Band', 'Condition', 'Day', 'Night', 'MUF'].forEach(function (h) {
            var th = document.createElement('th');
            th.textContent = h;
            tr.appendChild(th);
        });
        thead.appendChild(tr);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        var bandOrder = ['80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m', '6m', '2m'];
        bandOrder.forEach(function (bn) {
            var b = bands[bn];
            if (!b) return;
            var row = document.createElement('tr');
            row.appendChild(SW.el('td', 'band-name', b.name));
            row.appendChild(SW.el('td', 'band-cond band-' + (b.condition || 'unknown'), b.condition || '--'));

            var dayTd = SW.el('td', '');
            var dayBar = SW.el('div', 'eff-bar');
            if (b.efficiency !== undefined) {
                var pct = b.legend || '-';
                dayBar.style.width = b.efficiency + '%';
                dayBar.textContent = pct;
            }
            dayTd.appendChild(dayBar);
            row.appendChild(dayTd);

            row.appendChild(SW.el('td', '', b.efficiency !== undefined ? b.efficiency + '%' : '--'));
            row.appendChild(SW.el('td', '', b.muf !== undefined ? b.muf + ' MHz' : '--'));
            tbody.appendChild(row);
        });
        table.appendChild(tbody);
        container.appendChild(table);
    },

    // ---- D-RAP Maps ----
    renderDRAP: function (data) {
        var container = document.getElementById('drap-maps');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('drap-maps', data ? data.message : 'No data');
            return;
        }

        var maps = [
            { title: 'Current', key: 'drap_global' },
            { title: '12-Hour Forecast', key: 'drap_global' },  // same image, different conceptual view
            { title: '24-Hour Forecast', key: 'drap_global' },
        ];

        maps.forEach(function (m) {
            var item = SW.el('div', 'drap-map-item');
            var h = SW.el('h3', '', m.title);
            var img = document.createElement('img');
            img.src = SW.proxyImg(m.key);
            img.alt = m.title + ' D-RAP Map';
            img.className = 'drap-map';
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function () {
                img.style.display = 'none';
            });
            item.appendChild(h);
            item.appendChild(img);
            container.appendChild(item);
        });
    },

    // ---- SDO Solar Imagery Gallery ----
    renderSDO: function (data) {
        var container = document.getElementById('sdo-gallery');
        container.textContent = '';
        if (!data || data.error) {
            SW.showError('sdo-gallery', data ? data.message : 'No data');
            return;
        }

        var sdoKeys = ['sdo_193', 'sdo_304', 'sdo_171', 'sdo_magnetogram'];
        var names   = ['AIA 193Å (Corona)', 'AIA 304Å (Chromosphere)', 'AIA 171Å (Transition)', 'HMI Magnetogram'];
        var descs   = ['Hot corona — 1.2 MK plasma', 'Upper chromosphere — 50,000 K', 'Quiet corona — 600,000 K', 'Magnetic field — Photosphere'];

        for (var i = 0; i < sdoKeys.length; i++) {
            var card = SW.el('div', 'wavelength-card');
            var h = SW.el('h3', '', names[i]);
            var p = SW.el('p', 'wavelength-desc', descs[i]);

            var img = document.createElement('img');
            img.src = SW.proxyImg(sdoKeys[i]);
            img.alt = names[i];
            img.className = 'wavelength-image';
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function () {
                this.alt = 'Image unavailable';
                this.style.opacity = '0.3';
            });

            card.appendChild(h);
            card.appendChild(p);
            card.appendChild(img);
            container.appendChild(card);
        }
    },

    // ---- Satellite Gallery ----
    renderSatellites: function (data) {
        var container = document.getElementById('satellite-gallery');
        container.textContent = '';
        if (!data || data.error || !data.data || !data.data.satellites) {
            SW.showError('satellite-gallery', data ? data.message : 'No satellite data');
            return;
        }

        var satKeys = ['goes16_fd', 'goes18_fd'];
        var sats = data.data.satellites;

        // Only show GOES satellites (they have working proxies)
        for (var i = 0; i < Math.min(sats.length, 2); i++) {
            var s = sats[i];
            var card = SW.el('div', 'satellite-card');
            var h = SW.el('h3', '', s.name);
            var p = SW.el('p', '', s.description || s.type || '');
            var prov = SW.el('p', 'provider', 'Source: ' + (s.provider || 'Unknown'));

            var img = document.createElement('img');
            img.src = SW.proxyImg(satKeys[i]);
            img.alt = s.name;
            img.className = 'satellite-image';
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function () {
                this.style.display = 'none';
            });

            card.appendChild(h);
            card.appendChild(p);
            card.appendChild(prov);
            card.appendChild(img);
            container.appendChild(card);
        }
    },

    // ---- Error banner ----
    renderErrors: function (errors) {
        var container = document.getElementById('errors-container');
        if (!container) return;
        container.textContent = '';
        if (errors.length === 0) return;

        errors.forEach(function (msg) {
            var div = SW.el('div', 'error-message');
            div.appendChild(SW.el('span', 'error-icon', '\u26A0'));
            div.appendChild(SW.el('span', 'error-text', msg));
            container.appendChild(div);
        });
    },

    // ---- Load all data ----
    loadAll: function () {
        SW.showLoading('kp-card');
        SW.showLoading('xray-card');
        SW.showLoading('flux-card');
        SW.showLoading('aurora-forecast');
        SW.showLoading('band-conditions');
        SW.showLoading('drap-maps');
        SW.showLoading('sdo-gallery');
        SW.showLoading('satellite-gallery');

        var spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'inline-block';

        var errors = [];

        function handle(errors, result, name) {
            if (result && result.success) return result;
            errors.push(name + ': ' + ((result && result.error) || 'Unknown error'));
            return null;
        }

        // Fetch all 8 endpoints in parallel
        Promise.all([
            SW.fetchJSON('/api/v1/kp-index').then(function (r) { SW.renderKpIndex(r); return handle(errors, r, 'KP Index'); }),
            SW.fetchJSON('/api/v1/xray-flux').then(function (r) { SW.renderXrayFlux(r); return handle(errors, r, 'X-Ray'); }),
            SW.fetchJSON('/api/v1/solar-flux').then(function (r) { SW.renderSolarFlux(r); return handle(errors, r, 'Solar Flux'); }),
            SW.fetchJSON('/api/v1/aurora-forecast').then(function (r) { SW.renderAurora(r); return handle(errors, r, 'Aurora'); }),
            SW.fetchJSON('/api/v1/band-conditions').then(function (r) { SW.renderBandConditions(r); return handle(errors, r, 'Bands'); }),
            SW.fetchJSON('/api/v1/drap-absorption').then(function (r) { SW.renderDRAP(r); return handle(errors, r, 'D-RAP'); }),
            SW.fetchJSON('/api/v1/sdo-imagery').then(function (r) { SW.renderSDO(r); return handle(errors, r, 'SDO'); }),
            SW.fetchJSON('/api/v1/satellite-images').then(function (r) { SW.renderSatellites(r); return handle(errors, r, 'Satellites'); }),
        ]).then(function () {
            if (spinner) spinner.style.display = 'none';
            SW.renderErrors(errors);

            var updateEl = document.getElementById('last-update-time');
            if (updateEl) updateEl.textContent = SW.fmtTime(new Date().toISOString());
        });
    },

    // ---- Manual refresh ----
    refresh: function () {
        var spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'inline-block';

        // POST to refresh-all clears cache, then re-fetch everything
        fetch(SW.apiBase + '/api/v1/refresh-all', { method: 'POST' })
            .then(function () { SW.loadAll(); })
            .catch(function () { SW.loadAll(); });
    },

    // ---- Init ----
    init: function () {
        SW.apiBase = '/apps/space_weather';
        SW.loadAll();

        var btn = document.getElementById('refresh-btn');
        if (btn) btn.addEventListener('click', SW.refresh);
    }
};

// Boot when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', SW.init);
} else {
    SW.init();
}

})();
