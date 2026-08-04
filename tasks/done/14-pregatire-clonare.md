# Task 14 — Verificare finală + checklist de clonare

## Depinde de
Toate task-urile anterioare (1-13).

## Obiective
Confirmarea că magazinul „Rechizite Japan" e cu adevărat un template
curat, gata de clonat pentru celelalte 2 magazine, fără nicio urmă de
brand hardcodată în cod.

## Verificări

1. **Audit text hardcodat:**
   ```bash
   grep -ri "rechizite" templates/ src/ --include="*.twig" --include="*.php"
   grep -ri "japan" templates/ src/ --include="*.twig" --include="*.php"
   ```
   Trebuie să returneze gol (în afară de comentarii de cod sau nume de
   clase/fișiere care conțin "Japan" ca parte a domeniului de business
   generic, nu ca brand specific).

2. **Audit culori hardcodate:** verifică niciun fișier CSS/Twig nu are
   valori hex hardcodate pentru culorile de brand — toate trebuie să
   vină din variabilele `--store-*` generate în `base.html.twig`.

3. **Test de schimbare de brand:** schimbă complet `.env.local` (nume
   nou, culori noi, logo nou) fără să atingi niciun alt fișier, repornește
   serverul și verifică vizual că întregul site reflectă noul brand.

4. **Test bază de date izolată:** confirmă că fiecare `DATABASE_URL`
   diferit pornește cu o bază goală, fără date reziduale de la magazinul
   „Rechizite Japan".

5. Actualizează `README-ARHITECTURA.md` cu orice detaliu nou apărut pe
   parcursul dezvoltării (ex: dacă ai adăugat servicii noi cu configurare
   per-magazin, documentează-le acolo).

## Checklist de clonare (de folosit efectiv la crearea magazinului 2 și 3)

- [ ] `git clone` din repo-ul „Rechizite Japan", schimbă `git remote`.
- [ ] Copiază `.env.local.example` → `.env.local`, completează toate
      valorile pentru noul brand.
- [ ] Înlocuiește `assets/images/store/` cu logo/favicon noi.
- [ ] Creează baza de date nouă, rulează toate migrările în ordine.
- [ ] Creează cont `ROLE_ADMIN` pentru noul magazin.
- [ ] Populează categorii/produse specifice noii game.
- [ ] Configurează cheile API proprii (plăți, Google Ads, AdSense,
      Analytics) — niciodată reutilizate de la primul magazin.
- [ ] Test complet de flux: navigare catalog → coș → checkout → comandă
      → email confirmare, pe magazinul nou.
- [ ] Verifică sitemap.xml și robots.txt reflectă domeniul corect.

## Criterii de acceptare

- [ ] Toate verificările de mai sus trec fără excepții nedocumentate.
- [ ] Un coleg care nu a scris codul poate cloni și rebrandui magazinul
      urmând doar checklist-ul, fără să întrebe nimic despre unde se
      schimbă ce.
