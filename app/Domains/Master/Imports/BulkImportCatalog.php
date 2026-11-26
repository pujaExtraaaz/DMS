<?php

namespace App\Domains\Master\Imports;

/**
 * Single source of truth for Masters bulk-import templates, samples, and UI parameter notes.
 */
class BulkImportCatalog
{
    /**
     * @return array<string, array{
     *     label: string,
     *     match: string,
     *     required: list<string>,
     *     columns: list<string>,
     *     sample: list<string|int|float>,
     *     note: string
     * }>
     */
    public static function all(): array
    {
        return [
            'companies' => [
                'label' => 'Companies',
                'match' => 'code (auto-generated when blank)',
                'required' => ['name'],
                'columns' => [
                    'code', 'name', 'legal_name', 'company_type', 'business_group_code',
                    'gstin', 'pan', 'address', 'city', 'state', 'pincode', 'phone', 'email', 'website',
                    'is_active', 'is_default',
                ],
                'sample' => [
                    'CMP-01', 'Acme Trading', 'Acme Trading Pvt Ltd', 'Private Limited', '',
                    '29AABCU9603R1ZM', 'AABCU9603R', '#1 Main Road', 'Bengaluru', 'Karnataka', '560001',
                    '9999900001', 'info@acme.test', 'https://acme.test', 1, 1,
                ],
                'note' => 'Required: name. Match on code. Leave code blank to auto-generate. business_group_code must already exist when provided.',
            ],
            'branches' => [
                'label' => 'Branches',
                'match' => 'company_code + code',
                'required' => ['company_code', 'name', 'branch_type', 'phone', 'address', 'state', 'city', 'country'],
                'columns' => [
                    'company_code', 'code', 'name', 'branch_type', 'phone', 'alternate_phone',
                    'email', 'alternate_email', 'address', 'address_line_2', 'state', 'city', 'country',
                    'pincode', 'notes', 'is_default', 'is_active',
                ],
                'sample' => [
                    'CMP-01', 'BR-HQ', 'Head Office', 'Head Office', '9999900002', '',
                    'hq@acme.test', '', '#1 Main Road', 'Near Metro', 'Karnataka', 'Bengaluru', 'India',
                    '560001', 'Primary branch', 1, 1,
                ],
                'note' => 'Import Companies first. city must belong to state. code is optional (auto-generated when blank).',
            ],
            'warehouses' => [
                'label' => 'Warehouses',
                'match' => 'company_code + branch_code + code',
                'required' => ['company_code', 'branch_code', 'name', 'address', 'state', 'city', 'country'],
                'columns' => [
                    'company_code', 'branch_code', 'code', 'name', 'warehouse_type',
                    'contact_person', 'contact_phone', 'address', 'address_line_2',
                    'state', 'city', 'country', 'pincode', 'notes', 'is_default', 'is_active',
                ],
                'sample' => [
                    'CMP-01', 'BR-HQ', 'WH-01', 'Main Warehouse', 'Main Warehouse',
                    'Store Manager', '9999900003', 'Godown Lane', 'Unit 2',
                    'Karnataka', 'Bengaluru', 'India', '560002', 'Default stock location', 1, 1,
                ],
                'note' => 'Import Branches first. code is optional (auto-generated when blank).',
            ],
            'financial-years' => [
                'label' => 'Financial Years',
                'match' => 'company_code + name',
                'required' => ['company_code', 'name', 'starts_on', 'ends_on'],
                'columns' => [
                    'company_code', 'name', 'starts_on', 'ends_on', 'is_current', 'is_closed',
                ],
                'sample' => [
                    'CMP-01', 'FY 2026-27', '2026-04-01', '2027-03-31', 1, 0,
                ],
                'note' => 'Dates as YYYY-MM-DD. Overlapping ranges are rejected. Only one current open year per company.',
            ],
            'uoms' => [
                'label' => 'UOMs',
                'match' => 'code',
                'required' => ['code'],
                'columns' => ['code', 'name', 'is_active'],
                'sample' => ['PCS', 'Pieces', 1],
                'note' => 'Import before Products / Price List when possible. name defaults to code when blank.',
            ],
            'brands' => [
                'label' => 'Brands',
                'match' => 'company + name',
                'required' => ['name'],
                'columns' => ['company_code', 'name', 'detail', 'is_active'],
                'sample' => ['CMP-01', 'Acme', 'Primary OEM brand', 1],
                'note' => 'company_code optional — uses your login company when blank.',
            ],
            'categories' => [
                'label' => 'Categories',
                'match' => 'brand_name + name',
                'required' => ['brand_name', 'name'],
                'columns' => ['brand_name', 'name', 'is_active'],
                'sample' => ['Acme', 'Widgets', 1],
                'note' => 'Import Brands first. Brand must already exist.',
            ],
            'sub-categories' => [
                'label' => 'Sub Categories',
                'match' => 'brand_name + category_name + name',
                'required' => ['brand_name', 'category_name', 'name'],
                'columns' => ['brand_name', 'category_name', 'name', 'is_active'],
                'sample' => ['Acme', 'Widgets', 'Standard', 1],
                'note' => 'Import Categories first. Brand + Category must already exist.',
            ],
            'product-types' => [
                'label' => 'Product Types',
                'match' => 'company + name/code',
                'required' => ['name'],
                'columns' => [
                    'company_code', 'name', 'code', 'description', 'track_serial', 'is_active',
                ],
                'sample' => ['CMP-01', 'Finished Goods', 'FG', 'Sellable finished items', 1, 1],
                'note' => 'company_code optional — uses your login company when blank. track_serial: 1/0.',
            ],
            'products' => [
                'label' => 'Products',
                'match' => 'part_number, else name + model',
                'required' => ['name', 'uom_code'],
                'columns' => [
                    'name', 'part_number', 'model', 'product_type', 'brand', 'category', 'sub_category',
                    'uom_code', 'alternate_uom_code', 'conversion_factor', 'supply_type', 'hsn_code', 'tax_rate',
                    'track_serial', 'track_batch',
                    'height_cm', 'length_cm', 'width_cm', 'net_weight', 'weight_unit',
                    'purchase_price', 'trade_price', 'selling_price', 'mrp', 'purchase_price_tax_basis',
                    'alt_purchase_price', 'alt_trade_price', 'alt_selling_price', 'alt_mrp',
                    'costing_method', 'market_valuation_method',
                    'min_stock', 'reorder_level', 'specification', 'warranty', 'catalog_link',
                    'series', 'customer_warranty_months', 'discount_percent', 'is_active',
                ],
                'sample' => [
                    'Widget X', 'PN-WIDGET-001', 'WX-100', 'Finished Goods', 'Acme', 'Widgets', 'Standard',
                    'PCS', 'BOX', 10, 'goods', '84713010', 18,
                    1, 0,
                    10, 20, 15, 1.5, 'KG',
                    100, 130, 150, 175, 'exclusive',
                    900, 1100, 1400, 1600,
                    'avg_cost', 'avg_price',
                    5, 10, 'Steel body', '12 months', 'https://example.com/catalog/widget-x',
                    'WX', 12, 0, 1,
                ],
                'note' => 'Required: name, uom_code. Brand/category/sub_category/product_type link only if they already exist. Missing UOMs are created. Flags/tax_basis: 1/0 or exclusive|inclusive.',
            ],
            'parties' => [
                'label' => 'Parties (Customers &amp; Suppliers)',
                'match' => 'code → GSTIN → name',
                'required' => ['name'],
                'columns' => [
                    'name', 'code', 'party_type', 'classification', 'trade_name', 'gstin', 'phone', 'email',
                    'opened_on', 'reference_by', 'udyam_number', 'website', 'location_link', 'is_active',
                    'area', 'route',
                    'address_line_1', 'address_line_2', 'city', 'state', 'pincode',
                    'credit_limit', 'credit_days', 'credit_status', 'credit_period_basis', 'interest_rate',
                    'contact_name', 'contact_role', 'contact_phone', 'contact_email', 'level',
                    'bank_account_holder', 'bank_name', 'bank_branch', 'bank_account_number', 'bank_account_type', 'bank_ifsc',
                ],
                'sample' => [
                    'Sample Distributor', 'PARTY-001', 'sundry_debtors', 'Retail', 'Sample Dist. Pvt Ltd',
                    '29AABCU9603R1ZM', '9999999999', 'ops@sample.co',
                    '2024-04-01', 'Referral - Ravi', 'UDYAM-KR-01-0000001', 'https://sample.co', 'https://maps.example/sample', 1,
                    'Zone A', 'Route 1',
                    '#12, MG Road', 'Near Metro', 'Bengaluru', 'Karnataka', '560001',
                    50000, 30, 'open', 'cumulative', 18,
                    'Ravi Kumar', 'Owner', '9999888877', 'ravi@sample.co', 'party',
                    'Sample Distributor', 'HDFC Bank', 'MG Road', '50200012345678', 'Current', 'HDFC0001234',
                ],
                'note' => 'Required: name. Match code → GSTIN → name. Same party data is skipped; changes update. classification, area, route auto-created. level defaults to party. opened_on as YYYY-MM-DD.',
            ],
            'price-master' => [
                'label' => 'Price List',
                'match' => 'customer_type + part_number + uom_code + min_qty',
                'required' => ['part_number', 'uom_code', 'rate'],
                'columns' => ['customer_type', 'part_number', 'uom_code', 'rate', 'min_qty', 'rate_includes_gst'],
                'sample' => ['Retail', 'PN-WIDGET-001', 'PCS', 150, 0, 0],
                'note' => 'Product must already exist. rate must be > 0. customer_type defaults to Retail and is auto-created when missing. rate_includes_gst: 1/0.',
            ],
            'leads' => [
                'label' => 'CRM Leads',
                'match' => 'mobile, else email',
                'required' => ['contact_name'],
                'columns' => [
                    'contact_name', 'company_name', 'title', 'email', 'secondary_email',
                    'mobile', 'second_mobile_number', 'phone', 'landline',
                    'sales_person', 'tag', 'sub_category',
                    'mailing_street', 'mailing_city', 'mailing_state', 'mailing_zip',
                    'lead_source', 'campaign', 'priority', 'status', 'interested_product', 'notes',
                ],
                'sample' => [
                    'Anand Verma', 'Apex Tech Solutions', 'Director', 'anand@apextech.in', 'billing@apextech.in',
                    '9820011223', '9820099887', '022-26543210', '022-26543211', 'Admin', 'VIP', 'Electronics',
                    'Plot 42, MIDC, Andheri East', 'Mumbai', 'Maharashtra', '400093',
                    'Website', 'Q1 Outreach', 'high', 'new', 'Widget X', 'Follow up next week',
                ],
                'note' => 'Required: contact_name. Dedupes on mobile/email when present. sales_person / lead_source / campaign match existing masters by name. priority: low|normal|high|urgent. status: new|contacted|qualified|unqualified|lost.',
            ],
            'phone-directory' => [
                'label' => 'Phone Directory',
                'match' => 'primary_mobile, else email',
                'required' => ['contact_name', 'primary_mobile'],
                'columns' => [
                    'contact_name', 'contact_type', 'designation', 'department', 'company_name', 'category',
                    'primary_mobile', 'alternate_mobile', 'office_phone', 'extension', 'whatsapp_number',
                    'email', 'alternate_email', 'country', 'state', 'city', 'area', 'address', 'pincode',
                    'is_important', 'status', 'notes',
                ],
                'sample' => [
                    'Rajesh Kumar Sharma', 'customer', 'Procurement Manager', 'Purchase', 'Apex Industries Pvt Ltd', 'Key Client',
                    '9876543210', '9876543211', '011-23456789', '104', '9876543210',
                    'rajesh.sharma@apexind.com', 'accounts@apexind.com', 'India', 'Maharashtra', 'Mumbai', 'Andheri East',
                    'Plot 45, MIDC Industrial Area', '400093', 1, 'active', 'Key procurement contact',
                ],
                'note' => 'Required: contact_name, primary_mobile. contact_type: employee|customer|supplier|dealer|distributor|other. Match mobile (else email). Same data is skipped; changed rows update. In-file duplicate mobiles are skipped.',
            ],
            'purchase-orders' => [
                'label' => 'Purchase Orders',
                'match' => 'company + po_no (fallback supplier + po_date)',
                'required' => ['po_date', 'supplier_code|supplier_gstin|supplier_name', 'part_number', 'quantity', 'unit_cost'],
                'columns' => [
                    'po_no', 'po_date', 'supplier_code', 'supplier_gstin', 'supplier_name',
                    'company_code', 'warehouse_code', 'expected_date', 'credit_days', 'notes', 'submit_for_approval',
                    'is_freight_applicable', 'freight_mode', 'freight_percent', 'freight_amount', 'freight_tax_rate', 'freight_notes',
                    'part_number', 'uom_code', 'quantity', 'unit_cost', 'tax_percent',
                    'cgst_percent', 'sgst_percent', 'igst_percent', 'mrp', 'purchase_price_inclusive',
                    'purchase_discount_percent', 'batch_no', 'serial_no', 'weight',
                ],
                'sample' => [
                    'PO-1001', '2026-04-05', 'PARTY-001', '29AABCU9603R1ZM', 'Sample Distributor',
                    'CMP-01', 'WH-01', '2026-04-20', 30, 'Imported PO', 0,
                    0, 'amount', '', 0, 18, '',
                    'PN-WIDGET-001', 'PCS', 10, 100, 18,
                    9, 9, '', 175, 118,
                    0, '', '', '',
                ],
                'note' => 'One row per line item (repeat header fields). Import Parties + Products first. Supplier match: code → GSTIN → name. Same PO is skipped; changed editable PO updates. Prefer po_no to avoid duplicates; blank po_no matches supplier+date when unique. submit_for_approval: 1/0.',
            ],
        ];
    }

    /**
     * @return array{label: string, match: string, required: list<string>, columns: list<string>, sample: list<string|int|float>, note: string}
     */
    public static function get(string $type): array
    {
        $all = self::all();

        if (! isset($all[$type])) {
            abort(404, 'Unknown template');
        }

        return $all[$type];
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::all());
    }
}
