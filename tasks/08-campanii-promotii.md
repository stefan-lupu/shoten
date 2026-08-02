# Task 8 — Campanii și promoții

## Depinde de
Task 5 (Cart), Task 6 (Order).

## Obiective
Motor de reguli de preț care acoperă toate tipurile discutate: reducere
procentuală/fixă, cod promoțional, BOGO (X+Y gratis), produs cadou la
prag, bundle de produse.

## Entități de creat

### `Campaign`
- `name` (string) — nume intern, pentru admin
- `type` (string/enum: `percentage_discount`, `fixed_discount`, `coupon`,
  `bogo`, `gift_threshold`, `bundle`)
- `startsAt`, `endsAt` (datetime, nullable — null = fără limită)
- `isActive` (boolean)
- `couponCode` (string, nullable, unic — folosit doar pentru type=coupon)
- `discountValue` (decimal, nullable) — procent sau valoare fixă, în
  funcție de `type`
- `maxUses` (integer, nullable), `usesCount` (integer, implicit 0)

### `CampaignProduct` (tabel de legătură cu rol suplimentar)
- `campaign` (ManyToOne → Campaign)
- `product` (ManyToOne → Product)
- `role` (string: `target` [produsul la care se aplică reducerea],
  `trigger` [produsul care declanșează BOGO/cadou], `gift` [produsul
  oferit gratis], `bundle_item` [parte dintr-un bundle])

## Pași

1. Creează entitățile + migrare.
2. `src/Service/CampaignEngine.php` — serviciu central, metodă principală
   `applyCampaigns(Cart $cart, ?string $couponCode = null): CampaignResult`
   care returnează totalul recalculat + lista de reduceri aplicate
   (pentru afișare transparentă în coș: „−15 lei aplicat: Cod PRIMAVARA20").
3. Implementează fiecare tip de regulă ca strategie separată (Strategy
   pattern) în `src/Service/Campaign/Strategy/` — ușor de testat izolat
   și de adăugat tipuri noi ulterior fără să atingi restul motorului.
4. Validează cupoanele: activ, în interval de valabilitate, sub
   `maxUses`, aplicabil coșului curent (dacă are restricții de produs).
5. Afișează câmp „Cod promoțional" în pagina de coș, cu buton „Aplică" și
   feedback clar dacă e invalid/expirat.
6. La finalizarea comenzii (task 6), incrementează `usesCount` pe
   campania/cuponul folosit.

## Criterii de acceptare

- [ ] Fiecare tip de campanie (5 tipuri) are cel puțin un test manual
      documentat care demonstrează calculul corect al totalului.
- [ ] Un cupon expirat sau cu `maxUses` atins e respins cu mesaj clar.
- [ ] Reducerile aplicate sunt vizibile explicit în coș/checkout — clientul
      trebuie să vadă exact ce reducere a primit și de ce.
- [ ] Motorul funcționează corect și cu mai multe campanii active
      simultan pe același coș (definește și documentează ordinea de
      aplicare — ex: reduceri procentuale înainte de cupon fix).
