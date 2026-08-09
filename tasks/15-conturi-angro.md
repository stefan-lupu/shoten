# Task 15 — Conturi angro (B2B)

## Depinde de
Task 4 (utilizatori/auth), Task 11 (admin panel).

## Obiective
Un client poate solicita un cont angro (firmă), rămâne cont normal de
retail până la aprobare manuală din admin, iar după aprobare capătă acces
la prețurile de angro (task 16) și e marcat distinct în comenzi (task 17).

Aprobarea e **manuală, niciodată automată** — un cont angro dă acces la
prețuri mai mici, deci trebuie verificat că firma e reală înainte de
activare (fraudă/abuz).

**Non-obiectiv explicit**: integrare e-Factura ANAF (SPV, XML UBL,
semnătură). E un proiect separat, cu obligații legale proprii — nu intră
în acest task, doar facturarea „pe hârtie" (PDF, ca acum) cu date de firmă.

## Modificări pe entitatea `User`

- `companyName` (string, nullable)
- `companyCui` (string, nullable)
- `companyRegCom` (string, nullable)
- `companyAddress` (string, nullable)
- `wholesaleStatus` (enum `WholesaleStatus`: `None` implicit,
  `Pending`, `Approved`, `Rejected`)
- `wholesaleRequestedAt` (datetime, nullable)

Rol nou `ROLE_WHOLESALE` — se adaugă la `roles` **doar** la aprobare, se
scoate dacă un admin respinge/dezactivează ulterior contul (nu ștergem
istoric, doar schimbăm `wholesaleStatus` + rolul).

## Pași

1. Migrare pentru câmpurile noi de mai sus + enum-ul `WholesaleStatus`.
2. Adaugă `ROLE_WHOLESALE` în `role_hierarchy` din `security.yaml` — fără
   moștenire specială, e un rol paralel cu `ROLE_USER`, nu un substitut.
3. Pagină nouă `/cont/angro` (protejată `ROLE_USER`): formular de
   solicitare cont angro — `companyName`, `companyCui`, `companyRegCom`,
   `companyAddress`. La trimitere: salvează datele pe `User`, setează
   `wholesaleStatus = Pending`, `wholesaleRequestedAt = now()`, trimite
   email de confirmare primire cerere.
   - Dacă `wholesaleStatus` e deja `Pending`/`Approved`, pagina arată
     statusul curent în loc de formular (nu permite cereri duplicate).
4. Extinde `UserCrudController` (admin) cu:
   - filtru „Status angro" (`wholesaleStatus`)
   - acțiuni pe rând „Aprobă cont angro" / „Respinge cont angro”,
     vizibile doar când `wholesaleStatus = Pending`
   - la aprobare: adaugă `ROLE_WHOLESALE`, `wholesaleStatus = Approved`,
     trimite email de confirmare cu acces la prețuri angro
   - la respingere: `wholesaleStatus = Rejected`, trimite email cu motiv
     (câmp text liber completat de admin în formularul de respingere)
   - **CSRF pe ambele acțiuni** — au efect real (acces la prețuri), la
     fel ca acțiunile din `OrderCrudController` (vezi tasks deja
     implementate — `linkToUrl` + token per-entitate, nu `linkToCrudAction`
     simplu).
5. În `templates/account/index.html.twig` (sau echivalent): dacă
   `wholesaleStatus = None`, afișează link „Solicită cont angro”; dacă
   `Pending`, afișează „Cerere în așteptare”; dacă `Approved`, afișează
   un mesaj/badge „Cont angro activ”.

## Criterii de acceptare

- [ ] Un user obișnuit nu vede prețuri/opțiuni angro nicăieri înainte de
      a cere și a fi aprobat.
- [ ] Cererea de cont angro nu poate fi trimisă de două ori în paralel
      (al doilea request pe un cont deja `Pending` nu creează stare nouă).
- [ ] Aprobarea din admin adaugă efectiv `ROLE_WHOLESALE` — verificabil
      prin faptul că userul vede acum prețurile de tier (după task 16).
- [ ] Respingerea nu lasă contul cu acces parțial — `ROLE_WHOLESALE`
      absent, `wholesaleStatus = Rejected`, userul poate re-solicita.
- [ ] Acțiunile de aprobare/respingere din admin sunt protejate CSRF și
      doar `ROLE_ADMIN` le poate declanșa.
