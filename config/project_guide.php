<?php

return [
    'modules' => [
        [
            'number' => 1,
            'title' => 'Setup',
            'purpose' => 'Create the legal company and the places stock and users belong to before any transaction.',
            'sections' => [
                [
                    'title' => 'Company Profile',
                    'summary' => 'The profile is the legal identity printed on invoices, purchase orders, and Books vouchers.',
                    'steps' => [
                        'Open Company Profile and enter the legal name, trade name, GSTIN, PAN, and registered address.',
                        'Add the bank account that receipts and payments will use, and set the invoice due-date basis.',
                        'Save before creating branches. A blank GSTIN or address is what later prints as a blank tax block.',
                    ],
                ],
                [
                    'title' => 'Companies, branches, and warehouses',
                    'summary' => 'A company owns the books. A branch is an operating location. A warehouse is where quantity sits.',
                    'steps' => [
                        'Add each legal company under Companies. Sister concerns stay separate; they are grouped only for reporting.',
                        'Add a branch for every location that raises invoices or holds stock, then a warehouse under that branch.',
                        'Pick the working company, branch, and financial year in the Books top bar. Reports follow that selection.',
                    ],
                ],
                [
                    'title' => 'Financial year',
                    'summary' => 'Every voucher and invoice date must fall inside an open year.',
                    'steps' => [
                        'Create the year with its start and end dates, then mark one year as current.',
                        'Post new sales, purchases, receipts, and payments only into the open year.',
                        'Close a year only after the trial balance, stock, and bank are agreed. A closed year rejects new postings.',
                    ],
                ],
            ],
        ],
        [
            'number' => 2,
            'title' => 'Masters',
            'purpose' => 'Build the catalog and party list that every purchase, stock movement, and sale will use.',
            'sections' => [
                [
                    'title' => 'Brands, categories, and product types',
                    'summary' => 'Classification comes before the product. Product type is also where serial tracking is switched on.',
                    'steps' => [
                        'Create Brands, then Categories under a brand, then Sub Categories under a category.',
                        'Open Product Types and tick Track serial numbers for types that must be serialized. The list shows Serial numbers under that type.',
                        'Use Find a product type to walk brand, category, and sub-category until the matching type is left.',
                    ],
                ],
                [
                    'title' => 'Products and price list',
                    'summary' => 'A product cannot be purchased or sold until it exists with a unit, tax rate, and price.',
                    'steps' => [
                        'Create the product with HSN, tax, UOM, and prices. Set Product Type to the type that should control serials.',
                        'Saving a product whose type tracks serials turns serial tracking on for that product. Purchase invoices then require a serial.',
                        'Maintain the Price List used on orders. Inclusive and exclusive GST rates are stored from that list.',
                    ],
                ],
                [
                    'title' => 'Parties',
                    'summary' => 'Customers and suppliers are the same party master, marked by party type.',
                    'steps' => [
                        'Create the party and mark it customer, supplier, or both. GSTIN and addresses print on documents.',
                        'Add more than one contact person when purchasing or sales deals with different people at the same party.',
                        'Use Bulk Import when the catalog or party list is already in a spreadsheet. Review the imported rows before the first invoice.',
                    ],
                ],
            ],
        ],
        [
            'number' => 3,
            'title' => 'Purchases',
            'purpose' => 'Bring goods in from suppliers and record the bill that increases stock and payables.',
            'sections' => [
                [
                    'title' => 'Purchase orders',
                    'summary' => 'The order is the commitment. It does not move stock until a purchase invoice is posted.',
                    'steps' => [
                        'Create a purchase order, search the supplier by name, and add the warehouse, items, rates, and tax.',
                        'Set freight as a percentage of the pre-GST amount or as a fixed rupee amount. Freight tax is included in the order total.',
                        'Print the order and edit Order Notes at the top of the print. Those notes save back onto the order.',
                    ],
                ],
                [
                    'title' => 'Purchase invoices',
                    'summary' => 'The supplier bill is what Books, stock, and payables read.',
                    'steps' => [
                        'Create the invoice against the supplier and, when you have one, the purchase order. Put delivery instructions in Remarks at the top.',
                        'Type a serial on every line whose product type tracks serials. The invoice will not save while a required serial is blank.',
                        'Post the invoice. Stock increases in the warehouse, the supplier balance increases, and the Books day book shows the bill.',
                    ],
                ],
                [
                    'title' => 'Freight bills and comparison',
                    'summary' => 'Transport billed separately, and vendor quotes compared before the order, stay under Purchases.',
                    'steps' => [
                        'Record a freight bill when the transporter invoices you apart from the goods bill.',
                        'Use Comparison Sheets to line up vendor quotes, landed cost, and the chosen supplier before converting to purchase orders.',
                        'Open the purchase invoice print when you need the same layout used for the Books purchase document.',
                    ],
                ],
            ],
        ],
        [
            'number' => 4,
            'title' => 'Inventory',
            'purpose' => 'See what is on hand and correct quantity or location without creating a sale or purchase.',
            'sections' => [
                [
                    'title' => 'Current stock',
                    'summary' => 'Quantity on this screen is what posted purchases, sales, transfers, and adjustments have already done.',
                    'steps' => [
                        'Check Current Stock before promising a sale or after posting a purchase.',
                        'Filter by warehouse when the same product is held in more than one godown.',
                        'A draft invoice does not change this quantity. Post it first, then refresh stock.',
                    ],
                ],
                [
                    'title' => 'Transfers and adjustments',
                    'summary' => 'A transfer moves quantity between warehouses. An adjustment changes quantity.',
                    'steps' => [
                        'Use Stock Transfer to move quantity from one warehouse to another. Company-wide stock does not change.',
                        'Use Adjustments for damage, shortage, or found stock, and post the adjustment.',
                        'Open Stock Valuation when you need the value of stock on hand, not only the quantity.',
                    ],
                ],
                [
                    'title' => 'Serial numbers',
                    'summary' => 'Serials are collected on the purchase invoice for product types that track them.',
                    'steps' => [
                        'Turn Track serial numbers on at the product type, then assign that type on the product.',
                        'Enter one serial per unit on the purchase invoice. Blank serials on a tracked line are rejected.',
                        'Look up a serial later from Inventory when you need to see where that unit is.',
                    ],
                ],
            ],
        ],
        [
            'number' => 5,
            'title' => 'Sales & Orders',
            'purpose' => 'Turn a customer requirement into a booked order and then a tax invoice.',
            'sections' => [
                [
                    'title' => 'Quotations and orders',
                    'summary' => 'A quotation is an offer. An order is the commercial commitment. Neither one posts stock by itself.',
                    'steps' => [
                        'Create a quotation while the customer has not confirmed the rate or quantity.',
                        'Create an order with the customer, warehouse, products, and prices once they confirm.',
                        'Use Region Policies only when a brand may be sold in limited territories.',
                    ],
                ],
                [
                    'title' => 'Tax invoices',
                    'summary' => 'The tax invoice is the sales document that Books, receivables, and stock read.',
                    'steps' => [
                        'Create the tax invoice from the order, or create an invoice directly when there is no order.',
                        'Print it, then open E-Invoice and E-Way Bill. Sales documents use the sales-invoice layout.',
                        'Posting the invoice reduces stock and raises the customer balance. The Books dashboard includes it, including while it is still unposted.',
                    ],
                ],
                [
                    'title' => 'Print options',
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
            'purpose' => 'Move invoiced goods on a vehicle and close the trip with expenses and cash.',
            'sections' => [
                [
                    'title' => 'Trip sheets and load',
                    'summary' => 'A load groups the invoices that will travel on one vehicle.',
                    'steps' => [
                        'Build the trip sheet from invoices that are already posted.',
                        'Confirm the vehicle, driver, and the invoices included before the vehicle leaves.',
                        'Do not add a draft invoice to a load. Post it first so the quantity is real.',
                    ],
                ],
                [
                    'title' => 'Delivery runs',
                    'summary' => 'The run is the record of what was handed over.',
                    'steps' => [
                        'Create a delivery run from the load and mark each stop as it is completed.',
                        'Record a failed delivery on that stop instead of deleting the invoice.',
                        'Leave the run open until every stop has a result.',
                    ],
                ],
                [
                    'title' => 'Trip settlements',
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
            'purpose' => 'Collect money against sales invoices and keep the customer balance correct.',
            'sections' => [
                [
                    'title' => 'Outstanding balances',
                    'summary' => 'Outstanding is the unpaid part of posted sales, less receipts and credit notes.',
                    'steps' => [
                        'Open Outstanding Balances and filter the customer you are about to collect from.',
                        'Check the due date and the overdue amount before you record a receipt.',
                        'An unposted sales invoice is visible on the Books dashboard, and it becomes outstanding once it is posted.',
                    ],
                ],
                [
                    'title' => 'Receipts and cheques',
                    'summary' => 'A receipt reduces what the customer owes. A cheque stays pending until the bank clears it.',
                    'steps' => [
                        'Record a payment receipt against the customer and allocate it to the open invoices.',
                        'Park an uncleared cheque in the Cheque Register. Do not treat it as cleared cash.',
                        'Mark the cheque cleared when the bank statement shows it. The Books receipt then matches the bank.',
                    ],
                ],
                [
                    'title' => 'Credit notes and aging',
                    'summary' => 'A credit note reduces the customer balance. Aging shows how late the rest is.',
                    'steps' => [
                        'Issue a credit note for a sales return or a price reduction. It does not delete the original invoice.',
                        'Open Aging Analysis before the month is closed and follow up the oldest buckets first.',
                        'Reconcile the customer statement to the receipts and credit notes for the same period.',
                    ],
                ],
            ],
        ],
        [
            'number' => 8,
            'title' => 'Commercials',
            'purpose' => 'Apply schemes, targets, special deals, and overdue interest on top of the normal price.',
            'sections' => [
                [
                    'title' => 'Expense types and special deals',
                    'summary' => 'A special deal is an off-list arrangement for one customer or order.',
                    'steps' => [
                        'Define the expense types that special deals are allowed to use.',
                        'Create the special deal with the customer, the period, and the agreed cost.',
                        'Approve the deal before it is used. An unapproved deal does not change the invoice total.',
                    ],
                ],
                [
                    'title' => 'Targets, rebates, and schemes',
                    'summary' => 'These calculate a benefit on qualifying sales. They do not rewrite a posted invoice.',
                    'steps' => [
                        'Set the target and rebate for the period, then the scheme rules that decide who qualifies.',
                        'Review the calculated benefit after the sales for that period are posted.',
                        'Settle the benefit with a credit note. The original tax invoices stay as they were posted.',
                    ],
                ],
                [
                    'title' => 'Interest and overdue',
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
            'title' => 'HR · CRM',
            'purpose' => 'Keep employee records and follow leads that are not yet customers.',
            'sections' => [
                [
                    'title' => 'Employees',
                    'summary' => 'Department and designation exist before the employee record.',
                    'steps' => [
                        'Create Departments and Designations, then the employee against those two.',
                        'Mark attendance for the working days you want payroll and leave to see.',
                        'Process leave requests against leave types and the employee’s leave balance.',
                    ],
                ],
                [
                    'title' => 'Claims',
                    'summary' => 'A claim is money the employee spent. It is not a purchase invoice.',
                    'steps' => [
                        'Record the claim with the expense type and the amount.',
                        'Approve it before settlement. A draft claim does not hit the books.',
                        'Settle the approved claim. That is the point it can appear in Books.',
                    ],
                ],
                [
                    'title' => 'Leads',
                    'summary' => 'A lead is not a party until you convert it.',
                    'steps' => [
                        'Capture the lead and move it through the segments you use for follow-up.',
                        'Keep notes on the lead until the deal is won or lost.',
                        'Convert a won lead into a party before the first quotation or order. Orders cannot be raised on a lead.',
                    ],
                ],
            ],
        ],
        [
            'number' => 10,
            'title' => 'Accounting (Books)',
            'purpose' => 'Read the same business in ledgers: sales, purchases, bank, GST, stock value, and the financial statements.',
            'sections' => [
                [
                    'title' => 'Books dashboard',
                    'summary' => 'The tiles follow the company, branch, and as-on date in the top bar, and they include unposted invoices on that branch.',
                    'steps' => [
                        'Choose the company, branch, and financial year before you read the tiles. Another branch’s bills stay out of this branch.',
                        'Set As on and apply. Sales, receivables, payables, cash, bank, and stock recalculate up to that date.',
                        'If the amber note names another branch, that branch has unposted invoices. Switch to it, or open Day Book and choose All branches.',
                    ],
                ],
                [
                    'title' => 'Day book and statements',
                    'summary' => 'Day book is the live list. Profit & Loss, trial balance, and balance sheet read posted ledgers.',
                    'steps' => [
                        'Open Day Book. Posted vouchers and unposted invoices both appear. Unposted rows are marked Unposted.',
                        'Open a row to finish or review that voucher or invoice. Saving it updates this list without a separate sync step.',
                        'Use Trial Balance, Profit & Loss, and Balance Sheet after the bank, outstanding, and stock have been agreed.',
                    ],
                ],
                [
                    'title' => 'Sales, purchases, receipts, and payments',
                    'summary' => 'Documents raised in DMS are copied into Books. A posted Books document is not overwritten.',
                    'steps' => [
                        'Sales invoices, purchase invoices, receipts, and payments made in the operational modules sync into Books.',
                        'E-Invoice on a sales invoice follows the sales layout. E-Invoice on a purchase invoice follows the purchase layout.',
                        'Record banking, GST, stock journals, and period-end adjustments from the Books sections. Cancel or reverse a posted document. Do not delete it.',
                    ],
                ],
            ],
        ],
    ],
];
