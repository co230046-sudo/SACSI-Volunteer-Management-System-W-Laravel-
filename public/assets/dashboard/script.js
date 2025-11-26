// ================================================================
// 🔥 1. EVENT STATUS (Dynamic from Laravel)
// ================================================================
const eventStatusData = {
    labels: ['Upcoming Events', 'Completed Events', 'Cancelled Events'],
    datasets: [{
        label: 'Event Count',
        data: [
            window.dashboardData.eventStatus.upcoming,
            window.dashboardData.eventStatus.completed,
            window.dashboardData.eventStatus.cancelled
        ],
        backgroundColor: ['#36A2EB', '#4BC0C0', '#e44d26'],
        borderWidth: 1
    }]
};

// ================================================================
// 🔥 2. VOLUNTEERS BY COURSE / STRAND / SCHOOL (Dynamic)
// ================================================================
const courseVolunteerData = {
    labels: window.dashboardData.volunteersByCourse.labels,
    datasets: [{
        label: 'Volunteers',
        data: window.dashboardData.volunteersByCourse.totals,
        backgroundColor: [
            '#1e40af', '#3b82f6', '#2563eb',
            '#f59e0b', '#fbbf24', '#38bdf8',
            '#60a5fa', '#0ea5e9'
        ],
        borderWidth: 1
    }]
};

// ================================================================
// 🔥 3. VOLUNTEERS BY YEAR LEVEL (Dynamic)
// ================================================================
const yearLevelData = {
    labels: window.dashboardData.yearLevels.labels,
    datasets: [{
        label: 'Volunteers by Year Level',
        data: window.dashboardData.yearLevels.totals,
        backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#9966FF',
            '#4BC0C0', '#f59e0b'
        ],
        hoverOffset: 4
    }]
};

// ================================================================
// 🔥 COMMON CHART OPTIONS
// ================================================================
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        title: { display: true, font: { size: 18 } }
    }
};

// ================================================================
// 🔥 4. DRAW CHARTS
// ================================================================
new Chart(document.getElementById('classCategoryChart'), {
    type: 'bar',
    data: eventStatusData,
    options: {
        indexAxis: 'y',
        ...commonOptions,
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Event Status Overview' }
        }
    }
});

new Chart(document.getElementById('eventTypeChart'), {
    type: 'bar',
    data: courseVolunteerData,
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            title: { ...commonOptions.plugins.title, text: 'Volunteers by Course / Strand / School' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('eventChart'), {
    type: 'pie',
    data: yearLevelData,
    options: {
        ...commonOptions,
        plugins: {
            ...commonOptions.plugins,
            title: { ...commonOptions.plugins.title, text: 'Volunteers by Year Level' }
        }
    }
});

// ================================================================
// 🔥 PRINT SUMMARY
// ================================================================
function generateDataSummary() {
    const getValue = (id) =>
        document.querySelector(`#${id} .item-value`).textContent.trim();

    return `
        <html>
        <head>
            <title>SACSI Dashboard Summary</title>
            <style>
                body { font-family: Arial; padding: 20px; }
                h1 { color: #c90000; border-bottom: 2px solid #ccc; padding-bottom: 5px; }
                h2 { margin-top: 20px; }
                ul { list-style: none; padding: 0; }
                li { margin-bottom: 6px; }
                .metric { font-weight: bold; }
            </style>
        </head>
        <body>
            <h1>SACSI Volunteer Dashboard - Summary</h1>

            <h2>Key Metrics</h2>
            <ul>
                <li><span class="metric">Total Volunteers:</span> ${getValue('total-volunteers-container')}</li>
                <li><span class="metric">Active Volunteers:</span> ${getValue('active-volunteers-container')}</li>
                <li><span class="metric">Growth Rate:</span> ${getValue('growth-rate-container')}</li>
                <li><span class="metric">Average Attendance:</span> ${getValue('average-attendance-container')}</li>
                <li><span class="metric">Event Success Rate:</span> ${getValue('event-success-rate-container')}</li>
            </ul>

            <h2>Event Status Breakdown</h2>
            <ul>
                <li>Upcoming: ${window.dashboardData.eventStatus.upcoming}</li>
                <li>Completed: ${window.dashboardData.eventStatus.completed}</li>
                <li>Cancelled: ${window.dashboardData.eventStatus.cancelled}</li>
            </ul>

            <h2>Volunteers by Course</h2>
            <ul>
                ${window.dashboardData.volunteersByCourse.labels.map((l, i) =>
                    `<li>${l}: ${window.dashboardData.volunteersByCourse.totals[i]}</li>`
                ).join('')}
            </ul>

            <h2>Volunteers by Year Level</h2>
            <ul>
                ${window.dashboardData.yearLevels.labels.map((l, i) =>
                    `<li>${l}: ${window.dashboardData.yearLevels.totals[i]}</li>`
                ).join('')}
            </ul>
        </body>
        </html>`;
}

function printDataSummary() {
    const w = window.open('', '_blank');
    w.document.write(generateDataSummary());
    w.document.close();
    w.onload = () => w.print();
}

// ================================================================
// 🔥 EXPORT CSV
// ================================================================
function exportDataToExcelCsv() {
    let csv = "Category,Value\n";

    csv += `Total Volunteers,${document.querySelector('#total-volunteers-container .item-value').textContent}\n`;
    csv += `Active Volunteers,${document.querySelector('#active-volunteers-container .item-value').textContent}\n`;
    csv += `Growth Rate,${document.querySelector('#growth-rate-container .item-value').textContent}\n`;
    csv += `Average Attendance,${document.querySelector('#average-attendance-container .item-value').textContent}\n`;
    csv += `Event Success Rate,${document.querySelector('#event-success-rate-container .item-value').textContent}\n\n`;

    csv += "Event Status,Count\n";
    csv += `Upcoming,${window.dashboardData.eventStatus.upcoming}\n`;
    csv += `Completed,${window.dashboardData.eventStatus.completed}\n`;
    csv += `Cancelled,${window.dashboardData.eventStatus.cancelled}\n\n`;

    csv += "Course,Volunteers\n";
    window.dashboardData.volunteersByCourse.labels.forEach((label, i) => {
        csv += `${label},${window.dashboardData.volunteersByCourse.totals[i]}\n`;
    });

    csv += "\nYear Level,Volunteers\n";
    window.dashboardData.yearLevels.labels.forEach((label, i) => {
        csv += `${label},${window.dashboardData.yearLevels.totals[i]}\n`;
    });

    const blob = new Blob([csv], { type: "text/csv" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "SACSI_Dashboard_Data.csv";
    link.click();
}

// ================================================================
// 🔥 EXPORT PDF
// ================================================================
function generatePdfSummary() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    let y = 15;

    doc.setFontSize(18);
    doc.text("SACSI Dashboard Summary", 14, y);
    y += 10;

    const metrics = [
        `Total Volunteers: ${document.querySelector('#total-volunteers-container .item-value').textContent}`,
        `Active Volunteers: ${document.querySelector('#active-volunteers-container .item-value').textContent}`,
        `Growth Rate: ${document.querySelector('#growth-rate-container .item-value').textContent}`,
        `Average Attendance: ${document.querySelector('#average-attendance-container .item-value').textContent}`,
        `Event Success Rate: ${document.querySelector('#event-success-rate-container .item-value').textContent}`,
    ];

    metrics.forEach(m => {
        doc.text(m, 14, y);
        y += 8;
    });

    doc.save("dashboard-summary.pdf");
}

// ================================================================
// 🔥 Dropdown + Buttons
// ================================================================
document.getElementById('actionsDropdownToggle').onclick =
    () => document.getElementById('actionsDropdownMenu').classList.toggle('show');

document.getElementById('printDashboard').onclick = printDataSummary;
document.getElementById('exportExcelPlaceholder').onclick = exportDataToExcelCsv;
document.getElementById('exportPdf').onclick = generatePdfSummary;
