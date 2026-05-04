# Ändringslogg

Format inspirerat av [Keep a Changelog](https://keepachangelog.com/sv/1.1.0/), versioner enligt [Semantic Versioning](https://semver.org/lang/sv/).

## [Unreleased]

## [1.3.1] — 2026-05-06

### Ändrat

- Personnummer normaliseras konsekvent till formatet `ÅÅMMDD-XXXX` vid inloggning, registrering och när användaren sparas, så att inloggning fungerar även om man skriver med eller utan bindestreck / med fullt årtal.
- Migration som uppdaterar befintliga `users.personnummer` till kanoniskt format (hoppar över vid dublettkonflikt).

## [1.3.0] — 2026-05-05

### Tillagt

- **Attest av tid:** status på varje `time_entry`: `draft`, `submitted`, `approved`, `rejected`. Befintliga poster sätts vid migration till `approved` (historik).
- API:
  - `GET /api/v1/time-entries/pending-review` — lista kollegors poster som väntar attest (admin/chef, samma organisation).
  - `POST /api/v1/time-entries/{id}/submit` — medarbetare skickar avslutad stämpling (`clock_out` krävs).
  - `POST /api/v1/time-entries/{id}/approve` och `POST …/reject` (valfritt fält `reason`) — admin/chef; inte attestera sig själv.
- Frontend: Hem visar attest-status och ”Skicka för attest”; sida `/attest` för chefslista med godkänn/avslå.

### Ändrat

- Redigering och borttagning av tidposter tillåts endast i `draft` eller `rejected` (inte för väntande eller godkända poster).

## [1.2.2] — 2026-05-04

### Säkerhet

- CI failar om versionsstyrd kod innehåller `APP_KEY=base64:` (förhindrar återfall).
- Dokumentation: `docs/SECURITY.md` (rotation på Render, historik, GitGuardian).

## [1.2.1] — 2026-05-04

### Säkerhet

- Tog bort `APP_KEY` från versionsstyrd `phpunit.xml`; tester använder istället en slumpad nyckel i `tests/phpunit-bootstrap.php` (inte committed).

### Ändrat

- PHPUnit-bootstrap dokumenteras för lokala körningar utan `.env`.

## [1.2.0] — 2026-05-02

### Tillagt

- Tabellen `organizations` och koppling `users.organization_id`.
- Roller per användare: `admin`, `manager`, `employee` (grund för attest/frånvaro m.m.).
- Vid registrering skapas en organisation (valfritt namn via `organization_name`); första användaren blir **admin**.
- Befintliga användare vid migration tilldelas organisationen **Standardorganisation** som **employee**.
- API: `GET /api/v1/organization/members` (admin eller chef ser kollegor i samma organisation).
- `/api/v1/me` inkluderar nu även `organization`-relation.

## [1.1.0] — 2026-05-01

### Tillagt

- Visning av GPS-koordinater på sparade stämplingar, valfri anteckning per rad samt sida **Mina uppgifter**.

## [1.0.0] — 2026-05-01

### Tillagt

- Första publika Tidspuls-baseline (tidsspårning, auth, deploy-Netlify/Render).
