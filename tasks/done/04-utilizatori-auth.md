# Task 4 — Utilizatori, roluri și autentificare

## Depinde de
Task 1 (fundație).

## Obiective
Cont client cu înregistrare/autentificare. Roluri simple pentru MVP:
`ROLE_USER` (client) și `ROLE_ADMIN` (tu). Structură pregătită pentru
roluri suplimentare ulterioare (`ROLE_SUPPORT`, `ROLE_EDITOR`) fără
refactor major.

## Entitate `User`

- `email` (string, unic, folosit ca identifier)
- `password` (string, hash)
- `firstName`, `lastName` (string)
- `phone` (string, nullable)
- `roles` (json/array, implicit `['ROLE_USER']`)
- `createdAt` (datetime_immutable)
- Relație OneToMany către o viitoare entitate `Address` (task 6) —
  poate fi adăugată acum ca placeholder sau lăsată pentru task 6.

## Pași

1. `php bin/console make:user` — răspunde: da la "store in database",
   proprietate de identificare `email`, da la "hash passwords".
2. `php bin/console make:auth` — alege "Login form authenticator",
   generează `SecurityController` + `LoginFormAuthenticator`.
3. `php bin/console make:registration-form` — generează formularul de
   înregistrare + verificare email (poți simplifica verificarea email
   pentru MVP, dar lasă structura pregătită).
4. În `config/packages/security.yaml`:
   - configurează `role_hierarchy` cu `ROLE_ADMIN: [ROLE_USER]` de la
     început (ușor de extins ulterior cu ROLE_SUPPORT/ROLE_EDITOR).
   - protejează `/admin*` cu `ROLE_ADMIN` prin `access_control`.
   - protejează `/cont*` (pagini cont client) cu `ROLE_USER`.
5. Creează manual (sau prin fixture) un utilizator `ROLE_ADMIN` de test.
6. Adaugă în `base.html.twig` link dinamic „Cont" / „Autentificare" în
   funcție de `is_granted('ROLE_USER')`.

## Criterii de acceptare

- [ ] Înregistrare cont nou funcționează, parola e hash-uită (niciodată
      în clar în DB).
- [ ] Login/logout funcționează.
- [ ] Un user cu `ROLE_USER` nu poate accesa `/admin` (redirect/403).
- [ ] Un user cu `ROLE_ADMIN` poate accesa `/admin` (chiar dacă panoul
      propriu-zis vine abia în task 11 — doar ruta protejată e suficientă
      acum, poate fi un controller placeholder).
- [ ] Emailurile utilizatorilor sunt unice — încercarea de înregistrare
      cu un email existent dă eroare de validare clară, în română.
