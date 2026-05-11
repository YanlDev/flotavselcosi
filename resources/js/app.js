import imageCompression from 'browser-image-compression';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

window.imageCompression = imageCompression;
window.Chart = Chart;
