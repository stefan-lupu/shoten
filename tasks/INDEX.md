# Rechizite Japan — plan de task-uri

Proiect: magazin online Symfony + MySQL + Twig, „made in Japan", arhitectură
dinamică (pregătită pentru clonare în 2 magazine suplimentare).

Fiecare task e într-un fișier separat (`01-fundatie.md`, `02-entitati-catalog.md`,
etc.). Rezolvă-le în ordine — fiecare listează dependințele de care are nevoie.
Nu sări peste un task doar pentru că pare mic; ordinea contează pentru migrări.

## Ordinea task-urilor

| # | Fișier | Task | Depinde de |
|---|---|---|---|
| 1 | `01-fundatie.md` | Setup proiect + config dinamică de brand (StoreConfig) | — |
| 2 | `02-entitati-catalog.md` | Entități Category, Product, ProductImage + migrări | 1 |
| 3 | `03-catalog-frontend.md` | Listare produse, pagină categorie, pagină produs, SEO de bază | 2 |
| 4 | `04-utilizatori-auth.md` | Entitate User, roluri, înregistrare/login | 1 |
| 5 | `05-cos-cumparaturi.md` | Coș de cumpărături (sesiune + user logat) | 2, 4 |
| 6 | `06-checkout-comenzi.md` | Entități Order/OrderItem, flux checkout, adrese livrare | 4, 5 |
| 7 | `07-plati.md` | Integrare plăți: card, ramburs, transfer bancar | 6 |
| 8 | `08-campanii-promotii.md` | Motor campanii: reduceri, cupoane, BOGO, bundle-uri | 5, 6 |
| 9 | `09-recenzii.md` | Recenzii produse cu moderare | 2, 4 |
| 10 | `10-newsletter.md` | Abonare newsletter | 1 |
| 11 | `11-admin-panel.md` | Panou administrare (EasyAdminBundle) | 2, 6, 8, 9 |
| 12 | `12-seo-tehnic.md` | Sitemap, schema.org, meta tags dinamice | 3 |
| 13 | `13-google-ads-adsense.md` | Google Ads tracking + AdSense (venit pasiv) | 1, 12 |
| 14 | `14-pregatire-clonare.md` | Verificare finală: zero hardcodări, checklist clonare | toate |
| 15 | `15-conturi-angro.md` | Modul angro (B2B) — conturi de firmă cu aprobare manuală | 4, 11 |
| 16 | `16-preturi-angro.md` | Preț pe cantitate (tiers) pentru conturi angro | 15, 2, 8 |
| 17 | `17-checkout-facturare-angro.md` | Checkout și facturare cu date de firmă pentru comenzi angro | 16, 6, 11 |

## Reguli globale (valabile pentru toate task-urile)

- **Nicio valoare de brand hardcodată** — nume magazin, culori, texte, chei API
  vin mereu din `StoreConfig` / `.env.local` / variabila Twig `store`.
- **Fiecare task nou trebuie să funcționeze identic dacă schimbi doar `.env.local`**
  — asta e testul de bază pentru „e gata de clonat".
- Migrările Doctrine se fac task cu task, nu toate deodată — ca istoricul git
  să rămână curat și ușor de urmărit pe cele 3 magazine.
- Comenzi Symfony folosite frecvent: `php bin/console make:entity`,
  `make:migration`, `doctrine:migrations:migrate`, `make:controller`.
- Foloseste bootstrap pentru frontend

## Context de business (pentru referință rapidă)

- Public: persoane fizice din România.
- Magazinul 1: rechizite made in Japan, import direct.
- Stoc: hibrid (stoc propriu pentru produse populare + import la comandă).
- Plăți: card, ramburs, transfer bancar.
- Design: tematic tradițional japonez, negru + auriu.
- Monetizare adițională: Google AdSense + Google Ads pentru trafic.
- Modul angro (task 15-17): conturi de firmă cu aprobare manuală, preț pe
  cantitate vizibil doar clienților aprobați. Fără e-Factura ANAF (scop
  separat, nediscutat încă).
