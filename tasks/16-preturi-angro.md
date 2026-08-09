# Task 16 — Preț pe cantitate (tiers angro)

## Depinde de
Task 15 (conturi angro), Task 2 (catalog), Task 8 (campanii — pentru
regula de ordine dintre reduceri și preț angro).

## Obiective
Fiecare produs poate avea praguri de preț pe cantitate (ex: 1-9 buc =
preț normal, 10-49 = 15 lei/buc, 50+ = 12 lei/buc), vizibile și aplicabile
**doar** clienților cu `ROLE_WHOLESALE` aprobat. Restul clienților nu văd
niciodată prețurile de angro.

## Entitate nouă `ProductWholesaleTier`

- `product` (ManyToOne → Product)
- `minQuantity` (integer) — pragul de la care se aplică prețul
- `unitPrice` (decimal, precision 10, scale 2) — preț pe bucată la acest prag
- Constrângere logică (validată în admin, nu neapărat la nivel de DB):
  un produs nu poate avea două tiers cu același `minQuantity`, iar
  `unitPrice` trebuie să scadă (sau cel puțin să nu crească) pe măsură
  ce `minQuantity` crește — altfel un client ar plăti mai mult cumpărând
  mai mult, ceea ce n-are sens comercial.

## Serviciu `WholesalePricingResolver`

Metodă centrală, ex. `resolveUnitPrice(Product $product, int $quantity, ?User $user): string`:
- dacă `$user` e null sau nu are `ROLE_WHOLESALE` → prețul normal de
  retail al produsului, neschimbat.
- dacă `$user` are `ROLE_WHOLESALE` → caută tier-ul cu cel mai mare
  `minQuantity` ≤ `$quantity`; dacă există, întoarce `unitPrice` din
  tier; altfel prețul normal de retail (cantitate sub primul prag).

## Interacțiunea cu motorul de campanii (Task 8)

Regulă explicită, de documentat și testat (motorul de campanii nu știe
nimic despre tiers, iar tiers nu știe nimic despre campanii — trebuie
decisă ordinea undeva sus, în `CartManager`/`CampaignEngine`):

**Prețul de tier angro înlocuiește prețul de retail ca bază, apoi
campaniile normale (cupoane, reduceri procentuale) se aplică peste acel
preț de bază** — nu se cumulează cele două motoare fără reguli clare.
Dacă vrei alt comportament (ex: comenzile angro sunt excluse complet din
campaniile de retail), decide explicit aici înainte de implementare — nu
lăsa ordinea implicită/accidentală.

## Pași

1. Entitate + migrare pentru `ProductWholesaleTier`.
2. `WholesalePricingResolver` + teste manuale documentate pentru: user
   fără rol angro, user angro sub primul prag, user angro peste diverse
   praguri.
3. Hook în `CartManager` (acolo unde se face snapshot de preț la
   adăugare/actualizare cantitate în `CartItem::unitPrice`) — recalculează
   prețul de fiecare dată când cantitatea unui item se schimbă, nu doar
   la adăugare (un client angro care crește cantitatea peste un prag nou
   trebuie să vadă prețul scăzând automat în coș).
4. Pagina de produs (`product/show.html.twig`): dacă userul curent are
   `ROLE_WHOLESALE`, afișează un tabel „Preț pe cantitate” cu toate
   tiers-urile produsului; altfel nu afișează nimic legat de angro (nici
   măcar un hint că ar exista).
5. Admin: `CollectionField` pentru `wholesaleTiers` inline în
   `ProductCrudController`, editabil direct din formularul de produs (nu
   CRUD separat — sunt mereu legate de un produs anume).

## Criterii de acceptare

- [ ] Un client neautentificat sau fără `ROLE_WHOLESALE` nu vede niciodată
      un preț de tier, nici în catalog, nici în coș, nici în HTML sursă.
- [ ] Un client angro care adaugă în coș o cantitate sub primul prag
      plătește prețul normal de retail.
- [ ] Un client angro care crește cantitatea peste un prag din coș vede
      prețul unitar actualizat automat, fără să reîncarce manual pagina
      într-un mod ciudat.
- [ ] Ordinea preț-angro → campanii e documentată în cod (comentariu la
      punctul unde se aplică) și verificată printr-un test manual cu un
      cupon activ pe un cont angro.
- [ ] Editarea tiers-urilor dintr-un produs în admin nu afectează alte
      produse (fiecare tier e legat corect de produsul lui).
