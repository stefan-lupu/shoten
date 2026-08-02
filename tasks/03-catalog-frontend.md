# Task 3 — Catalog frontend (listare, categorie, produs) + SEO de bază

## Depinde de
Task 2 (entități Category/Product/ProductImage populate cu date).

## Obiective
Paginile publice de catalog, cu status de stoc afișat clar și SEO de bază
per pagină (esențial pentru task 12, dar structura se pune acum).

## Pași

1. `php bin/console make:controller ProductController`.
2. Rută `app_home` (`/`) — afișează produse recente/populare
   (`ProductRepository::findFeatured()`), grid de carduri.
3. Rută `app_category_show` (`/categorie/{slug}`) — listă produse din
   categoria respectivă, cu breadcrumb (Acasă > Categorie).
4. Rută `app_product_show` (`/produs/{slug}`) — pagină produs completă:
   galerie imagini (din `ProductImage`, ordonate după `position`), preț,
   descriere, badge status stoc.
5. Badge status stoc — component Twig reutilizabil:
   - `in_stock` → verde, text „În stoc"
   - `on_order` → galben/auriu, text „La comandă, ~{{ estimatedDays }} zile"
6. Fiecare template extinde `base.html.twig` și suprascrie blocurile
   `title` și `meta_description` folosind `product.metaTitle`/`metaDescription`
   cu fallback la `store.name`/`store.defaultMetaDescription` dacă sunt goale.
7. Paginare pe listele de produse (Doctrine Paginator sau
   `knplabs/knp-paginator-bundle`).

## Criterii de acceptare

- [ ] `/` afișează produse reale din DB, nu date mock.
- [ ] `/categorie/{slug}` cu slug inexistent → 404 curat (nu eroare 500).
- [ ] `/produs/{slug}` afișează corect badge-ul de stoc pentru ambele
      cazuri (`in_stock` și `on_order`).
- [ ] Title-ul paginii de produs (tab browser) reflectă `metaTitle` al
      produsului, nu doar numele generic al magazinului.
- [ ] Toate textele vizibile (butoane, etichete) sunt în română.
