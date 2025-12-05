
const data = [
  { label: 'Grade 11', percentage: 40, color: '#FF6384' },
  { label: 'Grade 12', percentage: 35, color: '#36A2EB' },
  { label: '1st Year College', percentage: 15, color: '#FFCE56' },
  { label: '2nd Year College', percentage: 10, color: '#9966FF' },
];

let startDeg = 0;
const segments = data.map(segment => {
  const endDeg = startDeg + (segment.percentage / 100) * 360;
  const cssSegment = `${segment.color} ${startDeg}deg ${endDeg}deg`;
  startDeg = endDeg;
  return cssSegment;
});


const chart = document.querySelector('.chart');
chart.style.background = `conic-gradient(${segments.join(', ')})`;
