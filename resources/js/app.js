require('./bootstrap');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus'
import Chartist from 'chartist';

window.Alpine = Alpine;
window.Chartist = Chartist;

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();


