require('./bootstrap');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus'
import Chartist from 'chartist';
import {zingchart, ZC} from 'zingchart/es6.js';
import 'zingchart/modules-es6/zingchart-pareto.min.js';

window.Alpine = Alpine;
window.Chartist = Chartist;
window.Zingchart = zingchart

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();


