Public setup API routes are ready. They don’t need login, but they **do require** `SETUP_KEY` from your `.env`.

### Routes

| Action         | URL                                                   |
| -------------- | ----------------------------------------------------- |
| Migrate        | `/api/setup/migrate?key=YOUR_SETUP_KEY`               |
| Seed           | `/api/setup/seed?key=YOUR_SETUP_KEY`                  |
| Seed one class | `/api/setup/seed?key=YOUR_SETUP_KEY&class=UserSeeder` |
| Migrate + seed | `/api/setup/migrate-seed?key=YOUR_SETUP_KEY`          |
| Status         | `/api/setup/status?key=YOUR_SETUP_KEY`                |

### Example

```text
http://127.0.0.1:8081/api/setup/migrate-seed?key=YOUR_SETUP_KEY
```

Or with header:

```bash
curl -H "X-Setup-Key: YOUR_SETUP_KEY" http://127.0.0.1:8081/api/setup/migrate
```

A `SETUP_KEY` was added to your `.env` — open `.env` and copy that value into the URL. Change it to something strong before production.
