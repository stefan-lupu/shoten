# Task 7 — Integrare plăți (card, ramburs, transfer bancar)

## Depinde de
Task 6 (Order/OrderItem, flux checkout).

## Obiective
Trei metode de plată funcționale. Cheile API vin din `.env.local`
(diferite per magazin), nu se hardcodează niciodată.

## Pași

### Ramburs (COD) — cel mai simplu, implementează primul
1. La checkout, dacă `paymentMethod = cod`: comanda se creează direct cu
   `paymentStatus = pending`, fără procesare online. Se marchează `paid`
   manual din admin (task 11) când banii ajung.

### Transfer bancar
1. La checkout, dacă `paymentMethod = bank_transfer`: comanda se creează
   cu `paymentStatus = pending`, iar pagina de confirmare + emailul
   afișează datele de cont bancar (adaugă `STORE_BANK_ACCOUNT` și
   `STORE_BANK_NAME` în `.env.local` și `StoreConfig`, ca să difere per
   magazin).
2. Admin marchează manual `paid` după verificare (task 11).

### Card online
1. Alege provider — Netopia sau Stripe sunt cele mai practice pentru
   România (Stripe are payouts RON limitate, verifică disponibilitate
   curentă înainte de a alege).
2. Creează `src/Service/Payment/CardPaymentService.php` — interfață
   clară: `createPaymentSession(Order $order): string` (returnează URL
   de redirect) și `handleWebhook(Request $request): void` (procesează
   callback-ul de confirmare de la provider).
3. Cheile API (`NETOPIA_API_KEY`, `NETOPIA_SIGNATURE` sau echivalent
   Stripe) vin din `.env.local`, injectate în serviciu prin
   `services.yaml` — același pattern ca `StoreConfig`.
4. Rută webhook dedicată (ex: `/webhook/payment/netopia`) care validează
   semnătura request-ului înainte de a actualiza `paymentStatus`.
5. La confirmare plată reușită: `paymentStatus = paid`, `status = confirmed`,
   trimite email de confirmare plată.
6. La eșec: `paymentStatus = failed`, permite userului să reîncerce
   plata din „Comenzile mele".

## Criterii de acceptare

- [ ] Toate 3 metodele de plată produc o comandă validă în DB.
- [ ] Webhook-ul de card verifică semnătura/autenticitatea request-ului
      înainte de a schimba orice status (niciodată încredere oarbă
      într-un payload extern).
- [ ] Datele de cont bancar afișate la transfer sunt din `.env.local`,
      nu hardcodate.
- [ ] Un test manual de plată eșuată (card refuzat) nu lasă comanda
      într-o stare ambiguă — statusul reflectă clar eșecul.
