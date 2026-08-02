# Task 11 — Panou de administrare

## Depinde de
Task 2 (catalog), Task 6 (comenzi), Task 8 (campanii), Task 9 (recenzii).

## Obiective
Panou funcțional pentru gestionarea zilnică a magazinului, fără a
construi UI custom de la zero — folosește EasyAdminBundle pentru viteză.

## Pași

1. `composer require easycorp/easyadmin-bundle`.
2. `php bin/console make:admin:dashboard` — generează
   `DashboardController`, protejează-l cu `ROLE_ADMIN` (deja configurat
   în task 4).
3. Generează CRUD-uri pentru: `Product`, `Category`, `ProductImage`
   (inline în Product ideal, via `CollectionField`), `Campaign`,
   `CampaignProduct`.
4. `Order` — CRUD read-heavy: listă cu filtre (status, metodă plată,
   interval dată), pagină detaliu cu acțiune „Marchează ca plătită" /
   „Marchează ca expediată" (butoane custom, nu editare liberă a
   statusului din dropdown, ca să eviți greșeli).
5. `Review` — listă cu filtru `status = pending` implicit, acțiuni
   rapide „Aprobă" / „Respinge" direct din listă (batch actions dacă
   EasyAdmin le suportă simplu, altfel per-rând).
6. `NewsletterSubscriber` — doar listă + export CSV (buton simplu care
   generează fișier descărcabil), fără editare.
7. Dashboard principal: câteva metrici simple (comenzi din ultimele 7
   zile, recenzii în așteptare, produse cu stoc sub un prag) — util
   zilnic, nu supra-construit.
8. Personalizează titlul/logo-ul din EasyAdmin dashboard să folosească
   `store.name`/`store.logoPath` (nu hardcoda „Admin" generic fără brand).

## Criterii de acceptare

- [ ] Un user `ROLE_USER` normal nu poate accesa nicio rută `/admin/*`.
- [ ] Adăugarea unui produs nou din admin apare imediat pe site (fără
      cache stale).
- [ ] Aprobarea unei recenzii din admin o face vizibilă public imediat.
- [ ] Marcarea unei comenzi ca „plătită" (pentru ramburs/transfer)
      funcționează și declanșează, dacă ai implementat-o, notificarea
      corespunzătoare.
- [ ] Exportul CSV de newsletter conține doar coloanele necesare
      (email, dată abonare), fără date sensibile inutile.
