# Task 17 — Checkout și facturare pentru comenzi angro

## Depinde de
Task 16 (preț pe cantitate), Task 6 (checkout/comenzi), Task 11 (admin panel).

## Obiective
O comandă plasată de un cont angro păstrează datele firmei (nu doar ale
persoanei care a comandat), factura PDF le afișează corect, iar admin-ul
poate filtra/recunoaște ușor comenzile angro față de cele de retail.

**Non-obiectiv explicit** (repetat din task 15, ca să rămână clar și aici):
fără integrare e-Factura ANAF — doar factura PDF existentă, extinsă cu
datele de firmă.

## Modificări pe entitatea `Order`

Snapshot de date firmă la momentul comenzii — același principiu ca
`shippingFullName`/`shippingStreet` etc. (nu se schimbă retroactiv dacă
userul își editează datele firmei ulterior):

- `billingCompanyName` (string, nullable)
- `billingCompanyCui` (string, nullable)
- `billingCompanyRegCom` (string, nullable)
- `billingCompanyAddress` (string, nullable)
- `isWholesaleOrder` (boolean, implicit `false`) — setat la `true` dacă
  userul avea `ROLE_WHOLESALE` la momentul plasării comenzii, indiferent
  ce se întâmplă cu contul lui după aceea.

## Pași

1. Migrare pentru câmpurile de mai sus.
2. La plasarea comenzii (`OrderService`/checkout): dacă userul are
   `ROLE_WHOLESALE`, copiază `companyName`/`companyCui`/`companyRegCom`/
   `companyAddress` din `User` pe câmpurile `billing*` de mai sus și
   setează `isWholesaleOrder = true`.
3. `InvoicePdfService`/`templates/invoice/pdf.html.twig`: dacă
   `order.isWholesaleOrder`, afișează secțiunea „Client” cu datele firmei
   (`billingCompanyName`, CUI, Reg Com, adresă) în loc de doar
   `shippingFullName` — factura B2B trebuie să identifice firma cumpărătoare,
   nu doar persoana de contact.
4. Admin (`OrderCrudController`): coloană/badge „Angro” în listă +
   filtru `isWholesaleOrder`, ca să poți separa rapid comenzile de firmă
   de cele de retail (relevant pentru evidența contabilă).
5. (Opțional, decide dacă intră în acest task sau rămâne pentru mai
   târziu) Cantitate minimă de comandă pentru conturi angro — dacă vrei
   o regulă de tipul „comanda minimă e X lei/produse pentru conturi
   angro”, valideaz-o la checkout, cu mesaj clar dacă nu e atinsă.

## Criterii de acceptare

- [ ] O comandă plasată de un cont angro are datele firmei salvate corect
      pe comandă, chiar dacă userul își schimbă ulterior datele firmei în
      cont.
- [ ] Factura PDF a unei comenzi angro afișează firma cumpărătoare (nume,
      CUI, Reg Com), nu doar numele persoanei.
- [ ] O comandă de retail (cont fără `ROLE_WHOLESALE`) nu are niciodată
      `isWholesaleOrder = true` și factura ei arată exact ca înainte de
      acest task (fără regresie).
- [ ] Admin poate filtra lista de comenzi după „Angro” și vede corect
      doar comenzile relevante.
