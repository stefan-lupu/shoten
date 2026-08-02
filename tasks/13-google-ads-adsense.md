# Task 13 — Google Ads tracking + Google AdSense

## Depinde de
Task 1 (sloturile de script deja pregătite în `base.html.twig`),
Task 12 (SEO, pentru context de trafic organic vs plătit).

## Obiective
Trafic plătit (Google Ads — tu plătești ca să aduci vizitatori) și venit
pasiv (Google AdSense — afișezi reclame de la alții și câștigi din
click-uri/afișări). Ambele deja au sloturi condiționale în
`base.html.twig` din task 1 — aici doar completezi logica și pozițiile.

## Google Ads (tracking conversii)

1. Confirmă `GOOGLE_ADS_CONVERSION_ID` e setat în `.env.local` (diferă
   per magazin — fiecare cont Google Ads e separat pentru cele 3 branduri).
2. Adaugă event de conversie la finalizarea comenzii (task 6) — pe
   pagina de mulțumire, un snippet `gtag('event', 'conversion', {...})`
   condiționat de existența `store.googleAdsConversionId`.
3. Nu trimite date personale (email, nume) către Google Ads — doar
   valoarea comenzii și un ID de tranzacție anonim, conform politicilor
   Google Ads.

## Google AdSense (venit pasiv)

1. Confirmă `GOOGLE_ADSENSE_CLIENT_ID` e setat în `.env.local`.
2. Creează un componentă Twig reutilizabilă `_adsense_slot.html.twig`
   care primește un `slotId` ca parametru — reutilizabilă în mai multe
   poziții fără duplicare de cod.
3. Poziții recomandate, cu grijă la experiența utilizatorului:
   - sidebar pe pagina de categorie (dacă ai layout cu sidebar)
   - un slot discret între rândurile de produse pe listarea de categorie
     (nu pe fiecare produs — devine intruziv)
   - footer, sub conținutul principal
   - **niciodată** pe pagina de checkout/plată (distragere + poate
     încălca politicile unor procesatori de plată)
4. Verifică politica de conținut AdSense — un magazin de produse
   japoneze e conținut standard, fără risc, dar verifică totuși
   consimțământul cookie-uri (AdSense folosește cookie-uri de tracking,
   necesită banner de consimțământ GDPR — vezi nota de mai jos).

## Notă GDPR (relevantă pentru clienți din România/UE)

AdSense și Google Ads necesită consimțământ explicit pentru cookie-uri
de publicitate, conform GDPR. Implementează un banner de consimțământ
simplu (accept/refuz) înainte de a încărca scripturile Google — nu le
încărca necondiționat la fiecare vizită. Acesta e un task tehnic real,
nu opțional, dacă vinzi către clienți din UE.

## Criterii de acceptare

- [ ] Scripturile Google Ads/AdSense nu se încarcă dacă userul refuză
      consimțământul cookie.
- [ ] Evenimentul de conversie se trimite o singură dată per comandă
      (nu la fiecare reîncărcare a paginii de mulțumire).
- [ ] Reclamele AdSense nu apar pe pagina de checkout.
- [ ] ID-urile Google (Ads, AdSense, Analytics) diferă corect între
      cele 3 magazine — fiecare citește doar din propriul `.env.local`.
