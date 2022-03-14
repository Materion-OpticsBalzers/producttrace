require('./bootstrap');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus'

window.Alpine = Alpine;

Alpine.plugin(persist);
Alpine.plugin(focus);

Alpine.start();
