# Task 9 — Recenzii produse

## Depinde de
Task 2 (Product), Task 4 (User).

## Obiective
Recenzii cu rating, moderate manual înainte de a apărea public (evită spam).

## Entitate `Review`
- `product` (ManyToOne → Product)
- `user` (ManyToOne → User)
- `rating` (integer, 1-5)
- `comment` (text)
- `status` (string: `pending`, `approved`, `rejected`) — implicit `pending`
- `createdAt`

## Pași

1. Creează entitatea + migrare.
2. Formular recenzie pe pagina de produs, vizibil doar userilor logați
   (`is_granted('ROLE_USER')`), cu selector stele 1-5 + textarea.
3. Restricție: un user poate lăsa o singură recenzie per produs (validare
   la nivel de entitate + constrângere unică compusă `product_id, user_id`).
4. Pe pagina de produs, afișează doar recenziile cu `status = approved`,
   plus media rating-ului calculată din acestea.
5. Recenziile `pending`/`rejected` nu sunt vizibile public — moderarea
   propriu-zisă (aprobare/respingere) se implementează în task 11 (admin).

## Criterii de acceptare

- [ ] O recenzie nouă intră implicit ca `pending`, nu apare public
      imediat.
- [ ] Un user nu poate lăsa 2 recenzii pentru același produs.
- [ ] Media rating-ului afișată pe pagina de produs se calculează doar
      din recenziile `approved`.
- [ ] Un user nelogat vede formularul de recenzie înlocuit cu un link
      către login, nu eroare.
