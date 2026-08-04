/*
 * Entrypoint minimal pentru panoul de admin (EasyAdmin) — pornește Stimulus
 * doar cu controllerul de autocomplete (necesar în câmpurile de tip
 * colecție, ex. produse din campanii). NU folosește stimulus_bootstrap.js
 * (care ar aduce și Turbo — ar strica navigarea clasică full-page a
 * EasyAdmin, de care depinde JS-ul lui propriu de colecții) și nu
 * încarcă Bootstrap/SweetAlert2/CSS-ul magazinului (ar intra în conflict
 * cu stilizarea proprie EasyAdmin).
 */
import { Application } from '@hotwired/stimulus';
import AutocompleteController from '/assets/@symfony/ux-autocomplete/controller.js';
import 'tom-select/dist/css/tom-select.default.css';

const application = Application.start();
application.register('symfony--ux-autocomplete--autocomplete', AutocompleteController);
