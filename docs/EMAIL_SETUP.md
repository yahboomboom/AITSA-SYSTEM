# Setting Up Real Email Delivery

The system already sends an email automatically when a student's account is created
(after their reservation fee is confirmed paid) — it includes their new Student ID
and password. Right now that email doesn't actually go anywhere; it just gets
written into a log file. This guide gets it landing in real inboxes.

Estimated time: 5 minutes.

## Step 1 — Turn on 2-Step Verification on the sending Gmail account

Pick the Gmail account AITSA will send from (this can be a personal Gmail, or a
Google Workspace account if the school has one).

1. Go to https://myaccount.google.com/security
2. Under "How you sign in to Google," turn on **2-Step Verification** if it isn't
   already on. Follow Google's prompts (usually just confirming your phone number).

You can't create an App Password without this step turned on first.

## Step 2 — Create an App Password

1. Go to https://myaccount.google.com/apppasswords
   (if that link asks you to sign in again, that's normal)
2. Under "App name," type something like `AITSA System` and click **Create**.
3. Google will show you a **16-character code** in a yellow box, like `abcd efgh ijkl mnop`.
   Copy it (remove the spaces when you use it — see Step 3).
   You will NOT be able to see this code again after you close the box, so copy it now.

This app password is different from your normal Gmail password. It only works for
this one purpose, and you can revoke it later from the same page without affecting
your normal Gmail login.

## Step 3 — Edit the project's `.env` file

Open the `.env` file in the project root folder (`AITSA-SYSTEM/.env`). Find this
block near the top:

```
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Replace it with this, filling in your own Gmail address and the app password from Step 2:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=youraddress@gmail.com
MAIL_PASSWORD="abcdefghijklmnop"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="youraddress@gmail.com"
MAIL_FROM_NAME="AITSA Admissions"
```

Notes:
- `MAIL_USERNAME` and `MAIL_FROM_ADDRESS` should be the same Gmail address.
- `MAIL_PASSWORD` is the 16-character app password from Step 2, with the spaces
  removed. Keep the quotes around it.
- Do **not** commit this `.env` file to git with real credentials in it — `.env`
  is already excluded from git, so this should happen automatically, but double
  check with `git status` after editing that `.env` doesn't show up as a change
  to be committed.

## Step 4 — Restart the server and test

If you're running `php artisan serve`, stop it (Ctrl+C) and start it again so it
picks up the new `.env` values.

To test it's working, go through the real flow once:
1. Submit a test application at `/apply`.
2. Pay the ₱ reservation fee (or, as a registrar, go to the Registrar Dashboard's
   Pending Admission Applications list and click "Mark as Paid" next to the test
   applicant).
3. Check the inbox of the email address you used for the test application — the
   "Your AITSA Student Account Is Ready" email should arrive within a few seconds.

If it doesn't arrive, check `storage/logs/laravel.log` for an error message
starting with `Mail` — the most common causes are a typo in the app password, or
2-Step Verification not actually being turned on yet.

## If Gmail's sending limit becomes a problem later

A personal Gmail account can send roughly 500 emails per day. If AITSA ever
needs more than that (e.g. during a very large admission cycle), switch to a
dedicated transactional email provider instead (Brevo, Resend, SendGrid, etc.) —
they all work the same way, just with different `MAIL_HOST`/`MAIL_PORT` values
and an API key instead of an app password. Ask if you need help switching later.
