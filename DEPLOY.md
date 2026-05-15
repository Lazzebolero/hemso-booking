# Deploy-checklista (staging / production)

Använd denna lista vid varje deploy till staging eller produktion. Kör röktest **på servern** efter deploy och **mot URL** för att verifiera att webbservern svarar.

## Före deploy

- [ ] Alla tester gröna lokalt: `php artisan test --compact`
- [ ] Bygg frontend: `npm ci && npm run build`
- [ ] Miljövariabler uppdaterade (`.env` på servern):
  - `APP_ENV=production` (eller `staging`)
  - `APP_DEBUG=false`
  - `APP_URL` satt till rätt domän
  - Databas, mail, `FILESYSTEM_DISK` (hundbilder)
- [ ] Backup av databas och `storage/app` (besökshundsbilder)

## Deploy-kommandon (på servern)

```bash
git pull origin main   # eller er branch
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link    # om public/storage saknas
php artisan config:cache
php artisan route:cache
php artisan view:cache
# npm run build körs normalt i CI eller lokalt innan deploy
```

## Röktest efter deploy

### 1. Lokalt (valfritt, före push)

```bash
php artisan deploy:smoke
```

I `local` är `APP_DEBUG=true` OK — kommandot ska passera utan `--strict` eller med `--strict`.

### 2. På servern efter deploy (SSH)

```bash
php artisan deploy:smoke --strict
```

Kontrollerar: `APP_KEY`, databas, migrationer, storage, Vite-manifest. I staging/production ska `APP_DEBUG` vara av — annars varning/fel med `--strict`.

### 3. HTTP mot staging/prod

```bash
php artisan deploy:smoke --url=https://staging.er-domän.se --strict
```

Verifierar att `/up` och `/login` svarar 2xx via webbservern.

### 4. Manuellt röktest i webbläsaren

Logga in och verifiera kort:

| Roll | Vad |
|------|-----|
| Admin | Besökshundar → lista, visa post, tillbaka med datumfilter |
| Host | Samma via host-arbetsyta |
| Guide | Mina besökshundar, skapa registrering |
| Alla | Tidrapportering öppnas utan layoutfel |

Extra vid behov:

- [ ] Ladda upp en hundbild och öppna den i listan/galleriet
- [ ] Admin → Systemhälsa: inga röda fel
- [ ] Cron/scheduler: heartbeat uppdaterad (se systemhälsa)

## Bakgrundsjobb (kö)

Om `QUEUE_CONNECTION=database` i `.env`:

```bash
php artisan migrate --force   # skapar jobs + failed_jobs om de saknas
php artisan queue:work --stop-when-empty   # manuell körning
```

I produktion ska en process köra `php artisan queue:work` kontinuerligt (supervisor/systemd) eller via er hosting-panel.

Kontrollera status under **Admin → Systemhälsa** (kortet Jobbkö).

## Övervakning (minimum efter go-live)

- Extern ping mot `https://er-domän.se/up` (förväntat HTTP 200)
- Loggnivå `LOG_LEVEL=warning` eller `error` i produktion
- Admin → Systemhälsa vid incidenter

## Rollback

- Återställ föregående release/tag
- `php artisan migrate:rollback` **endast** om migrationen är säker att rulla tillbaka
- Återställ databasbackup om datamigration kördes

## Felsökning

| Symptom | Åtgärd |
|---------|--------|
| Vit sida / 500 | `storage/logs/laravel.log`, `APP_DEBUG` tillfälligt av, kör `deploy:smoke` |
| Ingen CSS/JS | Kör `npm run build`, kontrollera `public/build/manifest.json` |
| Hundbilder syns inte | `php artisan storage:link`, filrättigheter på `storage/` |
| `/up` fungerar men inloggning inte | Session/cookies, `SESSION_DOMAIN`, HTTPS |
