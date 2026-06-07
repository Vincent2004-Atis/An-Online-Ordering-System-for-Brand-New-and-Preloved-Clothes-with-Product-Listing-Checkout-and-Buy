<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireAdmin();
require_once '../config/database.php';
$db = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Marguax Collections Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,wght@0,400;0,600;0,700;0,900;1,400;1,700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ── Dashboard-specific extras ── */
.stats-grid-main { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.charts-row      { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:24px; }
.loading-row td  { text-align:center; padding:40px; color:var(--text-3); font-size:.9rem; }
.quick-actions   { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; }

/* SVG chart */
.svg-chart-wrap  { width:100%; overflow:hidden; }
.svg-chart-wrap svg { width:100%; display:block; }

/* Rev-dot hover */
.rev-dot { cursor:pointer; transition:r .15s; }
.rev-dot:hover { r:6; }

/* Doughnut legend already in admin.css */

/* Recent orders / top products grid */
.bottom-grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; }
@media(max-width:860px){ .charts-row,.bottom-grid{ grid-template-columns:1fr; } }

/* Decorative rose bar on chart cards (adds boutique feel) */
.chart-card { position:relative; }
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-content">

    <div class="admin-topbar">
      <span class="admin-topbar-title">📊 Dashboard</span>
      <div class="admin-topbar-actions">
        <select id="daysSelect" class="form-control" style="width:auto;padding:7px 14px;font-size:.8rem;" onchange="loadAll()">
          <option value="7">Last 7 days</option>
          <option value="14">Last 14 days</option>
          <option value="30" selected>Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="loadAll()">🔄 Refresh</button>
      </div>
    </div>

    <div class="admin-page">

      <!-- Stats Grid -->
      <div class="stats-grid-main" id="statsGrid">
        <?php for($i=0;$i<6;$i++): ?>
        <div class="stat-card">
          <div class="stat-icon stat-icon-rose">✦</div>
          <div><div class="stat-val">—</div><div class="stat-lbl">Loading…</div></div>
        </div>
        <?php endfor; ?>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <a href="manage_orders.php"   class="btn btn-primary">📋 Manage Orders</a>
        <a href="manage_products.php" class="btn btn-outline">🛍️ Products</a>
        <a href="manage_users.php"    class="btn btn-outline">👥 Users</a>
        <a href="messages.php"        class="btn btn-outline">💬 Messages</a>
        <a href="analytics.php"       class="btn btn-gold btn-sm" style="margin-left:auto;">📈 Full Analytics →</a>
      </div>

      <!-- Charts Row -->
      <div class="charts-row">
        <div class="chart-card">
          <h4>📈 Revenue — Last <span id="chartDaysLabel">30</span> days</h4>
          <div class="svg-chart-wrap" id="revenueChartWrap">
            <div class="chart-empty">Loading…</div>
          </div>
        </div>
        <div class="chart-card">
          <h4>🥧 Order Status</h4>
          <div id="statusChartWrap">
            <div class="chart-empty">Loading…</div>
          </div>
        </div>
      </div>

      <!-- Recent Orders + Top Products -->
      <div class="bottom-grid">
        <div class="chart-card">
          <h4>🕐 Recent Orders</h4>
          <div class="table-wrap">
            <table id="recentTable">
              <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
              <tbody><tr class="loading-row"><td colspan="5">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
        <div class="chart-card">
          <h4>🏆 Top Products</h4>
          <div id="topProducts"><div class="chart-empty">Loading…</div></div>
        </div>
      </div>

    </div><!-- /admin-page -->
  </div><!-- /admin-content -->
</div>

<!-- Tooltip -->
<div id="chartTooltip"></div>

<script>
// ─── Rose / gold palette ──────────────────────────────────────────────────────
const ROSE   = '#d4647a';
const GOLD   = '#c8a96a';
const GOLD2  = '#e8c87a';
const GREEN  = '#4caf82';
const AMBER  = '#e8b55a';
const TEXT   = 'rgba(254,249,244,.55)';
const GRID   = 'rgba(212,100,122,.08)';
const BORDER = 'rgba(196,160,168,.18)';

// ─── SVG Line Chart (Revenue) ─────────────────────────────────────────────────
function renderRevenueChart(rows) {
  const wrap = document.getElementById('revenueChartWrap');
  if (!rows || !rows.length) {
    wrap.innerHTML = '<div class="chart-empty">No revenue data for this period.</div>';
    return;
  }

  const W = 600, H = 210;
  const padL = 54, padR = 16, padT = 16, padB = 36;
  const chartW = W - padL - padR;
  const chartH = H - padT - padB;

  const values = rows.map(r => parseFloat(r.revenue));
  const labels = rows.map(r => { const d=new Date(r.day); return (d.getMonth()+1)+'/'+d.getDate(); });
  const maxVal = Math.max(...values, 1);
  const range  = maxVal;

  let gridLines = '', yLabels = '';
  for (let i=0; i<=4; i++) {
    const v  = maxVal * i / 4;
    const cy = padT + chartH - (chartH * i / 4);
    gridLines += `<line x1="${padL}" y1="${cy}" x2="${W-padR}" y2="${cy}" stroke="rgba(212,100,122,.1)" stroke-width="1"/>`;
    yLabels   += `<text x="${padL-6}" y="${cy+4}" text-anchor="end" font-size="9" fill="${TEXT}">₱${v>=1000?(v/1000).toFixed(1)+'k':v.toFixed(0)}</text>`;
  }

  const pts = rows.map((r,i) => ({
    x: padL + (chartW * i / Math.max(rows.length-1,1)),
    y: padT + chartH - (chartH * parseFloat(r.revenue) / (range||1)),
    label: labels[i], val: parseFloat(r.revenue)
  }));

  const linePath = pts.map((p,i) => (i===0?`M${p.x},${p.y}`:`L${p.x},${p.y}`)).join(' ');
  const fillPath = linePath + ` L${pts[pts.length-1].x},${padT+chartH} L${padL},${padT+chartH} Z`;

  const step = Math.ceil(rows.length/7);
  let xLabels = '';
  pts.forEach((p,i) => {
    if (i%step===0||i===pts.length-1)
      xLabels += `<text x="${p.x}" y="${H-4}" text-anchor="middle" font-size="9" fill="${TEXT}">${p.label}</text>`;
  });

  let hitAreas = '';
  pts.forEach(p => {
    hitAreas += `<rect x="${p.x-14}" y="${padT}" width="28" height="${chartH}" fill="transparent" class="rev-hit" data-val="${p.val}" data-label="${p.label}"/>`;
  });

  const circles = pts.map(p =>
    `<circle cx="${p.x}" cy="${p.y}" r="4" fill="${ROSE}" stroke="rgba(14,11,13,.8)" stroke-width="2" class="rev-dot" data-val="${p.val}"/>`
  ).join('');

  wrap.innerHTML = `
    <svg viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="${ROSE}" stop-opacity="0.22"/>
          <stop offset="100%" stop-color="${ROSE}" stop-opacity="0.01"/>
        </linearGradient>
      </defs>
      ${gridLines}${yLabels}
      <path d="${fillPath}" fill="url(#revGrad)"/>
      <path d="${linePath}" fill="none" stroke="${ROSE}" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>
      ${circles}${xLabels}${hitAreas}
    </svg>`;

  const tooltip = document.getElementById('chartTooltip');
  wrap.querySelectorAll('.rev-hit').forEach(el => {
    el.addEventListener('mousemove', e => {
      tooltip.textContent = `${el.dataset.label}  ₱${fmt(el.dataset.val)}`;
      tooltip.style.opacity = '1';
      tooltip.style.left = (e.clientX+14)+'px';
      tooltip.style.top  = (e.clientY-30)+'px';
    });
    el.addEventListener('mouseleave', () => { tooltip.style.opacity='0'; });
  });
}

// ─── SVG Doughnut Chart (Order Status) ───────────────────────────────────────
function renderStatusChart(dist) {
  const wrap = document.getElementById('statusChartWrap');
  if (!dist || !dist.length) {
    wrap.innerHTML = '<div class="chart-empty">No orders yet.</div>';
    return;
  }
  const colors = { pending: AMBER, processing: ROSE, completed: GREEN, cancelled: 'rgba(255,255,255,.18)' };
  const colorList = [ROSE, GOLD, GREEN, AMBER, '#c084fc'];
  const total = dist.reduce((s,d) => s+parseInt(d.count),0);
  const cx=80, cy=80, R=68, r=42, W=180, H=170;

  let slices='', angle=-Math.PI/2;
  dist.forEach((d,i) => {
    const count = parseInt(d.count);
    const sweep = (count/total)*2*Math.PI;
    const endA  = angle+sweep;
    const color = colors[d.order_status]||colorList[i%colorList.length];
    const x1=cx+R*Math.cos(angle), y1=cy+R*Math.sin(angle);
    const x2=cx+R*Math.cos(endA),  y2=cy+R*Math.sin(endA);
    const xi1=cx+r*Math.cos(angle),yi1=cy+r*Math.sin(angle);
    const xi2=cx+r*Math.cos(endA), yi2=cy+r*Math.sin(endA);
    const large=sweep>Math.PI?1:0;
    slices += `<path d="M${xi1},${yi1} L${x1},${y1} A${R},${R} 0 ${large},1 ${x2},${y2} L${xi2},${yi2} A${r},${r} 0 ${large},0 ${xi1},${yi1} Z"
      fill="${color}" stroke="rgba(14,11,13,.6)" stroke-width="2"><title>${ucfirst(d.order_status)}: ${count}</title></path>`;
    angle = endA;
  });

  const centerLabel = `
    <text x="${cx}" y="${cy-4}" text-anchor="middle" font-size="22" font-weight="700" fill="#fef9f4" font-family="Bodoni Moda,serif">${total}</text>
    <text x="${cx}" y="${cy+14}" text-anchor="middle" font-size="8.5" fill="rgba(254,249,244,.42)" letter-spacing="1">TOTAL ORDERS</text>`;

  const legend = dist.map((d,i) => {
    const color = colors[d.order_status]||colorList[i%colorList.length];
    const pct   = ((parseInt(d.count)/total)*100).toFixed(0);
    return `<div class="donut-legend-item">
      <div class="donut-legend-dot" style="background:${color}"></div>
      <span>${ucfirst(d.order_status)}</span>
      <span style="margin-left:auto;font-weight:700;color:#fef9f4;">${d.count}
        <span style="color:rgba(254,249,244,.38);font-weight:400;">(${pct}%)</span></span>
    </div>`;
  }).join('');

  wrap.innerHTML = `
    <div style="display:flex;flex-direction:column;align-items:center;">
      <svg viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">${slices}${centerLabel}</svg>
    </div>
    <div class="donut-legend">${legend}</div>`;
}

// ─── Stats Grid ───────────────────────────────────────────────────────────────
function renderStats(s) {
  const items = [
    { icon:'💰', val:'₱'+fmt(s.total_revenue), lbl:'Total Revenue',  cls:'stat-icon-green' },
    { icon:'📋', val:s.total_orders,            lbl:'Total Orders',   cls:'stat-icon-rose'  },
    { icon:'⏳', val:s.pending_orders,           lbl:'Pending Orders', cls:'stat-icon-amber' },
    { icon:'✅', val:s.completed_orders,         lbl:'Completed',      cls:'stat-icon-green' },
    { icon:'👥', val:s.total_customers,          lbl:'Customers',      cls:'stat-icon-rose'  },
    
  ];
  document.getElementById('statsGrid').innerHTML = items.map(i => `
    <div class="stat-card">
      <div class="stat-icon ${i.cls}">${i.icon}</div>
      <div><div class="stat-val">${i.val}</div><div class="stat-lbl">${i.lbl}</div></div>
    </div>`).join('');
}

// ─── Recent Orders ────────────────────────────────────────────────────────────
function renderRecentOrders(orders) {
  const tbody = document.querySelector('#recentTable tbody');
  if (!orders || !orders.length) {
    tbody.innerHTML='<tr class="loading-row"><td colspan="5">No orders yet.</td></tr>'; return;
  }
  const badges = { pending:'badge-amber', processing:'badge-rose', completed:'badge-green' };
  tbody.innerHTML = orders.map(o => `
    <tr>
      <td><strong style="color:var(--text);">#${o.order_id}</strong><br>
          <span style="color:var(--gold);font-size:.75rem;font-weight:700;">Q${String(o.queue_number).padStart(3,'0')}</span></td>
      <td>${esc(o.customer_name)}</td>
      <td><strong style="color:var(--rose);">₱${fmt(o.total_amount)}</strong></td>
      <td><span class="badge ${badges[o.order_status]||'badge-gray'}">${ucfirst(o.order_status)}</span></td>
      <td style="font-size:.74rem;color:var(--text-3);">${fmtDate(o.order_date)}</td>
    </tr>`).join('');
}

// ─── Top Products ─────────────────────────────────────────────────────────────
function renderTopProducts(products) {
  const el = document.getElementById('topProducts');
  if (!products || !products.length) {
    el.innerHTML='<div class="chart-empty">No sales data yet.</div>'; return;
  }
  const max = Math.max(...products.map(p=>parseInt(p.units_sold)));
  el.innerHTML = products.map((p,i) => `
    <div style="margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:5px;">
        <span style="font-weight:600;color:var(--text);">${i+1}. ${esc(p.product_name)}</span>
        <span style="color:var(--gold);">${p.units_sold} sold</span>
      </div>
      <div style="height:6px;background:rgba(255,255,255,.07);border-radius:3px;">
        <div style="height:6px;border-radius:3px;width:${Math.round(parseInt(p.units_sold)/max*100)}%;
                    background:linear-gradient(90deg,${ROSE},${GOLD});transition:width .6s ease;"></div>
      </div>
    </div>`).join('');
}

// ─── Main Loader ──────────────────────────────────────────────────────────────
async function loadAll() {
  const days = document.getElementById('daysSelect').value;
  document.getElementById('chartDaysLabel').textContent = days;
  try {
    const res  = await fetch(`/Marguax_Collection/api/dashboard.php?days=${days}`);
    const data = await res.json();
    if (!data.success) return;
    renderStats(data.stats);
    renderRevenueChart(data.revenue);
    renderStatusChart(data.status_dist);
    renderRecentOrders(data.recent_orders);
    renderTopProducts(data.top_products);
  } catch(e) { console.error('Dashboard load error:', e); }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fmt(n)      { return parseFloat(n).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function ucfirst(s)  { return s ? s.charAt(0).toUpperCase()+s.slice(1) : ''; }
function esc(s)      { const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
function fmtDate(ds) { return ds ? new Date(ds).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}) : '—'; }

loadAll();
</script>
</body>
</html>s
