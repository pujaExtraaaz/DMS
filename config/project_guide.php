<?php

/**
 * In-app Guide — source of truth for /guide.
 * Structure: modules → sections → steps (plain strings).
 * Optional section keys: route (named route for “Open” link).
 */
return [
    'modules' => [
        [
            'number' => 1,
            'title' => 'Setup',
            'purpose' => 'Create the legal company, operating places, open financial year, and document numbers before anyone posts a transaction.',
            'sections' => [
                [
                    'title' => 'Company Profile',
                    'route' => 'organization.company-profile',
                    'summary' => 'The profile is the legal identity printed on invoices, purchase orders, and Books vouchers.',
                    'steps' => [
                        'Open Setup → Company Profile and enter the legal name, trade name, GSTIN, PAN, and registered address.',
                        'Add the bank account that receipts and payments will use, and set the invoice due-date basis.',
                        'Save before creating branches. A blank GSTIN or address prints as a blank tax block on every document.',
                    ],
                ],
                [
                    'title' => 'Companies, branches & warehouses',
                    'route' => 'organization.companies.index',
                    'summary' => 'A company owns the books. A branch is an operating location. A warehouse is where quantity sits.',
                    'steps' => [
                        'Add each legal company under Setup → Companies. Sister concerns stay separate companies; they are grouped only for reporting.',
                        'Add a branch for every location that raises invoices or holds stock. Enter the name and details — branch code is assigned automatically.',
                        'Add a warehouse under that branch. In Accounting Books, pick the working company, branch, and financial year in the top bar; reports follow that selection.',
                    ],
                ],
                [
                    'title' => 'Financial year',
                    'route' => 'organization.financial-years.index',
                    'summary' => 'Every voucher and invoice date must fall inside an open year.',
                    'steps' => [
                        'Create the year with its start and end dates, then mark one year as current.',
                        'Post new sales, purchases, receipts, and payments only into the open year.',
                        'Close a year only after trial balance, stock, and bank are agreed. A closed year rejects new postings.',
                    ],
                ],
                [
                    'title' => 'Voucher numbering & sister concerns',
                    'route' => 'organization.document-number-series.index',
                    'summary' => 'Number series keep invoice and voucher numbers unique. Sister concerns share group reporting without mixing books.',
                    'steps' => [
                        'Open Setup → Voucher Numbering and define series for invoices, orders, receipts, service tickets (SRV / AVITSRV), and other documents you use.',
                        'Use Sister Concerns when related companies need a combined Group Dashboard without sharing one ledger.',
                        'Confirm numbering and company structure before the first live posting — changing series mid-year creates gaps that auditors notice.',
                    ],
                ],
            ],
        ],
        [
            'number' => 2,
            'title' => 'Masters',
            'purpose' => 'Build the catalog, units, prices, and party list that every purchase, stock movement, and sale will use.',
            'sections' => [
                [
                    'title' => 'Brands, categories & product types',
                    'route' => 'masters.brands.index',
                    'summary' => 'Classification comes before the product. Product type is also where serial tracking is switched on.',
                    'steps' => [
                        'Create Brands, then Categories under a brand, then Sub Categories under a category.',
                        'Open Product Types and tick Track serial numbers for types that must be serialized. The list shows Serial numbers under that type.',
                        'Use Find a product type to walk brand → category → sub-category until the matching type is left.',
                    ],
                ],
                [
                    'title' => 'Products, UOM & price list',
                    'route' => 'masters.products.index',
                    'summary' => 'A product cannot be purchased or sold until it exists with a unit, tax rate, and price.',
                    'steps' => [
                        'Create the product with HSN, tax, base UOM, and prices. Set Product Type to the type that should control serials.',
                        'Saving a product whose type tracks serials turns serial tracking on for that product. Purchase invoices then require a serial per unit.',
                        'Maintain the Price List used on orders (trade / MRP). Inclusive and exclusive GST rates come from that list. After a Tally import, use price backfill if rates are still blank.',
                    ],
                ],
                [
                    'title' => 'Parties, phone directory & bulk import',
                    'route' => 'masters.customers.index',
                    'summary' => 'Customers and suppliers are the same party master, marked by party type.',
                    'steps' => [
                        'Create the party and mark it customer, supplier, or both. GSTIN and addresses print on documents.',
                        'Add more than one contact when purchasing or sales deals with different people. Use Phone Directory for quick lookup.',
                        'Use Bulk Import when the catalog or party list is already in a spreadsheet. Review imported rows before the first invoice.',
                    ],
                ],
            ],
        ],
        [
            'number' => 3,
            'title' => 'Purchases',
            'purpose' => 'Compare quotes, raise orders, receive goods, book the supplier bill, and land freight — in that order when possible.',
            'sections' => [
                [
                    'title' => 'Comparison → purchase order',
                    'route' => 'purchasing.orders.index',
                    'summary' => 'The order is the commitment. It does not move stock until goods are received or a purchase invoice is posted.',
                    'steps' => [
                        'Optional: open Material Comparison to line up vendor quotes and landed cost, then convert the chosen quote into purchase orders.',
                        'Create a purchase order, search the supplier by name, and add warehouse, items, rates, and tax.',
                        'Set freight as a percentage of the pre-GST amount or as a fixed rupee amount. Print the order; Order Notes at the top of the print save back onto the order.',
                    ],
                ],
                [
                    'title' => 'Receipt notes & goods receipts',
                    'route' => 'purchasing.inwards.index',
                    'summary' => 'Record what arrived against a purchase order before or beside the supplier bill.',
                    'steps' => [
                        'Create a Receipt Note or Goods Receipt against the purchase order and warehouse; enter received and accepted quantities, then post.',
                        'Posting updates stock for accepted quantity. The purchase invoice remains the payable document.',
                        'When Tally sync is on, a posted inward queues as a Tally Receipt Note (stock). The purchase invoice still queues as Purchase.',
                    ],
                ],
                [
                    'title' => 'Purchase invoices, landed cost & freight',
                    'route' => 'purchasing.invoices.index',
                    'summary' => 'The supplier bill is what Books, stock (if not already inwarded), and payables read.',
                    'steps' => [
                        'Create the invoice against the supplier and, when you have one, the purchase order. Put delivery instructions in Remarks.',
                        'Type a serial on every line whose product type tracks serials. The invoice will not save while a required serial is blank. Then post.',
                        'Record Landed Costs and Freight Bills when transport or extras are billed separately. Posted freight can create a Books landed-cost journal for Tally.',
                    ],
                ],
            ],
        ],
        [
            'number' => 4,
            'title' => 'Inventory',
            'purpose' => 'See what is on hand, move it between warehouses, correct quantity, and value stock — without creating a sale or purchase.',
            'sections' => [
                [
                    'title' => 'Current stock & stock register',
                    'route' => 'inventory.stock.index',
                    'summary' => 'Quantity here is what posted purchases, sales, transfers, and adjustments have already done.',
                    'steps' => [
                        'Check Current Stock before promising a sale or after posting a purchase. Filter by warehouse when the same product sits in more than one godown.',
                        'Open Stock Register for movement history on a product. A draft invoice does not change quantity — post it first, then refresh.',
                        'Use Reports → Stock Report / Stock Ledger for printable or exportable views of the same numbers.',
                    ],
                ],
                [
                    'title' => 'Transfers, adjustments & valuation',
                    'route' => 'inventory.transfers.index',
                    'summary' => 'A transfer moves quantity between warehouses. An adjustment changes quantity. Valuation shows value, not only qty.',
                    'steps' => [
                        'Use Stock Transfer to move quantity from one warehouse to another. Company-wide stock does not change. Posted transfers sync to Books as stock journals.',
                        'Use Adjustments for damage, shortage, or found stock, and post so Books and Tally receive a stock journal.',
                        'Open Stock Valuation when you need the value of stock on hand (rates from product / Books openings).',
                    ],
                ],
                [
                    'title' => 'Serial numbers',
                    'route' => 'inventory.serials.index',
                    'summary' => 'Serials are collected on the purchase invoice for product types that track them.',
                    'steps' => [
                        'Turn Track serial numbers on at the product type, then assign that type on the product.',
                        'Enter one serial per unit on the purchase invoice. Blank serials on a tracked line are rejected.',
                        'Look up a serial later from Inventory → Serial Numbers when you need to see where that unit is.',
                    ],
                ],
            ],
        ],
        [
            'number' => 5,
            'title' => 'Sales & Orders',
            'purpose' => 'Turn a customer requirement into a quotation, a booked order, then a tax invoice that moves stock and receivables.',
            'sections' => [
                [
                    'title' => 'Quotations → orders',
                    'route' => 'quotations.index',
                    'summary' => 'A quotation is an offer. An order is the commercial commitment. Neither posts stock by itself.',
                    'steps' => [
                        'Create a quotation while rate or quantity is not confirmed. Mark it sent or accepted when you want it on the Tally queue (Quotation voucher).',
                        'Create an order with customer, warehouse, products, and prices once they confirm. Approving the order queues a Tally Sales Order when sync is on.',
                        'Use Region Policies only when a brand may be sold in limited territories. Review Quotation Register under Reports for period exports.',
                    ],
                ],
                [
                    'title' => 'Tax invoices',
                    'route' => 'invoices.index',
                    'summary' => 'The tax invoice is the sales document that Books, receivables, and stock read.',
                    'steps' => [
                        'Create the tax invoice from the order, or create an invoice directly when there is no order (Direct Billing from the dashboard).',
                        'Print it, then open E-Invoice and E-Way Bill where required. Sales documents use the sales-invoice layout.',
                        'Posting reduces stock and raises the customer balance. The Books dashboard includes branch invoices for the selected as-on date.',
                    ],
                ],
                [
                    'title' => 'Print options',
                    'route' => 'invoices.index',
                    'summary' => 'The print screen remembers which optional fields you want on the page.',
                    'steps' => [
                        'Open Print options on the right. The field list opens and closes in place.',
                        'Untick batch, warehouse, HSN, or any other optional field. Number, party, item, quantity, rate, and total stay.',
                        'Order notes and narration print in the highlighted bar at the top when that field is ticked and the text is not empty.',
                    ],
                ],
            ],
        ],
        [
            'number' => 6,
            'title' => 'Logistics & Trips',
            'purpose' => 'Move invoiced goods on a vehicle and close the trip with expenses and cash collected.',
            'sections' => [
                [
                    'title' => 'Trip sheets / load',
                    'route' => 'logistics.load-sheets.index',
                    'summary' => 'A load groups the invoices that will travel on one vehicle.',
                    'steps' => [
                        'Build the trip sheet from invoices that are already posted.',
                        'Confirm the vehicle, driver, and the invoices included before the vehicle leaves.',
                        'Do not add a draft invoice to a load. Post it first so the quantity is real.',
                    ],
                ],
                [
                    'title' => 'Delivery runs',
                    'route' => 'deliveries.index',
                    'summary' => 'The run is the record of what was handed over at each stop.',
                    'steps' => [
                        'Create a delivery run from the load and mark each stop as it is completed.',
                        'Record a failed delivery on that stop instead of deleting the invoice.',
                        'Leave the run open until every stop has a result. Use Reports → Delivery Report for period exports.',
                    ],
                ],
                [
                    'title' => 'Trip settlements',
                    'route' => 'settlements.index',
                    'summary' => 'Settlement closes the cash and expenses for the trip.',
                    'steps' => [
                        'Open Trip Settlements after the vehicle returns.',
                        'Enter expenses, cash collected, and the closing balance.',
                        'Settle only after the invoices on that load are posted. Settlement does not rewrite those invoices.',
                    ],
                ],
            ],
        ],
        [
            'number' => 7,
            'title' => 'Receivables',
            'purpose' => 'Collect money against sales invoices, track cheques, issue credit notes, and keep the customer balance correct.',
            'sections' => [
                [
                    'title' => 'Outstanding & receipts',
                    'route' => 'outstanding.index',
                    'summary' => 'Outstanding is the unpaid part of posted sales, less receipts and credit notes.',
                    'steps' => [
                        'Open Outstanding Balances and filter the customer you are about to collect from. Check due date and overdue amount first.',
                        'Record a Payment Receipt against the customer and allocate it to the open invoices.',
                        'Use Reports → Party Ledger / Outstanding Statement when you need a printable statement or CSV/PDF export.',
                    ],
                ],
                [
                    'title' => 'Cheques & cheque books',
                    'route' => 'cheques.index',
                    'summary' => 'A cheque stays pending until the bank clears it. Cheque books track the physical series.',
                    'steps' => [
                        'Park an uncleared cheque in the Cheque Register. Do not treat it as cleared cash.',
                        'Maintain Cheque Books so issue and unused leaf numbers stay accurate.',
                        'Mark the cheque cleared when the bank statement shows it. The Books receipt then matches the bank. Use Reconciliation to finish the period.',
                    ],
                ],
                [
                    'title' => 'Credit notes & aging',
                    'route' => 'credit-notes.index',
                    'summary' => 'A credit note reduces the customer balance. Aging shows how late the rest is.',
                    'steps' => [
                        'Issue a credit note for a sales return or price reduction. It does not delete the original invoice.',
                        'Open Aging Analysis (Receivables or Reports) before month-end and follow up the oldest buckets first.',
                        'Reconcile the customer statement to the receipts and credit notes for the same period.',
                    ],
                ],
            ],
        ],
        [
            'number' => 8,
            'title' => 'Commercials',
            'purpose' => 'Apply schemes, targets, special deals, and overdue interest on top of the normal price — without rewriting posted invoices.',
            'sections' => [
                [
                    'title' => 'Expense types & special deals',
                    'route' => 'deals.index',
                    'summary' => 'A special deal is an off-list arrangement for one customer or order.',
                    'steps' => [
                        'Define the expense types that special deals are allowed to use.',
                        'Create the special deal with the customer, the period, and the agreed cost.',
                        'Approve the deal before it is used. An unapproved deal does not change the invoice total.',
                    ],
                ],
                [
                    'title' => 'Targets, rebates & schemes',
                    'route' => 'targets.index',
                    'summary' => 'These calculate a benefit on qualifying sales. They do not rewrite a posted invoice.',
                    'steps' => [
                        'Set the commercial target and rebate for the period, then the scheme rules that decide who qualifies.',
                        'Review the calculated benefit after the sales for that period are posted.',
                        'Settle the benefit with a credit note. The original tax invoices stay as they were posted.',
                    ],
                ],
                [
                    'title' => 'Interest & overdue',
                    'route' => 'interest.index',
                    'summary' => 'Interest applies when a customer crosses the credit days on an open invoice.',
                    'steps' => [
                        'Open Interest & Overdue and confirm the as-on date.',
                        'Review the customers who crossed their credit days before you raise an interest debit.',
                        'Post the interest as its own document. Do not edit the original sales invoice to add it.',
                    ],
                ],
            ],
        ],
        [
            'number' => 9,
            'title' => 'Sales Target Management',
            'purpose' => 'Assign sales targets to people and teams, track achievement, and act on alerts — separate from commercial rebate schemes.',
            'sections' => [
                [
                    'title' => 'Assign targets',
                    'route' => 'sales-targets.create',
                    'summary' => 'Targets are goals for a period (salesman, team, or branch). They drive dashboards, not invoice totals.',
                    'steps' => [
                        'Open Sales Target Management → Assign Targets and choose the period, person or team, and amount or quantity goal.',
                        'Save the assignment, then confirm it appears on Target List. Edit only open periods; locked periods stay for history.',
                        'Use Target Dashboard to see achievement against posted sales for the same filters.',
                    ],
                ],
                [
                    'title' => 'Performance & alerts',
                    'route' => 'sales-targets.dashboard',
                    'summary' => 'Performance screens show who is on track. Alerts flag shortfalls early.',
                    'steps' => [
                        'Open Salesman Performance for individual progress; Team & Branch Performance for rolled-up views.',
                        'Review Target Alerts for people or branches below threshold so you can coach before period end.',
                        'Use Target Reports for printable / exportable summaries for management meetings.',
                    ],
                ],
            ],
        ],
        [
            'number' => 10,
            'title' => 'HR · CRM',
            'purpose' => 'Run the people chain: holidays → leave policies → attendance (shifts / eSSL) → payroll runs → ESS and HR reports; CRM leads stay alongside. Service & Support is nested under the same sidebar group.',
            'sections' => [
                [
                    'title' => 'Organisation & employees',
                    'route' => 'hrms.employees.index',
                    'summary' => 'Departments, designations, and managers exist before the employee record. Link a User for ESS and set biometric_code for eSSL.',
                    'steps' => [
                        'Create Departments and Designations, then Managers via manager_id on the employee.',
                        'Create the employee with branch, joining date, bank details, and optional user login + biometric code.',
                        'Open HR Dashboard for today’s present / absent / late / on-leave lists.',
                    ],
                ],
                [
                    'title' => 'Holidays & leave policies',
                    'route' => 'hrms.holidays.index',
                    'summary' => 'Publish yearly calendars, then policies for CL/SL/EL/LWP/Comp-off with accrual, carry-forward, and encashment.',
                    'steps' => [
                        'Create a Holiday Calendar for the year (optional branch), add national/state/festival days as compulsory or optional, then Publish for ESS.',
                        'Define Leave Policies per type (yearly or monthly accrual, carry-forward max, half-day / short leave, encashment). Scope by department or designation if needed.',
                        'Run Allocate balances for the year. Employees apply leave (HR or ESS); day count skips Sundays, weekly offs, and compulsory holidays. Manager or HR approves.',
                    ],
                ],
                [
                    'title' => 'Attendance, shifts & eSSL',
                    'route' => 'hrms.attendances.index',
                    'summary' => 'Shifts and fixed/rotational weekly offs drive late / OT; punches can come from web, manual, or eSSL sync.',
                    'steps' => [
                        'Create Shifts with grace and full/half-day minutes; assign to employees with fixed offs or rotational every-Nth-week offs.',
                        'Record attendance manually/bulk or punch from ESS. Regularisation covers missing punches after approval (HR or manager ESS Approvals). Resolve Punch Conflicts when eSSL and web/manual differ.',
                        'Enable ESSL_ENABLED + ESSL_URL and pull/push punches. Use HR Reports. Comp-off credit is idempotent (OT / holiday work).',
                    ],
                ],
                [
                    'title' => 'Payroll runs & ESS',
                    'route' => 'hrms.payroll-runs.index',
                    'summary' => 'Salary structures + attendance/leave produce draft payroll with statutory PF/ESI/PT and encashment; approve, lock, bank CSV, PDF payslips, and dept reports.',
                    'steps' => [
                        'Maintain Pay Heads and employee Salary Structures; open History (also from employee profile) for revision timeline.',
                        'Create a Payroll Run for month/year; system applies LWP, OT, statutory PF/ESI/PT, and approved leave encashment. Approve then Lock. Download bank CSV, PDF payslips, and department/employee reports.',
                        'Linked employees use My ESS for holidays, leave, attendance, manager Approvals, and payslip PDF download.',
                    ],
                ],
                [
                    'title' => 'Claims & CRM leads',
                    'route' => 'hrms.expense-claims.index',
                    'summary' => 'Claims settle employee expenses. Leads convert to parties before sales.',
                    'steps' => [
                        'Record, approve, then settle claims.',
                        'Capture CRM leads through segments; convert won leads to parties before quotation or order.',
                    ],
                ],
            ],
        ],
        [
            'number' => 11,
            'title' => 'Service & Support',
            'purpose' => 'Register complaints, installations, and service jobs; assign Avit technicians / OEM centres; schedule visits; log work, warranty, charges, and feedback — with ticket numbers from Voucher Numbering (SRV / AVITSRV).',
            'sections' => [
                [
                    'title' => 'What lives where (sidebar)',
                    'route' => 'service.tickets.index',
                    'summary' => 'Under HR · CRM → Service & Support. Ticket Register is the home screen; other items are boards and registers around the same tickets.',
                    'steps' => [
                        'Ticket Register — create tickets, filters, open/overdue stats, escalate overdue SLA. Use + New Ticket (pick Complaint / Installation / Service on the form).',
                        'Daily Visit Schedule — day planner of open tickets whose expected visit (or visit / next-visit date) falls on the chosen date, optionally filtered by technician.',
                        'OEM Warranty Register, Quotation & Approval, Customer Feedback, Serial History, Reports & Analytics — read/follow-up views. Service Master Settings holds OEM centres, SLA hours by priority, and a shortcut to edit the SRV number series.',
                    ],
                ],
                [
                    'title' => 'Ticket numbering (AVITSRV)',
                    'route' => 'organization.document-number-series.index',
                    'summary' => 'Service tickets use document type SRV (e.g. AVITSRV-2627-0001). Customize like any other voucher series.',
                    'steps' => [
                        'Open Setup → Voucher Numbering, filter Service Ticket (SRV), and edit prefix / FY / padding — or use Service Master Settings → Edit SRV series.',
                        'New tickets pull the next number for the working company. Keep one active SRV series per company you service under.',
                    ],
                ],
                [
                    'title' => 'Create & triage a ticket',
                    'route' => 'service.tickets.create',
                    'summary' => 'One New Ticket form covers complaint, installation, and service. Installation fields appear when Request type = Installation.',
                    'steps' => [
                        'From Ticket Register click + New Ticket. Set Request type, Priority (drives SLA due), Handler (Avit / OEM / Both), party, product/serial, and problem text.',
                        'Optional: quick-create OEM centre, brand, or category from the form. Save — ticket opens as New with SLA due from Service Master Settings.',
                        'On the ticket screen assign an Avit technician and/or OEM centre, set Expected visit, then Assign (status becomes Assigned when it was New). Use + Create / Delete on those dropdowns when needed.',
                    ],
                ],
                [
                    'title' => 'Daily Visit Schedule',
                    'route' => 'service.schedule',
                    'summary' => 'Operational day board — not a separate calendar master. It lists tickets you already scheduled via Expected visit (or logged visit / next visit).',
                    'steps' => [
                        'After Assign, set Expected visit date/time on the ticket. That row appears under Daily Visit Schedule for that date.',
                        'Filter by date and technician to plan the day. Click the ticket number to open the job and log the visit report.',
                        'Empty day = no open tickets with expected_visit_at (or visit/next-visit) on that date. Closed tickets drop off the board.',
                    ],
                ],
                [
                    'title' => 'Field work → close',
                    'route' => 'service.tickets.index',
                    'summary' => 'Status path: New → Assigned → Visit → Pending (optional) → Completed → Confirmed → Closed (or Cancelled).',
                    'steps' => [
                        'Log Visit reports (action taken, parts, photos). Status moves to Visit when still New/Assigned.',
                        'Update Charges (paid/warranty), OEM Warranty claim fields, and Installation details when relevant. Raise a service invoice from the ticket when chargeable.',
                        'Move status to Completed (remarks), Confirmed, then Closed. Capture Customer Feedback (rating/comments). Use Escalate overdue on Ticket Register to flag SLA breaches.',
                    ],
                ],
                [
                    'title' => 'Registers & settings',
                    'route' => 'service.oem-centres.index',
                    'summary' => 'Follow-up screens and masters that support the ticket lifecycle.',
                    'steps' => [
                        'OEM Warranty Register — tickets/claims with OEM tracking. Quotation & Approval — chargeable quotes awaiting customer approval.',
                        'Customer Feedback and Serial History — ratings and prior jobs for a serial. Reports & Analytics — volume, priority, technician load.',
                        'Service Master Settings — OEM centre list, SLA resolution hours by priority (Critical/High/Normal/Low), and SRV numbering links.',
                    ],
                ],
            ],
        ],
        [
            'number' => 12,
            'title' => 'Accounting (Books)',
            'purpose' => 'Read and post the same business in ledgers: vouchers, bank, GST, stock value, and financial statements — opened from the main sidebar as a second panel.',
            'sections' => [
                [
                    'title' => 'Open the Books workspace',
                    'route' => 'books.tally.dashboard',
                    'summary' => 'Click Accounting (Books) in the main sidebar. The primary DMS menu stays open and the Accounting Books panel appears beside it.',
                    'steps' => [
                        'From any DMS screen, open Accounting (Books). You land on the Books Dashboard with the secondary Accounting Books sidebar.',
                        'The main sidebar keeps Dashboard, Masters, Sales, Reports, and the rest — so you can jump back without closing Books.',
                        'Use the Books search (Ctrl+K) in the secondary sidebar to jump to Chart of Accounts, Day Book, GST, banking, and other screens.',
                    ],
                ],
                [
                    'title' => 'Books dashboard & context bar',
                    'route' => 'books.tally.dashboard',
                    'summary' => 'Tiles follow company, branch, financial year, and as-on date. Unposted invoices on that branch can be included.',
                    'steps' => [
                        'Choose Company, Branch, and Financial Year in the top bar before you read the tiles.',
                        'Set As on and Apply. Sales, receivables, payables, cash, bank, assets, and stock recalculate up to that date.',
                        'Use + New Voucher, Receipt, Payment, or Day Book from the banner for day-to-day entry. If another branch has unposted bills, switch branch or open Day Book with All branches.',
                    ],
                ],
                [
                    'title' => 'Day book, statements & categories',
                    'summary' => 'Day book is the live list. Trial balance, P&L, and balance sheet read posted ledgers. Categories A–Q organise the rest of Books.',
                    'steps' => [
                        'Open Day Book from the gateway or Financial Statements. Posted vouchers and unposted invoices both appear; unposted rows are marked.',
                        'Work through Company & Foundation (Chart of Accounts, Ledgers), Sales & Receivables, Purchase & Payables, Banking & Treasury, GST & Compliance, and Inventory as needed.',
                        'Use Trial Balance, Profit & Loss, and Balance Sheet after bank, outstanding, and stock are agreed. Cancel or reverse a posted document — never delete it.',
                    ],
                ],
                [
                    'title' => 'How DMS documents land in Books',
                    'summary' => 'Operational documents sync into Books. A posted Books document is not overwritten by a later DMS edit.',
                    'steps' => [
                        'Sales invoices, purchase invoices, receipts, payments, cheques, freight, landed cost, and stock journals made in DMS appear in Books.',
                        'E-Invoice on a sales invoice follows the sales layout; on a purchase invoice it follows the purchase layout.',
                        'Record pure accounting (contra, journal, GST returns, period lock, year-end) from Books. Keep company/branch/FY selected so entries land in the right books.',
                    ],
                ],
            ],
        ],
        [
            'number' => 13,
            'title' => 'Reports',
            'purpose' => 'Review and export what already happened — registers, ledgers, stock, aging, and financial statements — with shared date filters and CSV/PDF where available.',
            'sections' => [
                [
                    'title' => 'How to use every report',
                    'route' => 'reports.sales',
                    'summary' => 'Reports never create stock or balances. They read posted (and sometimes draft) activity for the filters you set.',
                    'steps' => [
                        'Open Reports from the main sidebar. Pick the report that matches the question (sales, purchases, stock, party, finance).',
                        'Set the financial-year-aware date range, party, warehouse, or search chips, then Apply. Empty results usually mean no posted rows in that window — check sync and FY.',
                        'Use Export CSV or Export PDF when you need to share or archive. Filters on screen are the same filters used for the export.',
                    ],
                ],
                [
                    'title' => 'Sales, purchase & operations registers',
                    'route' => 'reports.quotations',
                    'summary' => 'Registers list documents for a period — quotations, sales, POs, purchase bills, payments, deliveries, pending orders.',
                    'steps' => [
                        'Quotation Register and Purchase Order Register list offers and POs with status; export for follow-up.',
                        'Sales Register and Purchase Register list tax invoices / purchase bills. Payments Report and Delivery Report cover collections and runs.',
                        'Pending Orders shows what is still open. Use these before chasing stock or receivables so you work from one list.',
                    ],
                ],
                [
                    'title' => 'Stock, party ledger & aging',
                    'route' => 'reports.party-statement',
                    'summary' => 'Stock and party screens answer “what do we hold?” and “what does this party owe / are we owed?”',
                    'steps' => [
                        'Stock Report and Stock Ledger show quantity and movement; Margin Analysis shows contribution on sales.',
                        'Party Ledger (Party Statement) builds opening + invoices + receipts + credit notes for a party. Export when sending a statement.',
                        'Outstanding Statement, Salesman Outstanding, Aging Analysis, and OD Report drive collection follow-up by bucket or salesman.',
                    ],
                ],
                [
                    'title' => 'Financial & group reports',
                    'route' => 'reports.day-book',
                    'summary' => 'Day Book, P&L, Balance Sheet, and Trial Balance mirror the Books statements for management and audit.',
                    'steps' => [
                        'Open Day Book, Profit & Loss, Balance Sheet, or Trial Balance under Reports with the same FY dates you use in Books.',
                        'Agree bank, stock, and outstanding before you treat P&L / BS as final for the period.',
                        'Use Group Dashboard and Sister Concern Reports when multiple companies need a combined view without merging ledgers.',
                    ],
                ],
            ],
        ],
        [
            'number' => 14,
            'title' => 'Tally Connector',
            'purpose' => 'Bridge hosted DMS to office TallyPrime so commercial vouchers and masters stay aligned — without exposing Tally to the internet.',
            'sections' => [
                [
                    'title' => 'How the connector works',
                    'route' => 'tally-connector.show',
                    'summary' => 'DMS is on the internet. TallyPrime stays on the office PC. tally_connector.exe moves XML both ways on a timer.',
                    'steps' => [
                        'The cloud never opens Tally. Only the connector on the Tally PC talks to Tally at http://127.0.0.1:9000.',
                        'Every few seconds the EXE polls DMS with the connector token, talks to local Tally, then sleeps (poll_seconds, default 8).',
                        'Each poll: (1) take Pending queue XML from DMS into Tally; (2) pull Tally exports and post replies into Books / linked masters.',
                        'While the EXE runs, Tally is open with HTTP/XML enabled, and TALLY_SYNC_ENABLED=true, sync is automatic — you do not click Send on each document.',
                    ],
                ],
                [
                    'title' => 'Outbound (DMS → Tally)',
                    'route' => 'tally.queue.index',
                    'summary' => 'Posted or approved DMS work becomes a Pending row. The connector delivers XML into Tally and reports Sent or Failed.',
                    'steps' => [
                        'Post or approve a document in DMS. Auto-enqueue writes a Pending queue row with XML.',
                        'The connector fetches pending items and POSTs each payload to local Tally, then reports Sent or Failed with the error text.',
                        'Open Tally Queue to watch Pending → Sent. Retry a Failed row only after the ledger or stock-item name in Tally matches what DMS sent.',
                        'Outbound covers masters (items, parties, units, godowns), invoices, credit notes, PO/SO/quotation, GRN, receipts/payments, stock journals, freight/landed cost.',
                    ],
                ],
                [
                    'title' => 'Inbound (Tally → DMS)',
                    'summary' => 'The same poll pulls masters, vouchers, and report snapshots into Books. Some document types are intentionally skipped.',
                    'steps' => [
                        'Imported: groups, ledgers, units, godowns, stock items; sales/purchase/receipt/payment/contra/journal/CN/DN; stock journals; day book / P&L / TB / BS / GST packs.',
                        'A Tally Receipt Note becomes goods receipt / stock-in (not a cash Receipt). Payment Receipt stays an accounting receipt.',
                        'Skipped on import: sales order, purchase order, quotation, delivery note — DMS remains the source of truth for those.',
                        'If both sides changed the same record, a conflict is logged and neither side is overwritten. Resolve manually, then let the next poll continue.',
                    ],
                ],
                [
                    'title' => 'Install and keep it running',
                    'route' => 'tally-connector.show',
                    'summary' => 'Download once on the Tally PC, fill config.json, keep Tally and the EXE running.',
                    'steps' => [
                        'Open Tally Connector (or Tally Queue → Download Connector). Download the zip on the Windows PC that runs Tally — not on the web server.',
                        'Unzip and edit config.json: dms_url, connector_token (same as TALLY_CONNECTOR_TOKEN), tally_url, company (exact Tally name), poll_seconds.',
                        'In TallyPrime enable HTTP/XML on that port and leave the company open. Start tally_connector.exe and leave the console open (optional install-task.ps1 for logon).',
                        'Server .env needs TALLY_SYNC_ENABLED=true, TALLY_CONNECTOR_TOKEN, and usually TALLY_COMPANY; then php artisan config:clear. No Node.js is required.',
                    ],
                ],
            ],
        ],
    ],
];
