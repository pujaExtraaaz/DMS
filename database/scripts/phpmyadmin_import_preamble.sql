-- Prepend this to any DMS SQL dump before importing in phpMyAdmin.
-- Prevents MySQL #1452 failures when child tables (e.g. accounting_company_links)
-- appear before parent rows in companies / acct_companies.
--
-- After a failed/partial import, also run:
--   php artisan accounting:repair-company-links

SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET AUTOCOMMIT = 0;

-- >>> paste dump body here <<<

COMMIT;
SET UNIQUE_CHECKS = 1;
SET FOREIGN_KEY_CHECKS = 1;
SET AUTOCOMMIT = 1;

-- Optional cleanup if dump still left orphans:
-- DELETE acl FROM accounting_company_links acl
-- LEFT JOIN companies c ON c.id = acl.organization_company_id
-- WHERE c.id IS NULL;
--
-- DELETE acl FROM accounting_company_links acl
-- LEFT JOIN acct_companies ac ON ac.id = acl.acct_company_id
-- WHERE ac.id IS NULL;
