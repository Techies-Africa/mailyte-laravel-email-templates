# Catalog plan — the remaining 27

23 of 50 are built. This is what the rest should be, why each earns a place, and
the design register it gets so no two templates in the catalog look alike.

Two rules carried over from the built set:

- **A template earns its place by contract, not by styling.** `verify-email-link`
  exists because a link-only flow cannot complete on a second device, not because
  it looks different. Where the difference is only visual, it ships as a
  `variant_of` instead of a new slug.
- **Register follows purpose.** A deletion notice and a promotional offer should
  not share a design language, and neither should two consecutive templates in
  the same category.

## Account (4 → 9 total)

| Slug | Why it exists | Register |
|---|---|---|
| `password-reset` | The most-sent account email there is. Time-boxed, single action, no decoration | Stark utility — near-plaintext, one button, maximum deliverability |
| `password-changed` | The after-the-fact record. Its job is to be believable and to offer a fast "this wasn't me" | Audit line — timestamped, monospaced metadata |
| `email-changed` | Goes to both the old and new address, and has to make the change obvious | Before / after — the two addresses set as a comparison |
| `account-deleted` | Deletion scheduled, with the recovery window stated exactly | Wind-down — sombre, serif, the recovery date as the largest element |

## Security (4 → 5 total)

| Slug | Why it exists | Register |
|---|---|---|
| `suspicious-activity` | A blocked attempt, not a successful one. Different message from `new-device-login` | Red alert — full-bleed severity band |
| `two-factor-enabled` | Confirms a security control changed, and how to undo it | Shield — teal, checklist of what changed |
| `api-key-created` | Developer credential created. Shows a prefix, never the secret | Developer console — dark, monospaced, key prefix in a code panel |
| `password-breach` | Credential found in a third-party breach. Advisory, not accusatory | Advisory — amber, numbered remediation steps |

## Billing (4 → 9 total)

| Slug | Why it exists | Register |
|---|---|---|
| `card-expiring` | A quiet nudge before anything breaks. Must not read as an alarm | Wallet — the card rendered as a panel, brand and last four |
| `subscription-renewing` | The plan-expiring notice: what renews, when, at what price | Calendar notice — date-forward, price stated once |
| `subscription-cancelled` | Confirms cancellation and what is kept versus lost | Wind-down ledger — a keep/lose table, no persuasion |
| `refund-issued` | Money going back, and when it lands | Credit note — sibling to the invoice ledger, figures reversed |

## Notifications (5 → 5 total)

The generic ones. Most products send far more of these than anything else.

| Slug | Why it exists | Register |
|---|---|---|
| `notification` | The plain single-event notification every product needs and few design | Plain notification — one line, one action, nothing else |
| `mention` | Someone named you. The quoted context is the whole value | Conversation — quoted snippet with author |
| `comment-reply` | A reply in a thread, with the parent shown for context | Thread — nested quotes, indented |
| `task-assigned` | Work assigned, with due date and priority | Work item — metadata table, priority chip |
| `weekly-digest` | Batched activity, so the per-event emails can be turned off | Dashboard digest — stat row over grouped lists |

## System and status (4 → 5 total)

| Slug | Why it exists | Register |
|---|---|---|
| `status-update` | The generic progress update: a long job, a review, a request moving on | Progress — status banner over a timeline |
| `maintenance-scheduled` | Planned downtime, in the reader's timezone | Notice board — dark band, window stated in a table |
| `incident-notice` | Something is broken now. Severity first, updates promised | Incident — severity-led, live status link |
| `incident-resolved` | Resolution and cause. The email that decides whether trust returns | Postmortem — timeline, what changed, no euphemism |

## Collaboration (4 → 4 total)

| Slug | Why it exists | Register |
|---|---|---|
| `team-invitation` | Someone is invited into a workspace. Highest-conversion email a team product sends | Invitation card — inviter, workspace, one accept action |
| `invite-accepted` | Tells the inviter it worked, and who joined | Roster — compact, who and when |
| `role-changed` | Permissions moved. Has to state what they can and cannot do now | Permissions diff — before and after |
| `seat-limit-reached` | Blocked from adding someone, with the two ways out | Capacity gauge — usage bar over upgrade and remove |

## Marketing (1 → 3 total)

| Slug | Why it exists | Register |
|---|---|---|
| `re-engagement` | Dormant account. Must lead with what changed, not with guilt | What you missed — editorial, product changes since they left |

## Events (1 → 1 total)

| Slug | Why it exists | Register |
|---|---|---|
| `event-invite` | Webinar or event with RSVP, add-to-calendar and a replay promise | Ticket — date block, venue line, RSVP |

## Resulting shape at 50

| Category | Count |
|---|---|
| account | 9 |
| billing | 9 |
| onboarding | 8 |
| notifications | 5 |
| security | 5 |
| system | 5 |
| collaboration | 4 |
| marketing | 3 |
| newsletter | 1 |
| events | 1 |

## Sequencing

Six batches of four to five, each verified before the next: render, overflow
(every layout × 320/375/480/600), contrast in both schemes, footer alignment.

1. Account — the four above, since password reset is the highest-volume gap
2. Security — pairs naturally with account, shares the restraint
3. Notifications — the generic set, most reused by real products
4. Billing — completes the money set
5. System and status — incident pair matters most
6. Collaboration, marketing, events — the remainder

## Deliberately not in the 50

Seasonal (Black Friday, holidays), industry-specific (restaurant booking,
shipping tracking) and channel-specific (SMS fallback, push digest) templates.
They are real categories, but a general-purpose catalog that ships them is
guessing at a business it cannot see. They belong in a community tier.
