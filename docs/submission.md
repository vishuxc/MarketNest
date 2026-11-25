# MarketNest submission and Git commands

Review the [README](../README.md), [architecture](architecture.md), and [assignment traceability table](phase-6-qa.md#assignment-requirement-traceability). Ship the verified MarketNest-Final ZIP. Each recipient uses their own MySQL schema and credentials and can populate it from the included migrations and seeders.

## Initial commit

Run the following in PowerShell from the project folder. The explicit list stages the application and documentation paths. Git's ignore rules exclude local environment files, database dumps, runtime logs, uploads, and ZIP archives.

```powershell
Set-Location -LiteralPath 'D:\laravel intership'
git add -- .editorconfig .env.example .gitattributes .gitignore README.md app artisan bootstrap composer.json composer.lock config database docs package-lock.json package.json phpunit.xml public resources routes storage tests vite.config.js
git diff --cached --name-only
git diff --cached --check
git ls-files -- .env '*.zip' 'public/storage/*' 'storage/logs/*.log'
git commit -m "Initial MarketNest submission"
git branch -M main
```

The `git ls-files` check should return no paths. Review the staged manifest and resolve any unexpected entries before committing.

## GitHub upload after approval

Create an empty GitHub repository, then supply its HTTPS URL when prompted. These commands assume the local repository has no remote named `origin`.

```powershell
$marketNestRepositoryUrl = Read-Host 'Paste the empty GitHub repository HTTPS URL'
git remote add origin $marketNestRepositoryUrl
git push -u origin main
```
