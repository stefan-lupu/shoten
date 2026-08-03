import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import Swal from 'sweetalert2';
import './styles/app.css';

function initFlashesAndConfirms() {
    document.querySelectorAll('[data-flash]').forEach((el) => {
        Swal.fire({
            icon: el.dataset.flash,
            text: el.textContent.trim(),
            toast: true,
            position: 'top-end',
            timer: 4000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
        el.remove();
    });

    document.querySelectorAll('form[data-confirm]:not([data-confirm-bound])').forEach((form) => {
        form.setAttribute('data-confirm-bound', '1');
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed) {
                return;
            }
            event.preventDefault();

            Swal.fire({
                title: form.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Da, sunt sigur',
                cancelButtonText: 'Anulează',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.requestSubmit();
                }
            });
        });
    });
}

// Turbo (Hotwired) interceptează navigarea și formularele și înlocuiește
// <body> fără să reîncarce pagina — DOMContentLoaded se declanșează o
// singură dată, la prima încărcare, nu și după navigările următoare.
// turbo:load se declanșează de fiecare dată (inclusiv la prima încărcare).
document.addEventListener('turbo:load', initFlashesAndConfirms);
