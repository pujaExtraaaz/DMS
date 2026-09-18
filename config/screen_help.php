<?php

/**
 * Contextual on-screen help for DMS pages.
 * Keys are route-name prefixes (longest match wins).
 */
return [

    'default' => [
        'title' => 'How to use this screen',
        'summary' => 'Complete the fields, save, then continue the next step in the daily flow: Purchase → Stock → Sale → Collect.',
        'steps' => [
            'Read the purpose below before posting.',
            'Fill required fields marked on the form.',
            'Save / Approve — do not delete posted stock or finance documents; use reverse/cancel flows.',
        ],
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'summary' => 'Live KPIs for sales, stock, outstanding, risk and targets. Use the date filter first.',
        'steps' => [
            'Pick Today / Week / Month / Custom date range.',
            'Review alerts (frozen parties, overdue, pending orders).',
            'Open any KPI card/report for drill-down.',
        ],
    ],

    'organization.companies' => [
        'title' => 'Companies',
        'summary' => 'Legal company master. Set GST, bank and due-date basis used on invoices.',
        'steps' => [
            'Create company with code, GSTIN and bank details.',
            'Set Due Date Basis = Invoice Date or Inward/Receive Date.',
            'Link to Sister Concern (Business Group) if needed — ledgers stay separate.',
        ],
    ],

    'organization.branches' => [
        'title' => 'Branches',
        'summary' => 'Operating locations under a company.',
        'steps' => ['Select company', 'Add branch code/name/address', 'Activate for user access'],
    ],

    'organization.warehouses' => [
        'title' => 'Warehouses / Godowns',
        'summary' => 'Stock locations used on inward, reservation and transfers.',
        'steps' => ['Link warehouse to branch/company', 'Use this warehouse on GRN and sales reservation'],
    ],

    'organization.financial-years' => [
        'title' => 'Financial Years',
        'summary' => 'Accounting periods. Closed years block new postings.',
        'steps' => ['Create FY date range', 'Mark one year as Current', 'Do not post into closed FY'],
    ],

    'organization.business-groups' => [
        'title' => 'Sister Concerns',
        'summary' => 'Group separate legal companies for reporting without merging ledgers.',
        'steps' => ['Create business group', 'Attach companies', 'Use consolidated reports only when needed'],
    ],

    'masters.brands' => [
        'title' => 'Brands',
        'summary' => 'Top of product hierarchy and scheme/region rules.',
        'steps' => ['Create brand', 'Later attach categories and products', 'Configure region policies if brand is territory-restricted'],
    ],

    'masters.categories' => [
        'title' => 'Categories',
        'summary' => 'Brand → Category → Sub-category → Product.',
        'steps' => ['Select brand (if prompted)', 'Create category', 'Add sub-categories next'],
    ],

    'masters.sub-categories' => [
        'title' => 'Sub Categories',
        'summary' => 'Refine catalog under a category.',
        'steps' => ['Select category', 'Create sub-category', 'Assign products to it'],
    ],

    'masters.products' => [
        'title' => 'Products',
        'summary' => 'Item master: prices, tax, warranty, image, serial/batch tracking.',
        'steps' => [
            'Enter SKU, name, UOM, brand/category.',
            'Set selling/trade/purchase price, MRP, discount, warranty.',
            'Upload image + catalog link if available.',
            'Choose Tracking: None / Serial / Batch before first inward.',
        ],
    ],

    'masters.customers' => [
        'title' => 'Parties (Customers / Suppliers)',
        'summary' => 'Credit profile starts here — limit, days, interest, party type.',
        'steps' => [
            'Set party type: Customer / Supplier / Dealer / Both.',
            'Enter credit limit, credit days and interest rate.',
            'Add billing/shipping contacts for delivery & transit.',
            'Frozen parties cannot take new credit sales until unfrozen.',
        ],
    ],

    'masters.price-masters' => [
        'title' => 'Price Master',
        'summary' => 'Party-type / slab based selling prices.',
        'steps' => ['Select customer type + product + UOM', 'Set price and min qty', 'Save — orders pick applicable price'],
    ],

    'purchasing.orders' => [
        'title' => 'Purchase Orders',
        'summary' => 'Order stock from supplier. Approve before inward.',
        'steps' => [
            'Create PO: supplier, warehouse, items, rates, expected date.',
            'Submit for approval → Approve.',
            'Next: Inward / GRN against this PO.',
        ],
    ],

    'purchasing.inwards' => [
        'title' => 'Inward / GRN',
        'summary' => 'Receive goods, assign serials/batches, post stock IN.',
        'steps' => [
            'Select approved PO.',
            'Enter received qty (within tolerance).',
            'Assign unique serials or batch + expiry.',
            'Post — stock ledger updates; duplicates are blocked.',
        ],
    ],

    'purchasing.invoices' => [
        'title' => 'Purchase Invoices',
        'summary' => 'Supplier bill with vendor price comparison and payable due date.',
        'steps' => [
            'Link inward / PO and enter supplier invoice no/date.',
            'Review vendor price comparison before saving higher rates.',
            'Due date follows company due-date basis + supplier credit days.',
            'Next: Freight Bill → Landed Cost if freight applies.',
        ],
    ],

    'purchasing.freight-bills' => [
        'title' => 'Freight Bills',
        'summary' => 'Record freight bill number, vendor and amount for allocation.',
        'steps' => ['Enter freight bill no, vendor, date, amount', 'Link purchase/inward invoices', 'Allocate in Landed Cost'],
    ],

    'purchasing.landed-costs' => [
        'title' => 'Landed Cost',
        'summary' => 'Allocate freight/charges to items without changing supplier invoice rate.',
        'steps' => [
            'Select freight bill / charges and linked invoice lines.',
            'Choose allocation: qty / value / weight / equal / manual.',
            'Post — inventory valuation uses landed unit cost.',
        ],
    ],

    'inventory.stock' => [
        'title' => 'Stock Register',
        'summary' => 'On-hand quantity by product / warehouse.',
        'steps' => ['Filter warehouse or low stock', 'Drill to stock ledger for movements', 'Use Transfers/Adjustments to correct stock'],
    ],

    'inventory.transfers' => [
        'title' => 'Stock Transfers',
        'summary' => 'Move stock Warehouse A → B.',
        'steps' => ['Create transfer with items', 'Dispatch from source', 'Receive at destination'],
    ],

    'inventory.adjustments' => [
        'title' => 'Stock Adjustments',
        'summary' => 'Increase/decrease stock with reason (damage, found, write-off).',
        'steps' => ['Choose IN or OUT', 'Enter qty + reason', 'Post — creates ledger movement'],
    ],

    'inventory.serials' => [
        'title' => 'Serial Lifecycle',
        'summary' => 'Lookup → Reserve → Deliver → Return for serial-tracked items.',
        'steps' => [
            'Lookup serial to see status/location/inward.',
            'Reserve with a note when holding for a sale.',
            'Deliver when handed to customer.',
            'Return to put sold/reserved serials back in stock.',
        ],
    ],

    'inventory.valuation' => [
        'title' => 'FIFO / LIFO Valuation',
        'summary' => 'Company costing method for stock value and COGS layers.',
        'steps' => ['Select company + method (FIFO or LIFO)', 'Activate setting', 'New stock OUT consumes layers by that method'],
    ],

    'inventory.purchases' => [
        'title' => 'Quick Purchase',
        'summary' => 'Simple purchase entry when full PO cycle is not needed.',
        'steps' => ['Enter supplier and lines', 'Post stock', 'Prefer full PO→GRN→PI for audit-heavy purchases'],
    ],

    'orders' => [
        'title' => 'Sales Orders',
        'summary' => 'Credit-checked orders. Warehouse mode reserves stock; van mode feeds dispatch.',
        'steps' => [
            'Create order: party, salesperson, items, warehouse/van mode.',
            'System checks credit limit and frozen status.',
            'Approve — warehouse orders reserve stock; back-order flags short stock.',
            'Then Deliver / Convert to Invoice as per mode.',
        ],
    ],

    'quotations' => [
        'title' => 'Quotations',
        'summary' => 'Optional quote before order. Admins can see estimated profit on landed cost.',
        'steps' => ['Create quote with party and items', 'Review rates/discounts', 'Convert to sales order when accepted'],
    ],

    'invoices' => [
        'title' => 'Sales Invoices',
        'summary' => 'Customer bill creates receivable. Due date = basis date + credit days.',
        'steps' => [
            'Create from order or Direct Billing.',
            'Confirm party, items, tax and due date.',
            'Post/Issue — outstanding ledger updates.',
            'Send WhatsApp/Email/Notification from invoice actions (tracked separately).',
        ],
    ],

    'region-policies' => [
        'title' => 'Region Brand Policies',
        'summary' => 'Restrict brands to regions/states. Override needs reason + audit.',
        'steps' => ['Define brand + allowed regions', 'Orders/quotes/invoices validate rules', 'Override only with authorized reason'],
    ],

    'payments' => [
        'title' => 'Collections / Payments',
        'summary' => 'Receive cash/UPI/bank/cheque and allocate to invoices.',
        'steps' => ['Select party', 'Enter amount + method', 'Allocate to one or more invoices', 'Partial payment leaves balance outstanding'],
    ],

    'cheques' => [
        'title' => 'Cheques / PDC / Security',
        'summary' => 'Vendor & client security cheques and PDC. Bounce > 3 freezes party.',
        'steps' => [
            'Create cheque: purpose (Security/PDC), direction, party, bank, amount.',
            'Update status: Pending → Deposited → Cleared/Bounced.',
            'On bounce, count increases; after threshold party is frozen.',
        ],
    ],

    'credit-notes' => [
        'title' => 'Credit Notes',
        'summary' => 'Reduce receivable for return, rate difference, scheme, damage.',
        'steps' => ['Link invoice if applicable', 'Select reason', 'Approve/post — outstanding reduces; stock may return'],
    ],

    'outstanding' => [
        'title' => 'Outstanding',
        'summary' => 'Party-wise receivable balances.',
        'steps' => ['Filter branch/party', 'Follow up overdue', 'Use Party Statement for ledger detail'],
    ],

    'reconciliation' => [
        'title' => 'Reconciliation',
        'summary' => 'Match collections and invoice balances.',
        'steps' => ['Review unmatched items', 'Allocate/reconcile differences', 'Confirm ledger agrees with invoices'],
    ],

    'logistics.load-sheets' => [
        'title' => 'Dispatch / Load Sheets',
        'summary' => 'Van path: load invoices/stock onto vehicle.',
        'steps' => ['Create load sheet with vehicle/driver', 'Add invoices/orders', 'Dispatch → then Delivery → Settlement'],
    ],

    'deliveries' => [
        'title' => 'Deliveries',
        'summary' => 'Confirm delivery, short supply or returns.',
        'steps' => ['Open delivery from load/order', 'Mark delivered quantities', 'Record shorts/returns', 'Settle cash on Settlement screen'],
    ],

    'settlements' => [
        'title' => 'Cash Settlement',
        'summary' => 'Reconcile van collections against load sheet.',
        'steps' => ['Select load sheet/date', 'Enter cash/UPI/cheque collected', 'Close settlement differences'],
    ],

    'deals' => [
        'title' => 'Deals & Project Margin',
        'summary' => 'Attach architect/site/installation/freight expenses for net margin.',
        'steps' => ['Create deal/project', 'Link invoice', 'Add expenses → approve', 'Review Margin Register'],
    ],

    'expense-types' => [
        'title' => 'Expense Types',
        'summary' => 'Master list for deal expenses (architect, installation, etc.).',
        'steps' => ['Create expense type', 'Use when posting deal expenses'],
    ],

    'targets' => [
        'title' => 'Targets',
        'summary' => 'Salesman / party / brand targets by period.',
        'steps' => ['Create period (Month/Quarter/Year)', 'Assign target amount', 'Achievement updates from posted sales'],
    ],

    'schemes' => [
        'title' => 'Brand Schemes',
        'summary' => 'Slabs for discount/rebate/free qty. Close period to settle.',
        'steps' => ['Create scheme + validity + slabs', 'Sales qualify provisionally', 'Close period → settlement/credit note'],
    ],

    'interest' => [
        'title' => 'Overdue Interest',
        'summary' => 'Interest = Overdue × Rate × Days / 365 on outstanding only.',
        'steps' => ['Preview interest for overdue invoices', 'Post interest ledger / debit note', 'Rates come from party/company settings'],
    ],

    'hrms' => [
        'title' => 'HRMS',
        'summary' => 'Employees, attendance, leave and claims linked to sales users.',
        'steps' => ['Create employee and map user/branch', 'Mark attendance', 'Approve leave/claims', 'Use for salesman targets/incentives'],
    ],

    'crm.leads' => [
        'title' => 'CRM Leads',
        'summary' => 'Meta/manual leads → assign → follow-up → convert to party → order.',
        'steps' => ['Create or import lead', 'Assign salesperson', 'Log follow-ups', 'Convert qualified lead to Party', 'Create quotation/order'],
    ],

    'tally' => [
        'title' => 'Tally Sync Queue',
        'summary' => 'Posted docs become Tally XML. Use the Download Connector ZIP on the office PC to push into local Tally.',
        'steps' => [
            'Download tally-connector.zip from this screen.',
            'On the Tally PC: unzip → install Node.js 18+ → run start.bat → set connector_token in config.json.',
            'Keep Tally open with HTTP port 9000 enabled.',
            'Pending items become Sent when the bridge posts successfully. Retry failed rows if needed.',
            'Open SETUP-GUIDE.html inside the ZIP for the full walkthrough.',
        ],
    ],

    'reports' => [
        'title' => 'MIS Reports',
        'summary' => 'Filter by date, branch, party, supplier, brand, category, item, salesperson.',
        'steps' => [
            'Set filters before Run.',
            'Pending Orders: ordered vs reserved vs delivered vs pending.',
            'Salesman Outstanding & Aging for collections.',
            'Margin Register for net profit after deal expenses.',
        ],
    ],

    'communications' => [
        'title' => 'Communications',
        'summary' => 'Track WhatsApp / Email / Notification status for invoices.',
        'steps' => ['Open invoice communication actions', 'Send channel', 'Check log: queued / sent / failed'],
    ],

    'users' => [
        'title' => 'Users',
        'summary' => 'Create users and assign roles for branch-wise access.',
        'steps' => ['Add user email/password', 'Assign role', 'Limit by company/branch as needed'],
    ],

];
