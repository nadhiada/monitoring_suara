const currentNoiseLevelEl = document.getElementById('currentNoiseLevel');
const currentNoiseStatusEl = document.getElementById('currentNoiseStatus');
const gaugeCircleEl = document.getElementById('gaugeCircle');
const speechBubbleEl = document.getElementById('speechBubble');
const modeToggleEl = document.getElementById('modeToggle');
const ctxChart = document.getElementById('noiseChart').getContext('2d');
const warningLogTableBody = document.getElementById('warningLogTableBody');
let noiseChart;
const canvasWave = document.getElementById('soundWaveCanvas');
const ctxWave = canvasWave.getContext('2d');
let currentNoiseLevelForWave = 0;
let lastLoggedLevel = 0;
let fetchInterval;

const API_URL = 'http://localhost/get_data.php';

const dummyData = [
    { level: 250, timestamp: Date.now() - 50000 },
    { level: 270, timestamp: Date.now() - 40000 },
    { level: 350, timestamp: Date.now() - 30000 },
    { level: 450, timestamp: Date.now() - 20000 },
    { level: 780, timestamp: Date.now() - 10000 },
    { level: 850, timestamp: Date.now() - 5000 },
    { level: 200, timestamp: Date.now() }
];

function getNoiseStatus(level) {
    if (level < 301) {
        gaugeCircleEl.className = 'gauge-circle safe';
        speechBubbleEl.classList.remove('active');
        return 'Hening';
    }
    if (level < 701) {
        gaugeCircleEl.className = 'gauge-circle warning';
        speechBubbleEl.classList.remove('active');
        return 'Normal';
    }
    gaugeCircleEl.className = 'gauge-circle danger';
    speechBubbleEl.classList.add('active');
    return 'Berisik!';
}

function addWarningToTable(timestamp, level) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${new Date(timestamp).toLocaleTimeString()}</td>
        <td><i class="fas fa-exclamation-triangle"></i> ${level}</td>
    `;
    if (warningLogTableBody.firstChild) {
        warningLogTableBody.insertBefore(row, warningLogTableBody.firstChild);
    } else {
        warningLogTableBody.appendChild(row);
    }
    if (warningLogTableBody.childElementCount > 10) {
        warningLogTableBody.removeChild(warningLogTableBody.lastChild);
    }
}

function initializeDashboard() {
    const latestDummyLevel = dummyData[dummyData.length - 1].level;
    currentNoiseLevelEl.innerText = latestDummyLevel;
    currentNoiseStatusEl.innerText = getNoiseStatus(latestDummyLevel);
    currentNoiseLevelForWave = latestDummyLevel;
    
    const dummyWarnings = dummyData.filter(d => d.level > 700);
    dummyWarnings.forEach(d => addWarningToTable(d.timestamp, d.level));

    const labels = dummyData.map(item => new Date(item.timestamp).toLocaleTimeString());
    const levels = dummyData.map(item => item.level);

    noiseChart = new Chart(ctxChart, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Tingkat Kebisingan (Skala 0-1023)',
                data: levels,
                borderColor: 'var(--primary-color)',
                borderWidth: 3,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1024,
                    title: { display: true, text: 'Nilai Analog Sensor' }
                },
                x: {
                    title: { display: true, text: 'Waktu' }
                }
            },
            plugins: { legend: { display: false } },
            animation: { duration: 1000, easing: 'easeInOutQuart' }
        }
    });
}

async function fetchData() {
    try {
        const response = await fetch(API_URL);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        
        if (data.length === 0) {
            currentNoiseLevelEl.innerText = "N/A";
            currentNoiseStatusEl.innerText = "Menunggu data...";
            currentNoiseLevelForWave = 0;
            return;
        }
        
        data.reverse();
        const latestLevel = data[data.length - 1].level;
        currentNoiseLevelEl.innerText = latestLevel;
        currentNoiseStatusEl.innerText = getNoiseStatus(latestLevel);
        currentNoiseLevelForWave = latestLevel;

        if (latestLevel > 700 && lastLoggedLevel <= 700) {
            addWarningToTable(data[data.length - 1].timestamp, latestLevel);
        }
        lastLoggedLevel = latestLevel;

        if (latestLevel < 100) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        const labels = data.map(item => new Date(item.timestamp).toLocaleTimeString());
        const levels = data.map(item => item.level);

        if (noiseChart) {
            noiseChart.data.labels = labels;
            noiseChart.data.datasets[0].data = levels;
            noiseChart.update();
        } else {
            noiseChart = new Chart(ctxChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tingkat Kebisingan (Skala 0-1023)',
                        data: levels,
                        borderColor: 'var(--primary-color)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 1024,
                            title: { display: true, text: 'Nilai Analog Sensor' }
                        },
                        x: {
                            title: { display: true, text: 'Waktu' }
                        }
                    },
                    plugins: { legend: { display: false } },
                    animation: { duration: 1000, easing: 'easeInOutQuart' }
                }
            });
        }
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        currentNoiseStatusEl.innerText = "Server tidak terhubung.";
        currentNoiseLevelForWave = 0;
    }
}

function drawSoundWave() {
    if (document.getElementById('monitoring-content').classList.contains('active')) {
        canvasWave.width = canvasWave.offsetWidth;
        canvasWave.height = canvasWave.offsetHeight;
        
        ctxWave.clearRect(0, 0, canvasWave.width, canvasWave.height);
        
        const amplitude = currentNoiseLevelForWave / 1024 * (canvasWave.height / 2);
        const frequency = currentNoiseLevelForWave / 1024 * 5 + 1;
        const speed = 0.05;
        
        ctxWave.beginPath();
        ctxWave.moveTo(0, canvasWave.height / 2);
        for (let i = 0; i < canvasWave.width; i++) {
            const y = canvasWave.height / 2 + amplitude * Math.sin((i + Date.now() * speed) * frequency / canvasWave.width * Math.PI * 2);
            ctxWave.lineTo(i, y);
        }
        ctxWave.strokeStyle = currentNoiseLevelForWave > 700 ? 'var(--danger-color)' : 'var(--primary-color)';
        ctxWave.lineWidth = 3;
        ctxWave.stroke();
    }
    requestAnimationFrame(drawSoundWave);
}

modeToggleEl.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
});

// Fungsi untuk mengelola tampilan konten
function showContent(id) {
    const allContent = document.querySelectorAll('.content');
    allContent.forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(id + '-content').classList.add('active');

    if (id === 'monitoring') {
        if (!fetchInterval) {
            fetchInterval = setInterval(fetchData, 3000);
            fetchData();
        }
    } else {
        clearInterval(fetchInterval);
        fetchInterval = null;
    }
}

// Panggil saat halaman dimuat
initializeDashboard();
showContent('monitoring');
drawSoundWave();