# Task 1 — Fundație proiect + configurare dinamică de brand

## Context
Acest proiect va fi clonat de 2 ori pentru alte 2 magazine cu nume/culori/produse
diferite. Tot ce ține de brand trebuie separat de cod de la început.

## Obiective
1. Proiect Symfony nou (webapp skeleton: Twig, Doctrine, security, forms).
2. Conexiune MySQL funcțională.
3. Un serviciu `StoreConfig` care centralizează identitatea magazinului
   (nume, slogan, culori temă, contact, chei API) citită din `.env.local`.
4. Layout Twig de bază (`base.html.twig`) care nu conține nicio valoare
   hardcodată de brand — totul via variabila globală Twig `store`.

## Pași

1. `symfony new . --webapp` (sau `composer create-project symfony/skeleton` +
   pachetele webapp dacă preferi control manual).
2. Configurează `DATABASE_URL` în `.env.local`, rulează
   `php bin/console doctrine:database:create`.
3. Creează `src/Service/StoreConfig.php` — clasă simplă, cu proprietăți
   readonly injectate din constructor pentru: name, slogan, domain,
   defaultMetaDescription, email, phone, culorile temei (bg, surface, text,
   text-muted, accent, accent-strong), logoPath, faviconPath,
   adsenseClientId, googleAdsConversionId, googleAnalyticsId.
4. În `config/services.yaml`: definește parametri Symfony care mapează
   fiecare variabilă de mediu (`%env(STORE_NAME)%` etc.) și injectează-i
   ca argumente în serviciul `StoreConfig`.
5. În `config/packages/twig.yaml`: adaugă `globals: store: '@App\Service\StoreConfig'`
   ca să fie disponibil în orice template fără injecție manuală.
6. Creează `.env.local.example` cu toate variabilele necesare, documentate,
   ca șablon pentru viitoarele clone (vezi lista de variabile mai jos).
7. Scrie `templates/base.html.twig`: header cu logo dinamic, variabile CSS
   generate din `store.themeColors`, footer cu contact dinamic, sloturi
   pentru Google Analytics/AdSense (condiționate de existența ID-ului).

## Variabile necesare în `.env.local`

```
STORE_NAME, STORE_SLOGAN, STORE_DOMAIN, STORE_DEFAULT_META_DESCRIPTION,
STORE_EMAIL, STORE_PHONE,
THEME_COLOR_BG, THEME_COLOR_SURFACE, THEME_COLOR_TEXT, THEME_COLOR_TEXT_MUTED,
THEME_COLOR_ACCENT, THEME_COLOR_ACCENT_STRONG,
THEME_LOGO_PATH, THEME_FAVICON_PATH,
GOOGLE_ADSENSE_CLIENT_ID, GOOGLE_ADS_CONVERSION_ID, GOOGLE_ANALYTICS_ID,
NETOPIA_API_KEY, NETOPIA_SIGNATURE
```

## Criterii de acceptare

- [ ] `php bin/console server:start` pornește fără erori, pagina de start
      (chiar goală) afișează numele magazinului din `.env.local`.
- [ ] Schimbarea oricărei valori din `.env.local` (nume, culoare accent)
      se reflectă imediat în pagină, fără modificări de cod.
- [ ] Zero șiruri hardcodate de tip "Rechizite Japan" în `.twig` sau `.php`
      (verifică cu `grep -ri "rechizite" templates/ src/` — trebuie să
      returneze gol, în afară de fixtures/seed data ulterioare).
- [ ] `README-ARHITECTURA.md` există în rădăcina proiectului, explicând
      fluxul `.env.local → StoreConfig → Twig`.
