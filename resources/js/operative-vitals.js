/**
 * Operative Vitals — Simple line chart for intra-op vital signs.
 * Uses native Canvas API (no external charting library required).
 */

document.addEventListener('DOMContentLoaded', function () {
    var chartEl = document.getElementById('vitals-chart');
    if (!chartEl) return;

    var url = chartEl.dataset.url;
    if (!url) return;

    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (!data || data.length === 0) return;
        renderChart(chartEl, data);
    })
    .catch(function () { /* silent */ });

    function renderChart(container, data) {
        // Build a simple tabular text-based visualization if <5 entries
        // For proper use, render canvas chart
        var canvas = document.createElement('canvas');
        canvas.width = container.clientWidth;
        canvas.height = container.clientHeight || 240;
        container.innerHTML = '';
        container.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        var w = canvas.width;
        var h = canvas.height;
        var padding = { top: 20, right: 20, bottom: 30, left: 40 };
        var plotW = w - padding.left - padding.right;
        var plotH = h - padding.top - padding.bottom;
        var n = data.length;

        if (n < 2) {
            ctx.fillStyle = '#9ca3af';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('At least 2 data points needed for chart', w / 2, h / 2);
            return;
        }

        // Extract series
        var series = [
            { key: 'hr', label: 'HR', color: '#ef4444' },
            { key: 'spo2', label: 'SpO2', color: '#3b82f6' },
            { key: 'systolic', label: 'Sys', color: '#10b981' },
        ];

        // Find min/max across all series
        var allVals = [];
        series.forEach(function (s) {
            data.forEach(function (d) {
                var v = parseInt(d[s.key]);
                if (!isNaN(v)) allVals.push(v);
            });
        });
        var minVal = Math.min.apply(null, allVals) - 10;
        var maxVal = Math.max.apply(null, allVals) + 10;
        var range = maxVal - minVal || 1;

        // Draw axes
        ctx.strokeStyle = '#e5e7eb';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(padding.left, padding.top);
        ctx.lineTo(padding.left, h - padding.bottom);
        ctx.lineTo(w - padding.right, h - padding.bottom);
        ctx.stroke();

        // Y-axis labels
        ctx.fillStyle = '#6b7280';
        ctx.font = '10px sans-serif';
        ctx.textAlign = 'right';
        for (var i = 0; i <= 4; i++) {
            var yVal = Math.round(minVal + (range * i / 4));
            var y = h - padding.bottom - (plotH * i / 4);
            ctx.fillText(yVal.toString(), padding.left - 5, y + 3);
            // Grid line
            ctx.strokeStyle = '#f3f4f6';
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(w - padding.right, y);
            ctx.stroke();
        }

        // X-axis labels (time)
        ctx.textAlign = 'center';
        ctx.fillStyle = '#6b7280';
        data.forEach(function (d, idx) {
            var x = padding.left + (plotW * idx / (n - 1));
            ctx.fillText(d.time || '', x, h - padding.bottom + 15);
        });

        // Draw series lines
        series.forEach(function (s) {
            ctx.strokeStyle = s.color;
            ctx.lineWidth = 2;
            ctx.beginPath();
            var started = false;
            data.forEach(function (d, idx) {
                var v = parseInt(d[s.key]);
                if (isNaN(v)) return;
                var x = padding.left + (plotW * idx / (n - 1));
                var y = h - padding.bottom - ((v - minVal) / range * plotH);
                if (!started) { ctx.moveTo(x, y); started = true; }
                else { ctx.lineTo(x, y); }
            });
            ctx.stroke();
        });

        // Legend
        var legendX = padding.left + 10;
        series.forEach(function (s, idx) {
            var lx = legendX + idx * 70;
            ctx.fillStyle = s.color;
            ctx.fillRect(lx, 5, 12, 12);
            ctx.fillStyle = '#374151';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'left';
            ctx.fillText(s.label, lx + 16, 14);
        });
    }
});
