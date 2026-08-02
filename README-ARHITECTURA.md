# Arhitectură — identitate de brand dinamică

Acest proiect e gândit să fie clonat pentru mai multe magazine (nume, culori,
contact, chei API diferite) fără nicio modificare de cod. Regula de bază:
**nicio valoare de brand nu se scrie hardcodat** în `.twig` sau `.php`.

## Fluxul: `.env.local` → `StoreConfig` → Twig

1. **`.env.local`** (necomitat, unic per magazin/clonă) conține toate
   variabilele de identitate: `STORE_NAME`, `STORE_SLOGAN`, culorile temei
   (`THEME_COLOR_*`), contact, chei API opționale (AdSense, Google Ads,
   Netopia). Șablonul documentat e în [`.env.local.example`](.env.local.example).

2. **`config/services.yaml`** mapează fiecare variabilă de mediu la un
   argument al serviciului `App\Service\StoreConfig` (`%env(STORE_NAME)%`
   etc.). Culorile temei sunt grupate într-un value object
   `App\ValueObject\ThemeColors`, injectat ca argument `$themeColors`.

3. **`src/Service/StoreConfig.php`** e o clasă `readonly` simplă — doar
   proprietăți publice, fără logică. E singura sursă de adevăr pentru
   identitatea magazinului curent în tot codul aplicației.

4. **`config/packages/twig.yaml`** înregistrează `StoreConfig` ca global
   Twig sub numele `store`, deci e disponibil în orice template fără
   injecție manuală în controller.

5. **`templates/base.html.twig`** consumă `store.*`: nume, slogan, logo
   (cu fallback pe text dacă `logoPath` e gol), footer de contact, variabile
   CSS generate din `store.themeColors` (`--color-bg`, `--color-accent`
   etc.), și scripturi Analytics/AdSense condiționate de existența ID-urilor.

## Ca să clonezi un magazin nou

Copiază `.env.local.example` în `.env.local`, completează valorile — gata.
Niciun fișier `.twig` sau `.php` nu trebuie atins. Testul rapid: schimbă o
valoare (ex. `STORE_NAME` sau `THEME_COLOR_ACCENT`) și reîncarcă pagina —
schimbarea trebuie să fie vizibilă imediat.
