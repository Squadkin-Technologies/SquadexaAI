/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * SquadexaAI Dashboard — Chart and Interaction JS
 */

(function() {
  'use strict';

  // SVG icons are loaded from window.__dashboardIcons (set in index.phtml)
  const icons = window.__dashboardIcons || {};

  // ====================================================
  // CHART RENDERING
  // ====================================================

  function drawChart(containerId, data) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const width = container.offsetWidth || 600;
    const height = 210;
    const padding = { left: 38, right: 12, top: 14, bottom: 26 };
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;

    // Get accent color from CSS variable or default
    const computedStyle = getComputedStyle(document.documentElement);
    const accentColor = computedStyle.getPropertyValue('--accent').trim() || '#6366f1';

    // Find max value for scaling
    const values = data.map(d => d.value || 0);
    const maxValue = Math.max(...values, 100);
    const step = Math.ceil(maxValue / 4);
    const maxY = step * 4;

    // Create SVG
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    svg.setAttribute('style', 'width:100%;height:100%');

    // Draw gridlines
    const gridColor = 'rgba(232, 234, 240, 0.5)';
    for (let i = 0; i <= 4; i++) {
      const y = padding.top + (plotHeight / 4) * i;
      const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      line.setAttribute('x1', padding.left);
      line.setAttribute('y1', y);
      line.setAttribute('x2', width - padding.right);
      line.setAttribute('y2', y);
      line.setAttribute('stroke', gridColor);
      line.setAttribute('stroke-width', i === 4 ? '1' : '1');
      line.setAttribute('stroke-dasharray', i === 4 ? '0' : '4,4');
      svg.appendChild(line);

      // Y-axis labels
      const value = maxY - (i * step);
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', padding.left - 8);
      text.setAttribute('y', y + 4);
      text.setAttribute('text-anchor', 'end');
      text.setAttribute('font-size', '11');
      text.setAttribute('fill', '#97a0b0');
      text.textContent = formatValue(value);
      svg.appendChild(text);
    }

    // Calculate points
    const points = data.map((d, idx) => {
      const x = padding.left + (plotWidth / (data.length - 1 || 1)) * idx;
      const y = padding.top + plotHeight - (plotHeight / maxY) * (d.value || 0);
      return { x, y, value: d.value };
    });

    // Draw area fill
    const pathData = [
      `M ${padding.left} ${padding.top + plotHeight}`,
      ...points.map((p, idx) => `${idx === 0 ? 'L' : ''} ${p.x} ${p.y}`),
      `L ${width - padding.right} ${padding.top + plotHeight}`,
      'Z'
    ].join(' ');

    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
    gradient.setAttribute('id', 'chartGradient');
    gradient.setAttribute('x1', '0%');
    gradient.setAttribute('y1', '0%');
    gradient.setAttribute('x2', '0%');
    gradient.setAttribute('y2', '100%');
    const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop1.setAttribute('offset', '0%');
    stop1.setAttribute('stop-color', accentColor);
    stop1.setAttribute('stop-opacity', '0.26');
    const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop2.setAttribute('offset', '100%');
    stop2.setAttribute('stop-color', accentColor);
    stop2.setAttribute('stop-opacity', '0');
    gradient.appendChild(stop1);
    gradient.appendChild(stop2);
    defs.appendChild(gradient);
    svg.appendChild(defs);

    const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    area.setAttribute('d', pathData);
    area.setAttribute('fill', 'url(#chartGradient)');
    svg.appendChild(area);

    // Draw line
    const linePath = points.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    line.setAttribute('d', linePath);
    line.setAttribute('fill', 'none');
    line.setAttribute('stroke', accentColor);
    line.setAttribute('stroke-width', '2.5');
    line.setAttribute('stroke-linecap', 'round');
    line.setAttribute('stroke-linejoin', 'round');
    svg.appendChild(line);

    // Draw data points
    points.forEach(p => {
      const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', p.x);
      circle.setAttribute('cy', p.y);
      circle.setAttribute('r', '3');
      circle.setAttribute('fill', 'var(--surface)');
      circle.setAttribute('stroke', accentColor);
      circle.setAttribute('stroke-width', '2');
      svg.appendChild(circle);
    });

    // X-axis labels (months)
    data.forEach((d, idx) => {
      if (idx % Math.ceil(data.length / 5) === 0 || idx === data.length - 1) {
        const x = padding.left + (plotWidth / (data.length - 1 || 1)) * idx;
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', x);
        text.setAttribute('y', height - 5);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', '11');
        text.setAttribute('fill', '#97a0b0');
        text.textContent = d.label || '';
        svg.appendChild(text);
      }
    });

    container.innerHTML = '';
    container.appendChild(svg);
  }

  function formatValue(val) {
    if (val >= 1000) return Math.round(val / 1000) + 'k';
    return val.toString();
  }

  // ====================================================
  // COPY TO CLIPBOARD
  // ====================================================

  function setupCopyButtons() {
    document.querySelectorAll('[data-copy-trigger]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('data-copy-trigger');
        const target = document.getElementById(targetId);
        if (!target) return;

        const text = target.textContent || target.value;
        navigator.clipboard.writeText(text).then(() => {
          const originalHtml = this.innerHTML;
          const originalClass = this.getAttribute('class');
          this.innerHTML = icons.checkCircle;
          this.classList.add('copied');

          setTimeout(() => {
            this.innerHTML = originalHtml;
            this.setAttribute('class', originalClass);
          }, 1200);
        });
      });
    });
  }

  // ====================================================
  // ICON INJECTION
  // ====================================================

  function injectIcons() {
    Object.entries(icons).forEach(([id, svg]) => {
      const el = document.getElementById(`icon-${id}`);
      if (el) el.innerHTML = svg;
    });
  }

  // ====================================================
  // INITIALIZATION
  // ====================================================

  document.addEventListener('DOMContentLoaded', function() {
    injectIcons();
    setupCopyButtons();

    // Draw chart if data exists
    const chartData = window.__dashboardChartData;
    if (chartData) {
      drawChart('chart', chartData);
      window.redrawChart = () => drawChart('chart', chartData);
    }

    // Redraw on resize (debounced)
    let resizeTimeout;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        if (window.redrawChart) window.redrawChart();
      }, 120);
    });
  });

  // Export for external use
  window.SquadexaDashboard = {
    drawChart: drawChart,
    icons: icons
  };
})();
