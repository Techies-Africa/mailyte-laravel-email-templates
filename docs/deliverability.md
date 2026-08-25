# Deliverability

## What this package can and cannot do for you

Being honest about this matters, because the split is lopsided.

Inbox placement is decided mostly by **who is sending**, not by what the message
looks like. Authentication, domain reputation, complaint rate, list hygiene and
engagement dominate. A beautifully built message from an unauthenticated domain
goes to spam; a plain-text message from a warm, authenticated domain with happy
recipients arrives.

What is left — and it is worth having — is the part a filter reads off the
message itself. That is what this package checks.

| Decided by | Who controls it | Checked here |
|---|---|---|
| SPF, DKIM, DMARC alignment | Your DNS | No |
| Sending domain and IP reputation | Your sending history | No |
| Complaint and bounce rate | Your list and your practices | No |
| Recipient engagement | Your content and cadence | No |
| One-click unsubscribe working | Your app, wired to `List-Unsubscribe` | Header presence only |
| Message weight and Gmail clipping | The template | **Yes** |
| Text-to-image balance, alt text | The template | **Yes** |
| Link shape, schemes, shorteners | The template and your data | **Yes** |
| Phrase heuristics, shouting subjects | The copy | **Yes** |
| Markup clients strip or block | The template | **Yes** |
| Rendering in Outlook, Gmail, Apple Mail | The template | Partly — see below |

**If your mail is going to spam, start with authentication, not the template.**
Fix SPF, DKIM and DMARC first. Then look here.

---

## Checking the message

```bash
php artisan mailyte:deliverability                    # every template, every layout
php artisan mailyte:deliverability invoice receipt    # just these
php artisan mailyte:deliverability --layout=branded --strict
```

It renders each message and audits what a filter would see. `--strict` fails on
warnings too, which is what CI runs.

### The rules

| Rule | What it catches | Why it matters |
|---|---|---|
| `MT050` | HTML past Gmail's ~102KB clip threshold | Gmail hides everything after the cut behind "View entire message" — including the footer, the postal address and the unsubscribe link |
| `MT051` | Too little visible text | A filter with nothing to classify falls back on reputation, which is a worse bet |
| `MT052` | Missing or token-thin plain-text part | A single-part HTML message is one of the oldest spam signals there is |
| `MT053` | Too many links for the amount of text | A high link-to-text ratio is the shape of bulk mail |
| `MT054` | A link labelled with one domain pointing at another | The shape of a phishing link, and scored as one regardless of intent |
| `MT055` | `http://` links | Mixed-scheme mail is penalised, and the link may be rewritten or stripped |
| `MT056` | Link shorteners | They hide the destination, which is exactly why filters distrust them |
| `MT057` | Images with no `alt` | Images are off by default in Outlook and for many Gmail users; without alt text that content is simply gone |
| `MT058` | Image-heavy with little to read | With images blocked it arrives as a stack of empty rectangles. Icons, spacers and the hidden half of a light/dark pair are not counted |
| `MT059` | Trigger phrases stacked into short copy | One "no obligation" in a page of copy is nothing; six in a paragraph is the pattern filters score |
| `MT060` | Shouting subject lines | `ALL CAPS`, `!!!` and `$$` are scored on their own |
| `MT061` | `<script>`, `<iframe>`, `<form>`, event handlers | Every major client strips these, and their presence raises the score on the way past |
| `MT062` | Empty subject, missing preheader | The subject is not optional; the preheader decides what the inbox preview says |
| `MT063` | Bulk mail with no unsubscribe route in the rendered output | Required by CAN-SPAM and by Gmail's and Yahoo's bulk sender rules |

A template that genuinely does not need a rule waives it in its own manifest,
with a written reason:

```json
"lint": {
  "ignore": [
    { "rule": "MT053", "reason": "A digest is a list of links; that is the format." }
  ]
}
```

Thresholds live in `config('mailyte.lint.rules')`.

---

## Getting a real score

The audit above cannot score authentication, because a file on disk has no
envelope, no DKIM signature and no sending IP. For that you need the message
somewhere that can see all three.

### Export the message

```bash
php artisan mailyte:deliverability invoice --layout=branded --eml=storage/eml
```

Writes an RFC 5322 file per render, with the rendered `List-Unsubscribe` headers
in place. Feed one to:

- **[mail-tester.com](https://www.mail-tester.com)** — send to the address it
  gives you and get a SpamAssassin score plus SPF/DKIM/DMARC results for your
  actual domain. The single most useful ten seconds available. Free, 3 checks a
  day.
- **`spamassassin -t < message.eml`** — the classic rule engine, locally. On
  Debian and Ubuntu: `apt install spamassassin && sa-update`. Scores the content
  rules; the authentication rules need a real received message.
- **Any mail client** — open the `.eml` in Outlook, Apple Mail or Thunderbird to
  see the real rendering, images off included.

### Send a real one

```bash
php artisan mailyte:send-test invoice --to=you@example.com
```

Goes through your configured mailer, so it exercises your actual authentication.
Send to a Gmail address and check **Show original** — you want `SPF: PASS`,
`DKIM: PASS`, `DMARC: PASS`. Anything else is the thing to fix.

### Seed-list and rendering services

Worth the money once you are sending at volume, and none of it is something this
package can replace:

- **[Litmus](https://litmus.com)** or **[Email on Acid](https://www.emailonacid.com)** —
  screenshots in 90+ real clients, plus spam-filter testing. This is the only
  honest way to verify Outlook 2016-2019 rendering, which no headless browser
  reproduces.
- **[GlockApps](https://glockapps.com)** or **[Inbox Monster](https://inboxmonster.com)** —
  seed-list inbox placement across providers.
- **[Google Postmaster Tools](https://postmaster.google.com)** and
  **[Microsoft SNDS](https://sendersupport.olc.protection.outlook.com/snds/)** —
  free, and the only view of your own reputation at Gmail and Outlook. Set both
  up on day one.

---

## Authentication, in the order that matters

The package cannot do any of this for you, and none of it is optional.

1. **SPF** — a TXT record on your sending domain listing who may send for it.
2. **DKIM** — your mail provider gives you a key; publish it and sign every
   message. Non-negotiable for Gmail and Yahoo bulk senders.
3. **DMARC** — start at `p=none` with `rua=` reporting so you can see what is
   failing, then move to `quarantine` and `reject` once it is clean.
4. **A subdomain for bulk mail** — `mail.example.com` or `news.example.com`, so
   a marketing complaint rate cannot damage the reputation your password resets
   depend on.
5. **One-click unsubscribe** — Gmail and Yahoo require it of anyone sending over
   5,000 messages a day. Every marketing template here declares the
   `List-Unsubscribe` and `List-Unsubscribe-Post` headers; your application has
   to honour the POST it produces, and stop sending within two days.
6. **Warm up gradually** — a new domain that sends 50,000 messages on its first
   day is indistinguishable from a compromised one.

---

## What is checked, and what only a real client can tell you

The catalog is verified against headless Chrome: 588 renders at 320/375/480/600px
with zero overflow, WCAG AA contrast in both colour schemes, and every template
in every layout it declares. That covers Gmail, Apple Mail, iOS Mail and the
modern webmail clients, which are all standards-based renderers.

**Outlook 2016–2019 on Windows is not covered by that**, because it renders with
Word's engine and no browser reproduces it. The templates are built for it —
table layouts, MSO conditional comments, VML behind background images, no
flexbox or grid in the message body — but "built for it" is not "verified in
it". If your audience is enterprise Windows, spend the Litmus subscription.

---

[← Back to the README](../README.md) · [Sending](sending.md) · [Theming](theming.md)
