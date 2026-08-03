# Task 6 — Checkout și comenzi

## Depinde de
Task 4 (User), Task 5 (Cart).

## Obiective
Transformarea coșului într-o comandă persistentă, cu adresă de livrare
și status urmăribil. Plata efectivă (integrarea providerilor) vine în
task 7 — aici doar structura de comandă și fluxul de checkout.

## Entități de creat

### `Address`
- `user` (ManyToOne → User)
- `fullName`, `phone`, `county`, `city`, `street`, `postalCode`
- `isDefault` (boolean)

### `Order`
- `user` (ManyToOne → User)
- `address` (ManyToOne → Address, sau câmpuri copiate direct pe comandă
  ca snapshot — recomandat, ca adresa să nu se schimbe retroactiv dacă
  userul își editează adresa salvată)
- `status` (string/enum: `pending`, `confirmed`, `shipped`, `delivered`, `cancelled`)
- `paymentMethod` (string: `card`, `cod` [ramburs], `bank_transfer`)
- `paymentStatus` (string: `pending`, `paid`, `failed`)
- `total` (decimal 10,2)
- `createdAt`

### `OrderItem`
- `order` (ManyToOne → Order)
- `product` (ManyToOne → Product)
- `productName` (string — snapshot al numelui, în caz că produsul se
  șterge/redenumește ulterior)
- `quantity` (integer)
- `unitPrice` (decimal 10,2)

## Pași

1. Creează entitățile + migrare.
2. `CheckoutController` cu flux în pași (poate fi o singură pagină cu
   formular sau 2-3 pași — alege o singură pagină pentru MVP):
   - selectare/adăugare adresă de livrare
   - selectare metodă de plată (radio: card/ramburs/transfer)
   - sumar comandă + buton „Plasează comanda"
3. La submit: validează stocul din nou (poate s-a schimbat între
   adăugarea în coș și checkout), creează `Order` + `OrderItem`-uri din
   conținutul coșului, scade stocul pentru produsele `in_stock`, golește
   coșul.
4. Email de confirmare comandă — folosește Symfony Mailer, subiect și
   conținut care includ `store.name` (dinamic, nu hardcodat).
5. Pagină „Comenzile mele" (`/cont/comenzi`) — listă comenzi ale
   userului logat, cu status.
6. Pagină detaliu comandă (`/cont/comenzi/{id}`) — doar accesibilă
   userului care deține comanda sau unui admin.

## Criterii de acceptare

- [ ] Checkout complet funcțional pentru toate 3 metodele de plată
      (chiar dacă plata efectivă e doar simulată până la task 7).
- [ ] Stocul se actualizează corect la plasarea comenzii.
- [ ] Un user nu poate vedea comanda altui user (verifică prin ID direct
      în URL — trebuie să dea 403, nu să afișeze datele).
- [ ] Emailul de confirmare ajunge (testează cu Mailtrap sau `mailer`
      local în dev) și conține numele magazinului corect din `store`.
