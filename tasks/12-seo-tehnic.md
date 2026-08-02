# Task 12 — SEO tehnic

## Depinde de
Task 3 (catalog frontend cu meta tags de bază deja în șabloane).

## Obiective
Fundația tehnică SEO: sitemap, structured data, meta tags complete —
pregătire pentru task 13 (Google Ads/AdSense) și pentru trafic organic.

## Pași

1. `SitemapController` — generează `sitemap.xml` dinamic, incluzând
   toate produsele, categoriile și paginile statice. Cache-uiește
   rezultatul (ex: 1 oră) ca să nu recalculeze la fiecare request de
   crawler.
2. Adaugă `robots.txt` (poate fi un fișier static în `public/`) care
   referențiază sitemap-ul: `Sitemap: https://{{ store.domain }}/sitemap.xml`
   — dar cum `robots.txt` e static, generează-l per magazin la clonare
   (documentează asta în task 14) sau fă-l dinamic printr-un controller
   dacă preferi zero fișiere statice de editat manual.
3. Adaugă JSON-LD structured data (`schema.org/Product`) pe pagina de
   produs: nume, preț, disponibilitate (mapează `stockStatus` la
   `InStock`/`PreOrder` din schema.org), rating agregat (din recenziile
   aprobate, task 9).
4. Verifică toate paginile publice au: `<title>` unic, meta description
   unică, `<link rel="canonical">`, Open Graph tags de bază (`og:title`,
   `og:description`, `og:image` — prima imagine din galeria produsului).
5. URL-uri curate deja asigurate prin slug-uri (task 2/3) — verifică nu
   există parametri query inutili în URL-urile publice.

## Criterii de acceptare

- [ ] `/sitemap.xml` e valid (validează cu un validator XML sitemap
      online) și conține toate produsele active.
- [ ] JSON-LD de pe o pagină de produs trece prin Google Rich Results
      Test fără erori.
- [ ] Fiecare pagină de produs are `og:image` funcțional (verifică cu
      un debugger de preview social, ex. Facebook Sharing Debugger).
- [ ] Structura e identică pe toate cele 3 magazine — doar domeniul din
      `store.domain` diferă.
