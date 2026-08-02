# Task 2 — Entități catalog (Category, Product, ProductImage)

## Depinde de
Task 1 (fundație + StoreConfig funcțional).

## Obiective
Modelul de date pentru catalog, ținând cont de stocul mixt (stoc propriu +
import la comandă) discutat în faza de concept.

## Entități de creat

### `Category`
- `name` (string, 255)
- `slug` (string, 255, unic)
- `description` (text, nullable)

### `Product`
- `name` (string, 255)
- `slug` (string, 255, unic)
- `description` (text)
- `price` (decimal 10,2)
- `origin` (string, 100) — ex: „Kyoto", „Osaka"
- `stockStatus` (string/enum: `in_stock` | `on_order`)
- `estimatedDays` (integer, nullable) — folosit doar când `stockStatus = on_order`
- `stock` (integer) — cantitate disponibilă când `stockStatus = in_stock`
- `metaTitle` (string, nullable) — SEO, completat manual sau fallback la `name`
- `metaDescription` (string, nullable) — SEO, fallback la `store.defaultMetaDescription`
- `category` (ManyToOne → Category)
- `createdAt` (datetime_immutable)

### `ProductImage`
- `filename` (string, 255)
- `position` (integer) — ordine afișare în galerie
- `product` (ManyToOne → Product, cu `orphanRemoval: true`)

## Pași

1. `php bin/console make:entity Category` — adaugă câmpurile de mai sus.
2. `php bin/console make:entity Product` — adaugă câmpurile de mai sus,
   inclusiv relația `category` (ManyToOne, `nullable: false`).
3. `php bin/console make:entity ProductImage` — relația `product`
   (ManyToOne, `nullable: false`).
4. Pe `Category`, adaugă relația inversă `products` (OneToMany) — utilă
   pentru pagina de categorie din task 3.
5. `php bin/console make:migration`, verifică fișierul generat manual
   înainte de a rula, apoi `php bin/console doctrine:migrations:migrate`.
6. Creează `src/Repository/ProductRepository.php` custom query:
   `findByCategorySlug(string $slug)` și `findFeatured(int $limit)`.
7. (Opțional dar recomandat) `make:fixtures` sau un `Command` simplu care
   populează 10-15 produse de test pentru rechizite, ca să ai date reale
   pentru task 3.

## Criterii de acceptare

- [ ] Migrarea rulează curat pe o bază de date goală.
- [ ] Un produs cu `stockStatus = on_order` are `estimatedDays` populat;
      un produs `in_stock` are `stock > 0`. Adaugă o constrângere logică
      (validare Symfony `#[Assert\Callback]` sau similar) care previne
      combinații inconsistente.
- [ ] Slug-urile sunt generate automat din nume (folosește un
      `EventListener`/`prePersist` sau un pachet de slug, ex. cocur/slugify)
      și sunt verificate ca unice.
- [ ] `ProductRepository::findByCategorySlug()` returnează corect produsele.
