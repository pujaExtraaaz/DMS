# SRS §29 Acceptance Criteria — UAT Checklist

Generated: 2026-09-08  
Product: Distributor Management System (Hybrid fulfilment)

Status key: **Pass** = implemented end-to-end · **Partial** = present with stubs/limits · **Fail** = not implemented

| # | Criterion | Result | Notes |
|---|---|---|---|
| 1 | Due dates use configured Receive/Invoice date basis | Partial | `due_date`, `due_date_basis`, `credit_days` on invoices; party defaults exist |
| 2 | Item master: specification, MRP, discount, warranty, purchase price, image, catalog | Partial | Fields on products; image upload UI not fully wired |
| 3 | Purchase invoice vendor price comparison | Pass | Purchase invoice module + vendor price histories |
| 4 | Sister concerns linkable without merging ledgers | Pass | Business groups + companies |
| 5 | Pending order report reconciles ordered/reserved/delivered/pending | Pass | `reports.pending-orders` |
| 6 | Freight Bill Number → item landed cost | Pass | Freight bills + landed cost allocation |
| 7 | Security cheques for vendors and clients | Pass | Cheques module with purpose/direction |
| 8 | Invoice communication WhatsApp/Email/Notification tracking | Partial | Logs with queued/sent/failed; providers are stubs |
| 9 | Dashboard date filtering | Pass | Today/Yesterday/Week/Month/Quarter/Year/Custom |
| 10 | FIFO and LIFO valuation | Partial | `stock_valuation_settings` table; engine not fully applied to all cost layers |
| 11 | Salesman targets and salesman-wise outstanding | Pass | Targets module + salesman outstanding report |
| 12 | Region-wise brand restrictions with override | Pass | Region brand policies + order validation |
| 13 | Unavailable-stock sales as authorized back-order | Pass | Back-order flag without inflating stock |
| 14 | Stock movement traceable to source document | Pass | `stock_movements` morph reference + warehouse |
| 15 | Invoice traceable to party/item/price/stock/expenses | Partial | Party/item/price yes; deal expenses optional |
| 16 | Credit limit blocking before credit-sale approval | Pass | CreditCheckService on approve |
| 17 | Serial/batch unique through lifecycle | Partial | Serial/batch masters + duplicate block; full lifecycle UI limited |
| 18 | Outstanding reconciles invoices, payments, credit notes | Pass | Ledger + credit notes |
| 19 | Cheque bounce threshold auto risk action | Pass | Bounce > 3 freezes party |
| 20 | Interest uses stored credit days/rate/overdue balance | Pass | Interest preview/post engine |
| 21 | Targets and schemes from posted eligible sales | Pass | Target + scheme modules |
| 22 | Critical actions auditable | Pass | `activity_logs` + `approval_logs` |
| 23 | Reports filter date/branch/party/supplier/brand/category/item/salesperson | Partial | Date/party/salesperson/product filters; not all dimensions everywhere |

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

1. Login as super-admin; open Organization → Companies/Branches/Warehouses/FY.
2. Create Brand → Category → Sub-category → Product (serial tracking).
3. Create Party with credit limit; create PO → approve → inward → PI → landed cost.
4. Create warehouse-mode SO; confirm credit block when over limit; approve with reservation.
5. Create van-mode SO → convert invoice → load sheet → delivery → settlement.
6. Record cheque bounce thrice+; confirm party frozen.
7. Run interest preview; open MIS reports with date filters.
8. Create HRMS employee + leave; create Meta lead (or webhook); view Tally queue.
