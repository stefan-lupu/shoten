# Task 10 — Newsletter

## Depinde de
Task 1 (fundație).

## Obiective
Abonare simplă la newsletter, cu consimțământ explicit (GDPR), stocată
în DB pentru MVP — integrare cu un provider extern (Mailchimp etc.) e
opțională și poate veni ulterior fără să schimbe formularul public.

## Entitate `NewsletterSubscriber`
- `email` (string, unic)
- `consentGiven` (boolean)
- `subscribedAt` (datetime_immutable)
- `unsubscribeToken` (string, unic — folosit pentru link de dezabonare
  fără login)

## Pași

1. Creează entitatea + migrare.
2. Formular scurt (doar email + checkbox consimțământ) în footer
   (`base.html.twig`) — vizibil pe toate paginile.
3. `NewsletterController`: rută POST pentru abonare (validează email
   unic, consimțământ bifat obligatoriu), rută GET pentru dezabonare
   prin `unsubscribeToken` din link-ul din email.
4. Mesaj de confirmare after-submit (fără reîncărcare bruscă a paginii —
   poate fi un simplu redirect cu flash message, nu necesită AJAX).
5. Textul de consimțământ trebuie să menționeze clar la ce se abonează
   (`{{ store.name }}` dinamic) și să aibă link către o pagină de
   confidențialitate (poate fi placeholder pentru acum).

## Criterii de acceptare

- [ ] Nu se poate abona un email fără bifarea consimțământului.
- [ ] Emailuri duplicate sunt respinse cu mesaj clar, nu eroare 500.
- [ ] Link-ul de dezabonare funcționează fără a necesita login.
- [ ] Formularul funcționează identic indiferent de magazinul pe care
      rulează (text „Abonează-te la noutățile {{ store.name }}", dinamic).
