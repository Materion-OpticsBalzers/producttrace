require('./bootstrap');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus'
import ApexCharts from 'apexcharts'


window.Alpine = Alpine;
window.ApexCharts = ApexCharts;

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();


