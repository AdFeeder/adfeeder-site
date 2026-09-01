# AdFeeder Website — Handoff Document

**Last updated:** [Insert Date]
**Prepared for:** Blanco Digital Ltd (trading as AdFeeder)
**Site:** https://webdev.adfeeder.com (staging/dev)

---

## 1. Overview

This document describes the current setup for the AdFeeder marketing website: where the code lives, how it gets deployed, and how to make changes going forward.

The site is a single-page marketing site for AdFeeder (digital marketing automation for automotive dealers), built in plain HTML/CSS/JS with a dark navy and coral design and Poppins font. It currently sits on a **staging subdomain** (`webdev.adfeeder.com`) separate from the live production domain (`adfeeder.com`).

---

## 2. Architecture

```
┌─────────────────┐        push        ┌──────────────────────┐
│   Local editing  │ ─────────────────▶ │   GitHub repository   │
│  (Claude / dev)  │                    │  davedibbleblanco/    │
└─────────────────┘                    │  adfeeder-site        │
                                        └──────────┬────────────┘
                                                   │ cron pull
                                                   │ every 5 min
                                                   ▼
                                        ┌──────────────────────┐
                                        │   GCP VM: topx-picker │
                                        │  35.242.151.115       │
                                        │  /var/www/webdev.     │
                                        │  adfeeder.com         │
                                        └──────────┬────────────┘
                                                   │ nginx + SSL
                                                   ▼
                                    https://webdev.adfeeder.com
```

### Key components

| Component | Detail |
|---|---|
| **GitHub repo** | [`davedibbleblanco/adfeeder-site`](https://github.com/davedibbleblanco/adfeeder-site) — **public** repo (deliberately, so the VM can pull with no stored credentials) |
| **Hosting VM** | Google Cloud Platform VM named `topx-picker`, IP `35.242.151.115`. This VM also hosts other unrelated apps (`fbo-offers`, `onboarding-app`, `topx-picker`) — the AdFeeder site is one of several nginx sites configured on it |
| **Web root** | `/var/www/webdev.adfeeder.com` on the VM |
| **Web server** | nginx, config at `/etc/nginx/sites-available/webdev.adfeeder.com` (symlinked into `sites-enabled`) |
| **SSL** | Free Let's Encrypt certificate via `certbot`, auto-renews via systemd timer — no manual action needed |
| **DNS** | `webdev.adfeeder.com` — A record pointing to `35.242.151.115`, managed wherever the main `adfeeder.com` DNS is hosted |

---

## 3. Deployment Workflow

1. Changes are made to `index.html` (or other site files) and committed to git.
2. Changes are pushed to `github.com/davedibbleblanco/adfeeder-site` on branch `main`.
3. A **cron job on the VM** (running as root) pulls the latest code automatically:
   ```
   */5 * * * * cd /var/www/webdev.adfeeder.com && git pull origin main >> /var/log/webdev-pull.log 2>&1
   ```
4. Within ~5 minutes, changes appear live at `https://webdev.adfeeder.com` — **no manual deploy step required**.

To check the pull log for errors:
```bash
cat /var/log/webdev-pull.log
```

### Making changes manually (without Claude)
If you ever need to push changes yourself from your own machine:
```bash
git clone https://github.com/davedibbleblanco/adfeeder-site.git
# edit files
git add .
git commit -m "your message"
git push origin main
```
No token is needed to push since the repo is public and you're the owner — GitHub will prompt for your own credentials/SSH key.

---

## 4. File Structure

```
adfeeder-site/
├── index.html       # Main landing page (nav, hero, pillars, products, footer, JS)
├── privacy.html      # Privacy Policy (auto-generated to match site styling)
├── cookies.html      # Cookie Policy (auto-generated to match site styling)
└── terms.html        # Agency Terms of Service (auto-generated to match site styling)
```

All four pages share the same `<style>` block, nav, and footer, copied from `index.html` so the site look stays consistent. If `index.html`'s nav/footer/branding changes, the legal pages should be regenerated or manually updated to match.

---

## 5. Outstanding Items / Known TODOs

- ⚠️ **Legal pages need review**: `privacy.html`, `cookies.html`, and `terms.html` were drafted as generic UK GDPR/PECR boilerplate. They contain placeholder text (e.g. `[Insert Date]`, generic payment terms) and **have not been reviewed by a solicitor**. Do not treat as final/compliant until reviewed.
- The site currently only has the four pages listed above — no blog, no additional inner pages yet.
- Nav dropdown links (Used Cars, New Cars, Commercial Vehicles, Support, Blog, About) are currently placeholder `#` links and don't go anywhere yet.
- Social icons in the footer (Facebook, X, Instagram, YouTube) are placeholder `#` links.
- This is a **staging** site (`webdev.adfeeder.com`). No process is currently set up for promoting this to the live `adfeeder.com` domain — that would need to be planned separately (e.g. swapping DNS, or copying files into the production web root once approved).

---

## 6. Access & Credentials

| What | Where |
|---|---|
| GitHub repo | https://github.com/davedibbleblanco/adfeeder-site (public, owned by `davedibbleblanco`) |
| VM SSH access | Via Google Cloud Console → Compute Engine → VM instances → `topx-picker` → SSH button (browser-based, no local setup needed) |
| GitHub Personal Access Token | **Not stored anywhere** — tokens were generated per-session for pushing and are not persisted in git config or on the VM (repo is public, so the VM doesn't need one to pull) |

**Note on tokens:** if a GitHub PAT is generated for future work, treat it as sensitive — revoke it via https://github.com/settings/tokens once no longer needed, and avoid pasting it anywhere it could be logged or persisted.

---

## 7. Useful Commands Reference

```bash
# SSH in via Cloud Console browser terminal — no local install needed

# Check nginx status
sudo systemctl status nginx

# Check nginx config for the AdFeeder site
sudo cat /etc/nginx/sites-available/webdev.adfeeder.com

# Test nginx config after any changes
sudo nginx -t

# Reload nginx after config changes
sudo systemctl reload nginx

# Check the auto-pull cron log
cat /var/log/webdev-pull.log

# View current cron jobs
sudo crontab -l

# Manually force a pull (bypass the 5-min wait)
cd /var/www/webdev.adfeeder.com && sudo git pull origin main

# Renew SSL manually (shouldn't be needed — auto-renews)
sudo certbot renew
```

---

## 8. Company Reference Details

- **Legal name:** Blanco Digital Ltd
- **Trading as:** AdFeeder
- **Company number:** 09675276 (England & Wales)
- **VAT number:** 219 6788 61
- **Registered office:** 31 The Balcony, Castle Arcade Quarter, Cardiff, CF10 1BY, United Kingdom
- **Phone:** 02922 749355
- **Email:** contact@adfeeder.com
