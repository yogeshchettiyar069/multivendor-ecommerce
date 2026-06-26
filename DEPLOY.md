# Deploy for free — Render + MongoDB Atlas

This guide gets your marketplace live on the internet **at zero cost**, step by step.
It assumes you have **never deployed anything before** — every click is spelled out.

You'll use two free services:

- **MongoDB Atlas** (free **M0** cluster) — your database. _You already created this._
- **Render** (free **web service**) — runs the app in one Docker container.

Total time: ~20 minutes. Cost: **$0**.

---

## ⚠️ Before anything else: rotate your database password

Your Atlas password was shared in chat during development, so treat it as **exposed**.
Change it before the site goes public:

1. Go to **https://cloud.mongodb.com** → your project.
2. Left sidebar → **Database Access** → find user `yogeshchettiyar` → **Edit**.
3. Click **Edit Password** → **Autogenerate Secure Password** → **copy it somewhere safe**
   → **Update User**.
4. You'll paste this new password into the connection string in Step 2 below.

> **Never commit passwords to GitHub.** They go only into Render's dashboard (Step 4),
> never into a file you push.

---

## Step 1 — Push the project to GitHub

You said you'll do this yourself. Two reminders so nothing leaks:

1. Make sure **`.env` is git-ignored** (it already is in this project — good). Your real
   secrets live only in `.env` locally and in Render's dashboard, never in the repo.
2. Push to a repo named, say, `multivendor-ecommerce`:

   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   git branch -M main
   git remote add origin https://github.com/<your-username>/multivendor-ecommerce.git
   git push -u origin main
   ```

After pushing, edit the CI badge URL at the top of `README.md` to use your
`<your-username>/multivendor-ecommerce` path, then commit and push that one change.

---

## Step 2 — Get your Atlas connection string

1. **https://cloud.mongodb.com** → **Database** → your cluster → **Connect**.
2. Choose **Drivers**.
3. Copy the connection string. It looks like:

   ```
   mongodb+srv://yogeshchettiyar:<password>@cluster0.0mxil17.mongodb.net/?retryWrites=true&w=majority
   ```

4. Replace `<password>` with the **new password** from the rotation step.
   - **Important:** if your password contains special characters, URL-encode them.
     For example `#` becomes `%23`, `@` becomes `%40`, `:` becomes `%3A`.
   - Save this finished string — it's your **`MONGODB_URI`** for Step 4.

### Allow Render to connect

1. Atlas → **Network Access** → **Add IP Address**.
2. Click **Allow access from anywhere** (`0.0.0.0/0`) → **Confirm**.
   - Render's free tier doesn't give you a fixed IP, so this is required.

---

## Step 3 — Generate your APP_KEY

Laravel needs a secret app key. Generate one on your machine:

```bash
docker compose exec app php artisan key:generate --show
```

(or without Docker: `php artisan key:generate --show`)

It prints something like `base64:abc123...`. **Copy the whole thing** including the
`base64:` prefix — that's your **`APP_KEY`** for Step 4.

---

## Step 4 — Create the Render service from the blueprint

This repo ships a `render.yaml` blueprint, so Render configures almost everything for you.

1. Go to **https://render.com** and sign up / log in (use **Sign in with GitHub** — easiest).
2. On the dashboard click **New +** → **Blueprint**.
3. **Connect your GitHub account** if prompted, then pick your
   `multivendor-ecommerce` repository.
4. Render reads `render.yaml` and shows one service named **multivendor-ecommerce**.
   Click **Apply** / **Create**.
5. It will now ask you to fill in the **secret** values (the ones marked `sync: false`).
   Enter these:

   | Key                     | Value                                                            |
   | ----------------------- | --------------------------------------------------------------- |
   | `APP_KEY`               | the `base64:...` string from Step 3                              |
   | `MONGODB_URI`           | the finished connection string from Step 2                      |
   | `APP_URL`               | leave blank for now — you'll set it in Step 6                    |
   | `ASSET_URL`             | leave blank for now                                             |
   | `STRIPE_KEY`            | your Stripe **publishable** test key (`pk_test_...`) — optional |
   | `STRIPE_SECRET`         | your Stripe **secret** test key (`sk_test_...`) — optional      |
   | `STRIPE_WEBHOOK_SECRET` | leave blank for now — you'll set it in Step 7                   |

   > Skip the Stripe keys if you don't want card payments — UPI, Netbanking, and Cash
   > on Delivery still work without them.

6. Click **Create** / **Deploy**. The first build takes **5–10 minutes** (it builds the
   Docker image, runs migrations against Atlas, and seeds the demo catalogue because
   `SEED_ON_BOOT=true`).

Watch the **Logs** tab. When you see `nginx ... RUNNING` and the health checks pass,
it's live.

---

## Step 5 — Open your site

At the top of the Render service page you'll see a URL like:

```
https://multivendor-ecommerce.onrender.com
```

Click it. You should see the storefront with real products. Log in with
`admin@example.com` / `password` (see the demo accounts table in the README).

---

## Step 6 — Set APP_URL and redeploy

Now that you know your real URL, tell the app about it (needed for correct links and
asset paths):

1. Render → your service → **Environment**.
2. Set **`APP_URL`** = your full URL, e.g. `https://multivendor-ecommerce.onrender.com`
3. Set **`ASSET_URL`** = the same URL.
4. Click **Save Changes**. Render redeploys automatically.

---

## Step 7 — Stripe webhook (only if you enabled card payments)

Card orders are finalised by a Stripe webhook, so Stripe needs to know your URL:

1. **https://dashboard.stripe.com** → make sure you're in **Test mode** (toggle, top right).
2. **Developers** → **Webhooks** → **Add endpoint**.
3. **Endpoint URL:** `https://<your-app>.onrender.com/stripe/webhook`
4. **Select events:** add `payment_intent.succeeded` and `payment_intent.payment_failed`.
5. **Add endpoint**, then on its page click **Reveal** under **Signing secret** and copy
   the `whsec_...` value.
6. Back in Render → **Environment** → set **`STRIPE_WEBHOOK_SECRET`** = that `whsec_...`
   value → **Save Changes** (Render redeploys).

Test a card payment with Stripe's test card `4242 4242 4242 4242`, any future expiry,
any CVC.

---

## Free-tier things to know

- **It sleeps.** After ~15 minutes with no traffic, the free service spins down. The
  next visit takes ~30–50 seconds to wake (cold start). This is normal for free hosting
  and fine for a portfolio demo.
- **Storage is ephemeral.** File-based cache and sessions reset on every redeploy. The
  app is configured for this (`CACHE_STORE=file`, `SESSION_DRIVER=file`), so it just
  means users get logged out after a deploy. Your **data is safe** — it lives in Atlas,
  not on Render.
- **Re-seeding.** `SEED_ON_BOOT=true` re-runs the seeder on each deploy, but the seeder
  skips if demo data already exists, so it won't duplicate products. To stop seeding
  entirely once you're happy, set `SEED_ON_BOOT=false` in the Environment tab.
- **Auto-deploy.** Every `git push` to `main` triggers a new Render deploy automatically.

---

## Troubleshooting

| Symptom                                   | Fix                                                                                 |
| ----------------------------------------- | ----------------------------------------------------------------------------------- |
| Build fails immediately                   | Check the **Logs** tab. Usually a missing env var — confirm `APP_KEY` is set.        |
| Site loads but shows a 500 error          | Almost always `MONGODB_URI`. Re-check the password is URL-encoded and Network Access allows `0.0.0.0/0`. |
| "No suitable servers found"               | Atlas **Network Access** isn't allowing Render. Add `0.0.0.0/0`.                     |
| Products page is empty                    | Seeder didn't run. Ensure `SEED_ON_BOOT=true`, then **Manual Deploy → Clear build cache & deploy**. |
| Card payment never completes              | Webhook not set up (Step 7) or wrong `STRIPE_WEBHOOK_SECRET`.                        |
| Styles/images missing                     | Set `APP_URL` and `ASSET_URL` to your real Render URL (Step 6) and redeploy.         |

That's it — your marketplace is live and free. 🎉
