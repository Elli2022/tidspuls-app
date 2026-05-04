# Ändringslogg

Format inspirerat av [Keep a Changelog](https://keepachangelog.com/sv/1.1.0/), versioner enligt [Semantic Versioning](https://semver.org/lang/sv/).

## [Unreleased]

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
