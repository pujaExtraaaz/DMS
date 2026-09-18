# SRS §29 Acceptance Criteria — UAT Checklist

Generated: 2026-09-08 · Updated: 2026-09-10  
Product: Distributor Management System (Hybrid fulfilment)

Status key: **Pass** = implemented end-to-end · **Partial** = present with live-credential dependency · **Fail** = not implemented

| # | Criterion | Result | Notes |
|---|---|---|---|
| 1 | Due dates use configured Receive/Invoice date basis | Pass | Company `due_date_basis`; applied on sales invoice, order conversion, purchase invoice |
| 2 | Item master: specification, MRP, discount, warranty, purchase price, image, catalog | Pass | Product form fields + multipart image upload to `storage/products` |
| 3 | Purchase invoice vendor price comparison | Pass | Purchase invoice module + vendor price histories |
| 4 | Sister concerns linkable without merging ledgers | Pass | Business groups + companies |
| 5 | Pending order report reconciles ordered/reserved/delivered/pending | Pass | `reports.pending-orders` with SRS filters |
| 6 | Freight Bill Number → item landed cost | Pass | Freight bills + landed cost allocation |
| 7 | Security cheques for vendors and clients | Pass | Cheques module with purpose/direction |
| 8 | Invoice communication WhatsApp/Email/Notification tracking | Partial | Adapters call Meta WhatsApp Cloud API / Mail / DB notifications; without `WHATSAPP_*` credentials WhatsApp logs fallback |
| 9 | Dashboard date filtering | Pass | Today/Yesterday/Week/Month/Quarter/Year/Custom |
| 10 | FIFO and LIFO valuation | Pass | Cost layers + consumptions on stock IN/OUT; Inventory → FIFO/LIFO settings UI |
| 11 | Salesman targets and salesman-wise outstanding | Pass | Targets module + salesman outstanding report |
| 12 | Region-wise brand restrictions with override | Pass | Region brand policies + order validation |
| 13 | Unavailable-stock sales as authorized back-order | Pass | Back-order flag without inflating stock |
| 14 | Stock movement traceable to source document | Pass | `stock_movements` morph reference + warehouse |
| 15 | Invoice traceable to party/item/price/stock/expenses | Pass | Party/item/price + deal expenses on margin report |
| 16 | Credit limit blocking before credit-sale approval | Pass | CreditCheckService on approve |
| 17 | Serial/batch unique through lifecycle | Pass | Duplicate block + reserve / deliver / return UI (`inventory.serials`) |
| 18 | Outstanding reconciles invoices, payments, credit notes | Pass | Ledger + credit notes |
| 19 | Cheque bounce threshold auto risk action | Pass | Bounce > 3 freezes party |
| 20 | Interest uses stored credit days/rate/overdue balance | Pass | Interest preview/post engine |
| 21 | Targets and schemes from posted eligible sales | Pass | Target + scheme modules |
| 22 | Critical actions auditable | Pass | `activity_logs` + `approval_logs` |
| 23 | Reports filter date/branch/party/supplier/brand/category/item/salesperson | Pass | Shared filter set applied across MIS reports |

## Still credential-dependent (not code gaps)

- **WhatsApp:** set `WHATSAPP_TOKEN` + `WHATSAPP_PHONE_NUMBER_ID` (or Meta access token)
- **Meta CRM Graph fetch:** set `META_ACCESS_TOKEN` (+ verify/webhook secrets)
- **Tally live push:** set `TALLY_ENDPOINT` (+ optional `TALLY_COMPANY`); without it queue items are `skipped`

## Hybrid fulfilment decision

- **Van path:** Invoice → Load sheet → Delivery → Settlement (kept)
- **Warehouse path:** Approve → Reserve → Deliver → Invoice (added for tracked/warehouse mode)

## Phase coverage

| Phase | Scope | Status |
|---|---|---|
| 1 | Org / catalog / party | Done |
| 2 | Purchase / inventory engine | Done |
| 3 | Sales hybrid | Done |
| 4 | Receivables / cheques / CN | Done |
| 5 | Deal expenses / margin | Done |
| 6 | Targets / schemes | Done |
| 7 | Interest / schedulers | Done |
| 8 | Dashboard / MIS | Done |
| 9 | HRMS | Done |
| 10 | Meta CRM | Done |
| 11 | Tally queue | Done |
| 12 | Hardening / UAT | Done |

## Manual UAT smoke steps

1. Login as super-admin; open Organization → Companies (set Due Date Basis) / Branches / Warehouses / FY.
2. Create Brand → Category → Sub-category → Product with image + serial tracking.
3. Create Party with credit limit; create PO → approve → inward → PI → landed cost.
4. Open Inventory → FIFO/LIFO; confirm method; post stock and verify cost layers.
5. Create warehouse-mode SO; confirm credit block when over limit; approve with reservation.
6. Use Serial Lifecycle: reserve → deliver → return a serial.
7. Create van-mode SO → convert invoice → load sheet → delivery → settlement.
8. Send invoice WhatsApp/Email from communication actions; confirm communication_logs status.
9. Record cheque bounce thrice+; confirm party frozen.
10. Run interest preview; open MIS reports with branch/brand/category/salesperson filters.
11. Create HRMS employee + leave; create Meta lead (or webhook); view Tally queue.

## Automated product smoke (2026-09-10)

- Client login: `client@extraaaz.com`
- On-screen help banner injected on authenticated DMS screens (`x-ui.screen-help` + `config/screen_help.php`)
- Sidebar regrouped as numbered business flow (Setup → Buy → Inventory → Sell → Van → Collect → Performance → HR/CRM → Reports → Help)
- Live HTTP smoke as client: core index/create/report routes returned 200 with help banner present
- Remaining go-live deps: WhatsApp / Meta / Tally credentials in `.env`
