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
// Nu e un controller Stimulus propriu-zis (doar ascultă `submit` la nivel de
// document) — necesar pentru orice formular Symfony randat direct în admin
// (ex: compunere newsletter), altfel token-ul placeholder "csrf-token" nu e
// niciodată înlocuit cu unul real și submit-ul pică cu „Token CSRF invalid".
import './controllers/csrf_protection_controller.js';

const application = Application.start();
application.register('symfony--ux-autocomplete--autocomplete', AutocompleteController);
