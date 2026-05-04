# Versioner och GitHub-releases

## Princip

- **Semantic versioning**: `MAJOR.MINOR.PATCH` (`v1.2.0`, `v1.3.1`, …).
- **`main`** är integrationsgren; funktioner slås ihop hit och taggas vid stabila milstolpar.
- **`CHANGELOG.md`** sammanfattar vad som ändrats mellan versioner.

## Tagga en release (lokalt)

```bash
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release v1.2.0: organisation och roller"
git push origin main --follow-tags
```

På GitHub: **Releases → Draft a new release**, välj taggen och klistra in relevant utdrag från `CHANGELOG.md`.

## Rekommenderad arbetsordning för större funktioner

1. Gren feature/`beskrivning` eller direkt små commits på `main` om du jobbar själv.
2. Tester och migrate lokalt / CI grönt.
3. Merge till `main`, deploy backend (migrate körs), sedan frontend-build.
4. Tag + GitHub Release.
