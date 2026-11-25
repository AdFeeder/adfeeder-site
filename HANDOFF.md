# AdFeeder Website — Handoff Document

**Last updated:** 24 November 2026
**Owner:** Dave (Blanco Digital Ltd, trading as AdFeeder)
**Live site:** https://adfeeder.com (also `www.adfeeder.com` and `webdev.adfeeder.com`)
**Repo:** https://github.com/davedibbleblanco/adfeeder-site (branch `main`)

This file tells the next person (or Claude session) what the site is, how it gets
live, what state it's in, and what's left to do. Read sections 1–3 before you
change anything.

---

## 1. What it is

The AdFeeder marketing website: a set of static HTML pages selling AdFeeder's ad
automation to car dealers. It's plain HTML, CSS and a little JS, with one PHP file for
the contact form. There's no framework, no build step and no package manager.

Look and feel: dark navy background, coral accents, Poppins font. The design
tokens are CSS custom properties (`--navy`, `--coral`, `--coral-dim`, `--border-coral`,
`--radius-lg`, …) defined at the top of each page's `<style>` block.

**Every page is self-contained.** Each `.html` file carries its own full copy of the
`<style>` block, nav and footer. There are no shared CSS, JS or include files. So a
change to the nav, the footer or a shared style has to be made **in every page**.
Search and replace across `*.html` and check you hit all 12 or 13 files.

---

## 2. Pages

| File | Purpose |
|---|---|
| `index.html` | Home page: hero, stats, pillars, products |
| `vehicle-ads.html` | Vehicle Ads product page |
| `used-cars.html` | Used-car dealers landing page |
| `new-cars.html` | New-car dealers landing page |
| `commercial-vehicles.html` | Commercial vehicle dealers landing page |
| `different.html` | "What makes us different" (large file, about 650 KB, because it has embedded images) |
| `glossary.html` | Public PPC glossary (no login), added 25 Sep 2026 |
| `book-a-demo.html` | Demo booking page |
| `contact.html` | Contact form. Posts JSON to `contact.php` |
| `contact.php` | Form handler. Validates, uses a honeypot (`botcheck`), and emails **support@adfeeder.com** via PHP `mail()`. CORS allows only adfeeder.com, www and webdev |
| `privacy.html`, `cookies.html`, `terms.html` | Legal pages (see TODOs) |
| `book-a-demo-bonus.html` | January deck offer page, 20% for life of contract (see section 5) |

---

## 3. Hosting and deploy

```
local edit ──git push──▶ GitHub: davedibbleblanco/adfeeder-site (main, PUBLIC repo)
                                   │  root cron on the VM: git pull every 5 min
                                   ▼
                GCP VM "topx-picker" (35.242.151.115), nginx 1.24 on Ubuntu
                web root: /var/www/webdev.adfeeder.com
                                   │
                                   ▼
          https://adfeeder.com · https://www.adfeeder.com · https://webdev.adfeeder.com
```

- **To deploy, commit and push to `main`.** Changes go live within about 5 minutes. The
  cron line (as root) is:
  `*/5 * * * * cd /var/www/webdev.adfeeder.com && git pull origin main >> /var/log/webdev-pull.log 2>&1`
- The repo is **public on purpose** so the VM can pull without stored credentials.
  **Never commit secrets here.** Don't put a password, API key or SMTP credentials
  in `contact.php`.
- On 24 Nov 2026, adfeeder.com, www and webdev all served identical content, and that
  content matched this repo exactly (the only difference was line endings). All three
  hostnames are on the same VM.
- **Unknown paths on adfeeder.com are 301-redirected to blog.adfeeder.com.** For
  example, `/blog/` goes to `https://blog.adfeeder.com/blog/`. This is presumably there to
  keep old WordPress URLs working. It also means a new page that hasn't been pushed yet
  shows up as a redirect to the blog, not a 404.
- Same VM, other sites: the AdFeeder apps (apps, notes, topx, timetracker, …) and
  the WordPress blog.

### ⚠️ Don't edit files directly on the server
In September 2026 the live web root was found to have months of direct edits that were
never committed. Whole pages existed only on the server. They were brought back into the
repo in commit `4a22f7a`. The cron pull doesn't warn you about local edits on the
server, so they drift silently. **Always change the repo and push.** If you must hotfix
on the server, commit the same change back to the repo straight away. Before relying on
the repo, you can check for drift:
```bash
cd /var/www/webdev.adfeeder.com && sudo git status
```

### Server access
- SSH: Google Cloud Console → Compute Engine → VM instances → `topx-picker` → **SSH**
  (in the browser, no setup needed). Or from a machine with the gcloud CLI, logged in as
  dave@blancodigital.co.uk (GCP project `adfeeder-uploaders`), use `gcloud compute ssh topx-picker`.
- Pushing to GitHub: as the `davedibbleblanco` account. If you make a personal access
  token for a session, revoke it afterwards at https://github.com/settings/tokens.

### Not yet confirmed (check on the server)
- Which nginx config serves `adfeeder.com` and `www`. It may be the
  `webdev.adfeeder.com` config with extra `server_name`s, or a separate config pointing
  at the same root. Run `grep -rn "adfeeder.com" /etc/nginx/sites-enabled/`.
- Where the fallback redirect to blog.adfeeder.com is set (probably a
  `try_files … @blog` or `error_page 404` rule in that config).
- Whether PHP `mail()` actually delivers on this VM (is a local MTA or relay set up?). Send a test enquiry from
  `contact.html` and confirm it reaches support@adfeeder.com. If not, the form looks
  like it worked but nothing arrives. A 500 response from `contact.php` is
  the clue.

---

## 4. The blog (separate system)

`blog.adfeeder.com` is a **WordPress** install on the same VM, at
`/var/www/blog.adfeeder.com`. It isn't in this repo. Its look comes from a child
theme in a separate repo, https://github.com/davedibbleblanco/generatepress-child
(local folder `source/repos/generatepress-child`, with its own HANDOFF.md). That theme
copies this site's colours and fonts, so a brand change here should be repeated there.

The site nav and footer link to `https://blog.adfeeder.com`.

Known WordPress issue (Nov 2026): the admin asked for FTP credentials when updating plugins.
The fix is to have the files owned by the PHP user (`sudo chown -R www-data:www-data
/var/www/blog.adfeeder.com`) and add `define('FS_METHOD', 'direct');` to its
`wp-config.php`. Check whether that's been done before assuming it's fixed.

---

## 5. Work in progress and TODOs

**Live: `book-a-demo-bonus.html`** (shipped 25 Nov 2026)
- The page the Christmas present icon on the last slides of the January offer deck links to. It is a copy of
  `book-a-demo.html` with an offer panel: sign up before the end of December 2026 for 20% off for the life of the
  contract, plus free setup for sign-ups before 25 December. It is `noindex, nofollow` and not in the site nav.
- It books exactly like the normal demo page, so a booking from it is not tagged. Add tagging if sales need to know who is
  claiming the offer.
- After 31 December 2026 the offer has ended: update or retire the page and the deck link.

**Open items**
- ⚠️ **Legal pages not reviewed by a solicitor.** `privacy.html`, `cookies.html` and
  `terms.html` started as generic UK GDPR/PECR boilerplate. Treat them as drafts
  until they're reviewed.
- **No analytics tag found** on the live home page (no GA4 or GTM). If tracking is wanted,
  it must be added to every page (see section 1). The cookie policy and a consent banner may
  then need updating.
- Shared markup is duplicated across every page. If the site grows, consider moving the
  CSS into one `styles.css` and loading the nav and footer from one place. This isn't
  urgent, but it's the main source of slow, error-prone edits.

---

## 6. Recent history

| Date | Change |
|---|---|
| 23 Jul 2026 | Initial upload, legal pages, SVG pillar icons |
| 3 Sep 2026 | `4a22f7a` repo re-synced with the live server (product, landing, contact and demo pages); `9950032` mobile CSS grid overflow fix on hero and stat boxes |
| 25 Sep 2026 | `a6f23cb` public PPC glossary page |
| 6 Nov 2026 | `book-a-demo-bonus.html` drafted (uncommitted) |
| 25 Nov 2026 | `book-a-demo-bonus.html` finished (20% for life offer) and published |

## 7. Handy commands (on the VM)

```bash
cat /var/log/webdev-pull.log                        # auto-pull log
sudo crontab -l                                     # see the pull job
cd /var/www/webdev.adfeeder.com && sudo git pull origin main   # deploy now
sudo nginx -t && sudo systemctl reload nginx        # after nginx config edits
sudo certbot renew --dry-run                        # SSL auto-renews; this just checks
```

## 8. Company details (used in footer and legal pages)

- **Legal name:** Blanco Digital Ltd, trading as AdFeeder
- **Company number:** 09675276 (England & Wales) · **VAT:** 219 6788 61
- **Registered office:** 31 The Balcony, Castle Arcade Quarter, Cardiff, CF10 1BY
- **Phone:** 02922 749355 · **Email:** contact@adfeeder.com (form enquiries go to support@adfeeder.com)
