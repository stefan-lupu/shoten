# Task 5 — Coș de cumpărături

## Depinde de
Task 2 (Product), Task 4 (User).

## Obiective
Coș funcțional pentru vizitatori nelogați (păstrat în sesiune) care se
transferă/persistă când userul se loghează.

## Entități de creat

### `Cart`
- `user` (ManyToOne → User, nullable — null pentru coș de sesiune neasociat)
- `sessionId` (string, nullable — folosit cât timp userul nu e logat)
- `createdAt`, `updatedAt`

### `CartItem`
- `cart` (ManyToOne → Cart)
- `product` (ManyToOne → Product)
- `quantity` (integer)
- `unitPrice` (decimal 10,2) — **snapshot** al prețului la momentul
  adăugării (nu recalculat live din Product, ca să nu se schimbe
  retroactiv dacă prețul produsului se modifică)

## Pași

1. Creează entitățile + migrare.
2. `src/Service/CartManager.php` — serviciu care centralizează logica:
   `addItem()`, `removeItem()`, `updateQuantity()`, `getCurrentCart()`,
   `getTotal()`. `getCurrentCart()` identifică coșul curent din sesiune
   sau din `user` dacă e logat.
3. La login (event listener pe `LoginSuccessEvent` sau în
   authenticator), transferă itemii din coșul de sesiune către coșul
   asociat userului, dacă există unul.
4. `CartController`: rute `app_cart` (vizualizare), `app_cart_add`,
   `app_cart_remove`, `app_cart_update` (AJAX sau form POST clasic —
   alege POST clasic pentru MVP, simplu de întreținut).
5. Validează stocul la adăugare: nu permite `quantity` mai mare decât
   `product.stock` pentru produsele `in_stock` (pentru `on_order` nu
   există limită strictă de stoc).
6. Afișează numărul de produse din coș în header (`base.html.twig`),
   folosind `store` global nu e nevoie aici — direct din `CartManager`
   injectat printr-un Twig extension sau un context processor simplu.

## Criterii de acceptare

- [ ] Adăugare/eliminare/actualizare cantitate funcționează fără eroare.
- [ ] Coșul persistă între request-uri pentru vizitator nelogat (sesiune).
- [ ] La login, coșul de sesiune se combină cu coșul existent al userului
      (nu se pierde niciun produs).
- [ ] Total-ul coșului se calculează corect folosind `unitPrice`
      snapshot-uit, nu prețul curent din `Product`.
- [ ] Nu se poate adăuga o cantitate mai mare decât stocul disponibil
      pentru produsele `in_stock`.
