<?php

namespace Tally\DataExchange\Import;

use Tally\DataExchange\Import\Definitions\AccountGroupImporter;
use Tally\DataExchange\Import\Definitions\BranchImporter;
use Tally\DataExchange\Import\Definitions\CompanyImporter;
use Tally\DataExchange\Import\Definitions\LedgerImporter;
use Tally\DataExchange\Import\Definitions\OpeningBalanceImporter;
use Tally\DataExchange\Import\Definitions\PartyImporter;
use Tally\DataExchange\Import\Definitions\ProductGroupImporter;
use Tally\DataExchange\Import\Definitions\ProductImporter;
use Tally\DataExchange\Import\Definitions\UnitImporter;
use InvalidArgumentException;

class ImportCatalog
{
    /**
     * @return list<class-string<ImportDefinition>>
     */
    public function classes(): array
    {
        return [
            CompanyImporter::class,
            BranchImporter::class,
            AccountGroupImporter::class,
            LedgerImporter::class,
            PartyImporter::class,
            UnitImporter::class,
            ProductGroupImporter::class,
            ProductImporter::class,
            OpeningBalanceImporter::class,
        ];
    }

    /**
     * @return list<ImportDefinition>
     */
    public function all(): array
    {
        return array_map(fn (string $class) => new $class, $this->classes());
    }

    public function find(string $key): ImportDefinition
    {
        foreach ($this->classes() as $class) {
            $definition = new $class;

            if ($definition->key() === $key) {
                return $definition;
            }
        }

        throw new InvalidArgumentException('Unknown import.');
    }
}
