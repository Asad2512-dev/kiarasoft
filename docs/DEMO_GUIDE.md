# Demo Guide (FYP presentation)

Seed: `php artisan migrate:fresh --seed` (≈15 s). All data is fictional and labelled “(Demo)”. Password for every account = `KIRASOFT_DEMO_PASSWORD` from `.env`.

| Role | E-mail | Lands on |
|---|---|---|
| Super Admin | admin@kirasoft.test | Platform overview |
| Shop Admin | owner@kirasoft.test | Dashboard (all 3 branches) |
| Branch Manager | manager@kirasoft.test | Dashboard (G-11 + Saddar) |
| Cashier | cashier@kirasoft.test | POS (G-11 Markaz) |
| Cashiers (other branches) | cashier2@ / cashier3@kirasoft.test | POS (Saddar / Bahria) |

Data: shop *Al-Noor Kiryana Store* (Islamabad/Rawalpindi), 3 branches, 10 categories, 57 Pakistani grocery products (rice, atta, sugar, tea, oil, ghee, milk, daal, masala, biscuits, cleaning), opening stock, ~650 invoices over 30 days, supplier deliveries, 5 returns (partial, full, not-restocked), low/out-of-stock items, a second shop (*Karachi Fresh Mart*) for isolation, and 2 pending signup requests.

## Suggested 10-minute walkthrough
1. **Cashier** → POS. Press **F2**, type `ATTA`, Enter; scan-style entry: type `MAIDA-L` + Enter; set Maida qty to `1.5` (decimal kg). Add 5% discount. **F9** → quick-cash button → **Enter**. Show change, **F8** print the 80 mm receipt. Try selling more than available stock → blocked.
2. Cashier → *My sales*: only their own invoices. Try `/dashboard` → 403.
3. **Manager** → Dashboard (real figures), *Stock alerts* (bell), *Stock adjustment*: receive stock, preview before/after; *Stock movements* shows the ledger incl. the sale just made.
4. Manager → open the cashier's invoice → *Process return* 1 unit → refund computed with discount; stock restored; invoice “Partially returned”.
5. **Shop Admin** → *Reports*: this month, per branch/cashier, chart, CSV export, print. Point out the metric definitions. *Inventory → Reconcile* shows all lines match.
6. Shop Admin → *Employees*: create a cashier for Bahria, reset password (shown once), deactivate. *Branches*: assign manager. *Settings*: change receipt footer/tax and re-print.
7. **Super Admin** → approve “Lahore Super Store” signup (creates shop + admin), suspend a shop, *Backups* → back up → *Test restore*, *System health*.
8. Resize the browser to phone width: bottom-sheet cart on POS, tables become cards.
