# Brevo Email Setup Guide

This portfolio uses [Brevo](https://www.brevo.com/) (formerly Sendinblue) for transactional email delivery from the contact form.

Two deployment options are supported: **Vercel (recommended)** and **traditional PHP hosting**.

---

## Prerequisites

- A free or paid [Brevo account](https://www.brevo.com/)
- Get your Brevo API key from [app.brevo.com/settings/apis](https://app.brevo.com/settings/apis)

---

## Step 1: Get Your Brevo API Key

1. Log in to [your Brevo dashboard](https://app.brevo.com/)
2. Go to **Settings** → **SMTP & APIs**
3. Under the **API keys** tab, click **Create new API key**
4. Enter a name (e.g., "Portfolio Contact Form")
5. Copy the generated API key — you'll need it in Step 2

---

## Step 2: Configure Environment Variables

### Option A: Vercel Deployment (Recommended)

On Vercel, environment variables are set in the **Project Settings** — not from a `.env` file at runtime.

1. Go to your project in the [Vercel dashboard](https://vercel.com/dashboard)
2. Navigate to **Settings** → **Environment Variables**
3. Add these three variables (select **Production**, **Preview**, and **Development**):

| Name | Value | Environment |
|---|---|---|
| `BREVO_API_KEY` | Your Brevo API key from Step 1 | Production, Preview, Development |
| `BREVO_FROM_EMAIL` | `support@mikenlabs.com` (must be verified in Brevo) | Production, Preview, Development |
| `BREVO_TO_EMAIL` | `support@mikenlabs.com` | Production, Preview, Development |

4. For local development with `vercel dev`, create a `.env.local` file:

```bash
cp .env.template .env.local
# Edit .env.local and fill in your real values
```

### Option B: Traditional PHP Hosting

1. Copy `.env.template` to `.env` in your project root:

```bash
cp .env.template .env
```

2. Open `.env` and fill in your real values (already populated if you followed earlier steps)

---

## Step 3: Verify Your Sender Email in Brevo

1. In your Brevo dashboard, go to **Settings** → **Sender authentication**
2. Add and verify your `BREVO_FROM_EMAIL` address (Brevo sends a confirmation email)
3. Until verified, emails may be rejected by the API

---

## Step 4: Test Locally

### Vercel (Local Development)

```bash
npm install -g vercel
vercel dev
# Or if you have Vercel CLI already:
npx vercel dev
```

Then visit `http://localhost:3000` and submit the contact form.

### Traditional PHP Hosting

```bash
php -S localhost:8000
```

Then visit `http://localhost:8000` and submit the contact form.

---

## Step 5: Deploy

### Deploy to Vercel

```bash
vercel
```

Vercel automatically detects the `api/send-email.js` file as a serverless function and maps it to `/api/send-email`.

Project structure on Vercel:

```
your-project/
├── index.html
├── css/
├── img/
├── js/
├── lib/
├── api/
│   └── send-email.js   ← Vercel serverless function
├── vercel.json          ← Function configuration
├── .vercelignore        ← Excludes PHP files from Vercel build
├── .env.local           ← Local dev only (not deployed)
└── .env.template        ← Reference template
```

> **Note**: `.vercelignore` excludes `send-email.php` from Vercel deployment. Vercel does not natively support PHP serverless functions — the Node.js function in `/api/send-email.js` handles all email delivery on Vercel.

### Deploy to Traditional PHP Hosting

```
your-project/
├── index.html
├── css/
├── img/
├── js/
├── lib/
├── .env          ← your real config (keep this PRIVATE)
├── .env.template  ← leave as a reference
├── send-email.php  ← PHP backup endpoint
└── vercel.json     ← can be ignored on PHP hosting
```

---

## Troubleshooting

| Problem | Solution |
|---|---|
| **500 / 503 error** | Check serverless function logs in Vercel dashboard |
| **"API key not configured"** | Verify environment variables are set (Vercel: Project Settings → Environment Variables; PHP: check `.env`) |
| **Emails not arriving** | Verify sender email in Brevo dashboard; check [Brevo activity logs](https://app.brevo.com/activities) |
| **401 Unauthorized** | Your Brevo API key is invalid or restricted — regenerate it |
| **Function timeout** | Brevo API has a 10s timeout configured in `vercel.json`; Vercel default is 10s |
| **`.env.local` not picked up** | Ensure file is named `.env.local` (not `.env`) for Vercel local dev |

---

## How It Works (Vercel)

```
[Visitor fills form]
        ↓
[index.html → AJAX POST (JSON) → /api/send-email]
        ↓
[api/send-email.js reads process.env.BREVO_API_KEY]
        ↓
[send-email.js calls Brevo API: https://api.brevo.com/v3/smtpEmail]
        ↓
[Brevo sends email to BREVO_TO_EMAIL]
        ↓
[AJAX response updates the page with success/error message]
```

## How It Works (PHP)

```
[Visitor fills form]
        ↓
[index.html → AJAX POST → send-email.php]
        ↓
[send-email.php reads .env for API key via loadEnv()]
        ↓
[send-email.php calls Brevo API via cURL]
        ↓
[Brevo sends email to BREVO_TO_EMAIL]
        ↓
[AJAX response updates the page with success/error message]
```

---

## Files Summary

| File | Purpose |
|---|---|
| `.env.template` | Template for environment variables (copy to `.env` or `.env.local`) |
| `api/send-email.js` | Node.js serverless function for Vercel (uses `process.env`) |
| `send-email.php` | PHP fallback endpoint for traditional hosting (reads `.env`) |
| `js/main.js` | Frontend AJAX handler — POSTs JSON to `/api/send-email` |
| `vercel.json` | Vercel function configuration (10s timeout, Node 18 runtime) |
| `.vercelignore` | Excludes PHP files from Vercel build (prevents runtime errors) |
| `css/style.css` | Includes `.alert.success` / `.alert.error` styles for form messages |
| `.gitignore` | Excludes `.env` and `node_modules` from version control |
