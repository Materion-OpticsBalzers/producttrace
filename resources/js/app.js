require('./bootstrap');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus'
import ApexCharts from 'apexcharts'
import $ from 'jquery'

require('select2')


window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.$ = $;

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();


