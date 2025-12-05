(function () {
  /* -------------------------
     tiny toast
  ------------------------- */
  const toastEl = document.getElementById("toastLite");
  let toastTimer = null;
  function toast(msg){
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove("show"), 1400);
  }

  /* -------------------------
     helpers
  ------------------------- */
  function escapeHtml(str) {
    return (str ?? "").toString()
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function escapeCSV(v){
    const s = String(v ?? "");
    if (/[",\n]/.test(s)) return `"${s.replaceAll('"','""')}"`;
    return s;
  }

  /* -------------------------
     export dropdown
  ------------------------- */
  const exportWrap = document.querySelector(".export");
  const exportBtn = document.getElementById("exportBtn");
  const exportMenu = document.getElementById("exportMenu");

  function setExportOpen(open){
    if (!exportWrap || !exportBtn || !exportMenu) return;
    exportWrap.classList.toggle("open", open);
    exportBtn.setAttribute("aria-expanded", open ? "true" : "false");
    exportMenu.setAttribute("aria-hidden", open ? "false" : "true");
  }

  exportBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    setExportOpen(!exportWrap.classList.contains("open"));
  });

  document.addEventListener("click", () => setExportOpen(false));

  /* -------------------------
     copy event code
  ------------------------- */
  const codeBtn = document.getElementById("eventCodeCopy");
  async function copyCode(){
    const code = (codeBtn?.getAttribute("data-code") || "").trim();
    if (!code || code === "—") { toast("No event code to copy."); return; }

    try{
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(code);
      } else {
        const ta = document.createElement("textarea");
        ta.value = code;
        ta.style.position = "fixed";
        ta.style.left = "-9999px";
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        document.execCommand("copy");
        ta.remove();
      }
      toast("Event code copied!");
    } catch {
      window.prompt("Copy Event Code:", code);
      toast("Copy manually (shown in prompt).");
    }
  }
  codeBtn?.addEventListener("click", copyCode);
  codeBtn?.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") { e.preventDefault(); copyCode(); }
  });

  /* -------------------------
     PRINT: values-only report
  ------------------------- */
  function openPrintWindow(title, html){
    const w = window.open("", "_blank", "noopener,noreferrer,width=1000,height=700");
    if (!w) { toast("Popup blocked. Allow popups to print."); return null; }

    w.document.open();
    w.document.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>${escapeHtml(title || "Event Summary")}</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800;900&display=swap" rel="stylesheet" />
  <style>
    :root { --ink:#111827; --muted:#6b7280; --red:#b23a45; --line:rgba(17,24,39,.12); }
    *{ box-sizing:border-box; }
    body{ font-family: Nunito,Arial; margin: 26px; color:var(--ink); }
    .h1{ font-size: 22px; font-weight: 900; margin: 0 0 6px; }
    .muted{ color:var(--muted); font-weight:700; }
    .row{ display:flex; gap:12px; flex-wrap:wrap; margin: 14px 0 8px; }
    .box{ border:1px solid var(--line); border-radius:12px; padding:12px 14px; min-width: 220px; flex: 1 1 220px; }
    .k{ font-weight: 900; color:var(--red); margin-bottom:6px; font-size: 12px; letter-spacing:.02em; text-transform:uppercase; }
    .v{ font-weight: 800; font-size: 14px; }
    .section{ margin-top: 18px; }
    .h2{ font-size: 15px; font-weight: 900; margin: 0 0 10px; }
    table{ width:100%; border-collapse:collapse; margin-top: 10px; }
    th,td{ border:1px solid var(--line); padding:10px; text-align:left; vertical-align:top;}
    th{ background:rgba(17,24,39,.04); font-weight:900;}
    .small{ font-size: 12px; }
  </style>
</head>
<body>
  ${html}
  <script>
    window.onload = () => { window.print(); window.onafterprint = () => window.close(); };
  </script>
</body>
</html>`);
    w.document.close();
    return w;
  }

  function buildPrintHTML(){
    const meta = window.EVENT_SUMMARY_META || {};
    const chart = Array.isArray(window.EVENT_SUMMARY_CHART) ? window.EVENT_SUMMARY_CHART : [];
    const mode = (window.EVENT_SUMMARY_CHART_MODE || meta.chart_mode || "actual").toString().toLowerCase();
    const modeLabel = mode === "actual" ? "Actual Attendance" : "Expected Volunteers";

    const title = meta.title || "Event Summary";

    const chartRows = chart.map(s => `
      <tr>
        <td>${escapeHtml(s.label ?? "Unknown")}</td>
        <td>${escapeHtml((s.percentage ?? "").toString())}${(s.percentage != null ? "%" : "")}</td>
        <td>${escapeHtml((s.count ?? "").toString())}</td>
      </tr>
    `).join("");

    return `
      <div class="h1">${escapeHtml(title)}</div>
      <div class="muted small">
        Code: ${escapeHtml(meta.event_code || "—")} •
        Status: ${escapeHtml(meta.status || "completed")} •
        Generated: ${escapeHtml(meta.generated_at || "")}
      </div>

      <div class="row">
        <div class="box"><div class="k">Date</div><div class="v">${escapeHtml(meta.date || "—")}</div></div>
        <div class="box"><div class="k">Time</div><div class="v">${escapeHtml(meta.start_time || "")} - ${escapeHtml(meta.end_time || "")}</div></div>
        <div class="box"><div class="k">Venue</div><div class="v">${escapeHtml(meta.venue || "—")}</div></div>
        <div class="box"><div class="k">Location</div><div class="v">${escapeHtml(meta.barangay || "—")} • District ${escapeHtml(String(meta.district ?? ""))}</div></div>
        <div class="box"><div class="k">Expected</div><div class="v">${escapeHtml(String(meta.expected ?? 0))}</div></div>
        <div class="box"><div class="k">Attended</div><div class="v">${escapeHtml(String(meta.attended ?? 0))}</div></div>
        <div class="box"><div class="k">Attendance Rate</div><div class="v">${escapeHtml(String(meta.attendance_rate ?? 0))}%</div></div>
      </div>

      <div class="section">
        <div class="h2">Distribution (${escapeHtml(modeLabel)})</div>
        <table>
          <thead><tr><th>Label</th><th>Percent</th><th>Count</th></tr></thead>
          <tbody>
            ${chartRows || `<tr><td colspan="3" class="muted">No distribution data.</td></tr>`}
          </tbody>
        </table>
      </div>
    `;
  }

  document.getElementById("exportPrint")?.addEventListener("click", () => {
    setExportOpen(false);
    const meta = window.EVENT_SUMMARY_META || {};
    openPrintWindow(meta.title || "Event Summary", buildPrintHTML());
  });

  /* -------------------------
     PDF DOWNLOAD (html2pdf) — ONLY #printArea
  ------------------------- */
  function withExportMode(fn){
    document.documentElement.classList.add("is-exporting");
    document.body.classList.add("is-exporting");
    try { return fn(); }
    finally {
      setTimeout(() => {
        document.documentElement.classList.remove("is-exporting");
        document.body.classList.remove("is-exporting");
      }, 0);
    }
  }

  document.getElementById("exportPDF")?.addEventListener("click", async () => {
    setExportOpen(false);

    const printArea = document.getElementById("printArea");
    if (!printArea) { toast("Missing print area (#printArea)."); return; }
    if (typeof html2pdf === "undefined") { toast("PDF export unavailable (html2pdf missing)."); return; }

    const meta = window.EVENT_SUMMARY_META || {};
    const fileBase = (meta.event_code || meta.title || "event_summary").toString()
      .replace(/[^\w\-]+/g, "_")
      .slice(0, 60);

    const opt = {
      margin:       [10, 10, 10, 10],
      filename:     `${fileBase}_summary.pdf`,
      image:        { type: "jpeg", quality: 0.95 },
      html2canvas:  { scale: 2, useCORS: true, backgroundColor: "#ffffff" },
      jsPDF:        { unit: "mm", format: "a4", orientation: "portrait" },
      pagebreak:    { mode: ["css", "legacy"] }
    };

    toast("Generating PDF...");

    try{
      await withExportMode(async () => {
        const clone = printArea.cloneNode(true);
        clone.querySelectorAll(".print-only").forEach(el => el.style.display = "block");

        const holder = document.createElement("div");
        holder.style.position = "fixed";
        holder.style.left = "-99999px";
        holder.style.top = "0";
        holder.style.width = printArea.getBoundingClientRect().width + "px";
        holder.appendChild(clone);
        document.body.appendChild(holder);

        await html2pdf().set(opt).from(clone).save();
        holder.remove();
      });

      toast("PDF downloaded!");
    } catch (e){
      console.error(e);
      toast("PDF failed. Check console.");
    }
  });

  /* -------------------------
     CSV export
  ------------------------- */
  document.getElementById("exportCSV")?.addEventListener("click", () => {
    setExportOpen(false);
    exportCSV();
  });

  function exportCSV(){
    const meta = window.EVENT_SUMMARY_META || {};
    const chart = Array.isArray(window.EVENT_SUMMARY_CHART) ? window.EVENT_SUMMARY_CHART : [];

    const rows = [];
    rows.push(["section","key","value"]);

    Object.entries(meta).forEach(([k,v]) => rows.push(["meta", k, String(v ?? "")]));

    chart.forEach((s, idx) => {
      rows.push(["chart", `label_${idx+1}`, String(s.label ?? "")]);
      if (s.percentage != null) rows.push(["chart", `percentage_${idx+1}`, String(s.percentage)]);
      if (s.count != null) rows.push(["chart", `count_${idx+1}`, String(s.count)]);
    });

    const csv = rows.map(r => r.map(escapeCSV).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });

    const fileBase = (meta.event_code || meta.title || "event_summary").toString()
      .replace(/[^\w\-]+/g, "_")
      .slice(0, 60);
    const filename = `${fileBase}_summary.csv`;

    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 500);

    toast("CSV exported!");
  }

  /* -------------------------
     pie chart render
  ------------------------- */
  const chartEl = document.querySelector(".chart");
  const legend = document.getElementById("chartLegend");
  const tooltip = document.getElementById("chartTooltip");
  const centerTop = document.getElementById("chartCenterTop");
  const centerSub = document.getElementById("chartCenterSub");
  const hintEl = document.getElementById("chartHint");
  if (!chartEl) return;

  const mode = (window.EVENT_SUMMARY_CHART_MODE || "actual").toString().toLowerCase();
  const modeLabel = mode === "actual" ? "Attended" : "Expected";

  if (centerSub) centerSub.textContent = modeLabel;
  if (hintEl && window.EVENT_SUMMARY_CHART_HINT) hintEl.textContent = String(window.EVENT_SUMMARY_CHART_HINT);

  const raw = Array.isArray(window.EVENT_SUMMARY_CHART) ? window.EVENT_SUMMARY_CHART : [];

  const totalExpected = Number(window.EVENT_SUMMARY_TOTAL_EXPECTED || 0);
  const totalAttended = Number(window.EVENT_SUMMARY_TOTAL_ATTENDED || 0);

  if (centerTop) {
    centerTop.textContent = String(mode === "actual" ? (totalAttended || 0) : (totalExpected || 0));
  }

  let data = raw.map(d => ({
    label: (d.label ?? "Unknown").toString(),
    color: (d.color ?? "#9ca3af").toString(), // ✅ controller picks theme-friendly colors now
    percentage: (d.percentage != null ? Number(d.percentage) : null),
    count: (d.count != null ? Number(d.count) : null),
  }));

  const hasPct = data.some(x => typeof x.percentage === "number" && isFinite(x.percentage));
  if (!hasPct && data.some(x => typeof x.count === "number" && isFinite(x.count))) {
    const sum = data.reduce((s, x) => s + (isFinite(x.count) ? Math.max(0, x.count) : 0), 0);
    data = data.map(x => ({ ...x, percentage: sum ? (Math.max(0, x.count || 0) / sum) * 100 : 0 }));
  }

  data = data
    .map(x => ({ ...x, percentage: Math.max(0, Number(x.percentage) || 0) }))
    .filter(x => x.percentage > 0.0001);

  if (!data.length) {
    chartEl.style.background = "conic-gradient(rgba(17,24,39,.10) 0deg 360deg)";
    if (legend) {
      legend.innerHTML = `
        <div class="legend-item" style="justify-content:center;">
          <div class="legend-main" style="text-align:center;width:100%;">
            <div class="legend-label">No distribution data</div>
            <div class="legend-sub">Import attendance to populate this chart</div>
          </div>
        </div>
      `;
    }
    return;
  }

  const sumPct = data.reduce((s, x) => s + x.percentage, 0) || 1;
  const scaled = data.map(x => ({ ...x, percentage: (x.percentage / sumPct) * 100 }));

  let startDeg = 0;
  const ranges = [];
  const segments = scaled.map(seg => {
    const endDeg = startDeg + (seg.percentage / 100) * 360;
    ranges.push({ start: startDeg, end: endDeg, ...seg });
    const css = `${seg.color} ${startDeg}deg ${endDeg}deg`;
    startDeg = endDeg;
    return css;
  });

  chartEl.style.background = `conic-gradient(${segments.join(", ")})`;

  if (legend) {
    legend.innerHTML = scaled.map(seg => {
      const pct = Math.round(seg.percentage * 10) / 10;
      const countTxt = (seg.count != null && isFinite(seg.count)) ? `• ${seg.count}` : "";
      return `
        <div class="legend-item">
          <div class="color-box" style="background:${seg.color}"></div>
          <div class="legend-main">
            <div class="legend-label">${escapeHtml(seg.label)}</div>
            <div class="legend-sub">${pct}% ${countTxt}</div>
          </div>
        </div>
      `;
    }).join("");
  }

  if (tooltip) {
    const getAngleDeg = (evt) => {
      const rect = chartEl.getBoundingClientRect();
      const cx = rect.left + rect.width / 2;
      const cy = rect.top + rect.height / 2;
      const dx = evt.clientX - cx;
      const dy = evt.clientY - cy;
      let deg = (Math.atan2(dy, dx) * 180) / Math.PI;
      deg += 90;
      if (deg < 0) deg += 360;
      return deg;
    };

    const findSeg = (deg) =>
      ranges.find(r => deg >= r.start && deg < r.end) || ranges[ranges.length - 1];

    chartEl.addEventListener("mousemove", (evt) => {
      const deg = getAngleDeg(evt);
      const seg = findSeg(deg);
      if (!seg) return;

      const pct = Math.round(seg.percentage * 10) / 10;
      tooltip.innerHTML = `<strong>${escapeHtml(seg.label)}</strong> — ${pct}%`;

      // ✅ position tooltip relative to chart center and cursor
      const rect = chartEl.getBoundingClientRect();
      tooltip.style.left = `${evt.clientX - rect.left}px`;
      tooltip.style.top = `${evt.clientY - rect.top}px`;
      tooltip.style.opacity = "1";
    });

    chartEl.addEventListener("mouseleave", () => { tooltip.style.opacity = "0"; });
  }
})();
