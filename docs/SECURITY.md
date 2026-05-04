# Säkerhet — APP_KEY och hemligheter

## Vad som gäller för Laravel `APP_KEY`

`APP_KEY` används bland annat till kryptering och signerade cookies. Den ska **endast** finnas i:

- Render (eller annan värd) som **miljövariabel**
- Lokal `.env` (**versionshanteras inte**, se `.gitignore`)
- Under PHPUnit körs en **slumpad** nyckel via `tests/phpunit-bootstrap.php` — den hamnar aldrig i git.

**Commit aldrig** `APP_KEY=base64:...` i filer som pushas till GitHub.

## Om en nyckel ändå har läckt till GitHub

1. **Ta bort den från aktuell kod** (gjort i `v1.2.1` för PHPUnit-fallet).
2. **Rotera produktionsnyckeln** på Render om du inte är 100 % säker på att den läckta strängen aldrig använts i prod:
   - Lokalt: `php artisan key:generate --show`
   - Kopiera värdet till Render → Web Service → **Environment** → `APP_KEY` → **Save**
   - **Manual Deploy** (containern startar om med ny nyckel)
   - *Konsekvens:* befintliga sessioner/krypterade cookies kan ogiltigförklaras — användare kan behöva logga in igen.
3. **GitGuardian / GitHub Secret scanning:** följ deras “resolve”-flöde när läckan är åtgärdad i `main`.
4. **Historik:** nyckeln kan synas i **gamla commits**. Det är oftast **okej** om prod har en **annan** nyckel och du har roterat. Vill du rensa historiken krävs `git filter-repo`/force-push och samordning med alla som klonat repot — gör bara om du vet vad det innebär.

## Skydd mot återfall

GitHub Actions kör ett steg som **failar bygget** om versionsstyrd kod innehåller `APP_KEY=…base64:` följt av minst ~32 base64-tecken (exkl. `vendor/`, `node_modules`). Rena dokumentationsreferenser utan nyckel triggars inte.
