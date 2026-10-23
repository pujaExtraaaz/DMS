<?php

namespace App\Domains\TallySync\Templates;

class XmlHelpers
{
    public static function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES);
    }

    public static function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    public static function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    public static function ledgerEntry(
        string $ledger,
        float $signedAmount,
        bool $isParty = false,
        string $billAllocation = '',
        string $tag = 'LEDGERENTRIES.LIST',
        ?string $costCentre = null,
        ?string $narration = null,
        ?string $costCategory = null,
    ): string {
        $name = self::xml($ledger);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $partyFlag = $isParty ? 'Yes' : 'No';
        $amount = self::money($signedAmount);
        $category = self::xml($costCategory ?: 'Primary Cost Category');
        $cost = $costCentre
            ? '<CATEGORYALLOCATIONS.LIST><CATEGORY>'.$category.'</CATEGORY><COSTCENTREALLOCATIONS.LIST><NAME>'.self::xml($costCentre).'</NAME><AMOUNT>'.$amount.'</AMOUNT></COSTCENTREALLOCATIONS.LIST></CATEGORYALLOCATIONS.LIST>'
            : '';
        $note = trim((string) $narration);
        $noteXml = $note !== '' ? '<NARRATION>'.self::xml($note).'</NARRATION>' : '';

        return '<'.$tag.'>'
            .'<LEDGERNAME>'.$name.'</LEDGERNAME>'
            .'<ISDEEMEDPOSITIVE>'.$deemed.'</ISDEEMEDPOSITIVE>'
            .'<ISPARTYLEDGER>'.$partyFlag.'</ISPARTYLEDGER>'
            .'<AMOUNT>'.$amount.'</AMOUNT>'
            .$billAllocation
            .$cost
            .$noteXml
            .'</'.$tag.'>';
    }

    public static function billAllocation(string $name, string $type, float $signedAmount): string
    {
        $billName = self::xml($name);
        $amount = self::money($signedAmount);

        return '<BILLALLOCATIONS.LIST><NAME>'.$billName.'</NAME><BILLTYPE>'.self::xml($type).'</BILLTYPE><AMOUNT>'.$amount.'</AMOUNT></BILLALLOCATIONS.LIST>';
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function inventoryEntry(
        string $stockItem,
        float $qty,
        string $unit,
        float $rate,
        float $signedAmount,
        string $godown,
        string $ledger,
        string $batch = 'Primary Batch',
        array $extra = [],
    ): string {
        $item = self::xml($stockItem);
        $deemed = $signedAmount < 0 ? 'Yes' : 'No';
        $amount = self::money($signedAmount);
        $unitXml = self::xml($unit);
        $qtyText = self::xml(trim(self::quantity(abs($qty)).' '.$unit));
        $rateText = self::xml(number_format($rate, 2, '.', '').($unit !== '' ? '/'.$unit : ''));
        $godownXml = self::xml($godown);
        $batchXml = self::xml($batch);
        $ledgerXml = self::xml($ledger);
        $discount = (float) ($extra['discount'] ?? 0);
        $discountXml = $discount > 0 ? '<DISCOUNT>'.self::money($discount).'</DISCOUNT>' : '';
        $destination = trim((string) ($extra['destination_godown'] ?? ''));
        $destinationXml = $destination !== '' ? '<DESTINATIONGODOWNNAME>'.self::xml($destination).'</DESTINATIONGODOWNNAME>' : '';
        $mfg = self::ymd($extra['manufactured_on'] ?? null);
        $exp = self::ymd($extra['expires_on'] ?? null);
        $mfgXml = $mfg !== '' ? '<MFDON>'.$mfg.'</MFDON>' : '';
        $expXml = $exp !== '' ? '<EXPIRYPERIOD>'.$exp.'</EXPIRYPERIOD>' : '';

        return '<ALLINVENTORYENTRIES.LIST>'
            .'<STOCKITEMNAME>'.$item.'</STOCKITEMNAME>'
            .'<ISDEEMEDPOSITIVE>'.$deemed.'</ISDEEMEDPOSITIVE>'
            .'<RATE>'.$rateText.'</RATE>'
            .$discountXml
            .'<AMOUNT>'.$amount.'</AMOUNT>'
            .'<ACTUALQTY>'.$qtyText.'</ACTUALQTY>'
            .'<BILLEDQTY>'.$qtyText.'</BILLEDQTY>'
            .$destinationXml
            .'<BATCHALLOCATIONS.LIST>'
            .'<GODOWNNAME>'.$godownXml.'</GODOWNNAME>'
            .'<BATCHNAME>'.$batchXml.'</BATCHNAME>'
            .'<AMOUNT>'.$amount.'</AMOUNT>'
            .'<ACTUALQTY>'.$qtyText.'</ACTUALQTY>'
            .'<BILLEDQTY>'.$qtyText.'</BILLEDQTY>'
            .$mfgXml.$expXml
            .'</BATCHALLOCATIONS.LIST>'
            .'<ACCOUNTINGALLOCATIONS.LIST>'
            .'<LEDGERNAME>'.$ledgerXml.'</LEDGERNAME>'
            .'<ISDEEMEDPOSITIVE>'.$deemed.'</ISDEEMEDPOSITIVE>'
            .'<AMOUNT>'.$amount.'</AMOUNT>'
            .'</ACCOUNTINGALLOCATIONS.LIST>'
            .'</ALLINVENTORYENTRIES.LIST>';
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public static function accountingEntries(array $lines, string $tag = 'ALLLEDGERENTRIES.LIST'): string
    {
        $xml = '';

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            $signed = $debit > 0 ? -$debit : $credit;
            $bill = '';
            $bills = $line['bills'] ?? [];

            if ($bills === [] && ! empty($line['bill_name'])) {
                $bills = [[
                    'name' => $line['bill_name'],
                    'type' => $line['bill_type'] ?? 'New Ref',
                    'amount' => abs($signed),
                ]];
            }

            foreach ($bills as $allocation) {
                $billAmount = abs((float) ($allocation['amount'] ?? abs($signed)));
                $bill .= self::billAllocation(
                    (string) ($allocation['name'] ?? ''),
                    (string) ($allocation['type'] ?? 'New Ref'),
                    $signed < 0 ? -$billAmount : $billAmount,
                );
            }

            $xml .= self::ledgerEntry(
                (string) $line['ledger'],
                $signed,
                (bool) ($line['is_party'] ?? false),
                $bill.self::bankAllocation($line, $signed),
                $tag,
                isset($line['cost_centre']) ? (string) $line['cost_centre'] : null,
                isset($line['narration']) ? (string) $line['narration'] : null,
                isset($line['cost_category']) ? (string) $line['cost_category'] : null,
            );
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function voucherBody(
        string $remoteId,
        string $voucherType,
        string $dateYmd,
        string $voucherNo,
        string $entries,
        string $action = 'Create',
        ?string $party = null,
        ?string $reference = null,
        ?string $narration = null,
        bool $isInvoice = false,
        array $extra = [],
    ): string {
        $remote = self::xml($remoteId);
        $vchType = self::xml($voucherType);
        $number = self::xml($voucherNo);
        $partyXml = self::xml($party ?? '');
        $view = $isInvoice ? 'Invoice Voucher View' : 'Accounting Voucher View';
        $invoiceFlag = $isInvoice ? 'Yes' : 'No';
        $effective = self::ymd($extra['effective_date'] ?? null) ?: $dateYmd;
        $referenceDate = self::ymd($extra['reference_date'] ?? null);
        $referenceXml = $reference ? '<REFERENCE>'.self::xml($reference).'</REFERENCE>' : '';
        $referenceDateXml = $referenceDate !== '' ? '<REFERENCEDATE>'.$referenceDate.'</REFERENCEDATE>' : '';
        $place = trim((string) ($extra['place_of_supply'] ?? ''));
        $placeXml = $place !== '' ? '<PLACEOFSUPPLY>'.self::xml($place).'</PLACEOFSUPPLY><STATENAME>'.self::xml($place).'</STATENAME>' : '';
        $gstin = trim((string) ($extra['party_gstin'] ?? ''));
        $gstinXml = $gstin !== '' ? '<PARTYGSTIN>'.self::xml($gstin).'</PARTYGSTIN>' : '';
        $gstType = trim((string) ($extra['gst_registration_type'] ?? ''));
        $gstTypeXml = $gstType !== '' ? '<GSTREGISTRATIONTYPE>'.self::xml($gstType).'</GSTREGISTRATIONTYPE>' : '';
        $optional = ! empty($extra['is_optional']) ? 'Yes' : 'No';
        $postDated = ! empty($extra['is_post_dated']) ? 'Yes' : 'No';
        $memo = ! empty($extra['is_memo']) ? 'Yes' : 'No';
        $receiptName = trim((string) ($extra['name_on_receipt'] ?? ''));
        $receiptXml = $receiptName !== '' ? '<BASICBUYERNAME>'.self::xml($receiptName).'</BASICBUYERNAME>' : '';
        $nature = trim((string) ($extra['nature_of_payment'] ?? ''));
        $natureXml = $nature !== '' ? '<VCHENTRYMODE>'.self::xml($nature).'</VCHENTRYMODE>' : '';
        $reverses = self::ymd($extra['reverses_on'] ?? null);
        $reverseXml = $reverses !== '' ? '<REVERSALDATE>'.$reverses.'</REVERSALDATE>' : '';
        $rate = trim((string) ($extra['exchange_rate'] ?? ''));
        $currency = trim((string) ($extra['currency'] ?? ''));
        $fxXml = ($rate !== '' && (float) $rate > 0)
            ? '<CURRENCYNAME>'.self::xml($currency !== '' ? $currency : 'INR').'</CURRENCYNAME><EXCHANGERATE>'.self::xml($rate).'</EXCHANGERATE>'
            : '';
        $foreign = trim((string) ($extra['foreign_total'] ?? ''));
        $foreignXml = $foreign !== '' ? '<FOREIGNAMOUNT>'.self::xml($foreign).'</FOREIGNAMOUNT>' : '';
        $narrationXml = self::xml($narration ?? 'DMS '.$voucherType.' '.$voucherNo);
        $partyBlock = $party
            ? '<PARTYLEDGERNAME>'.$partyXml.'</PARTYLEDGERNAME><PARTYNAME>'.$partyXml.'</PARTYNAME>'
            : '';
        $inventory = trim((string) ($extra['inventory'] ?? ''));

        return '<VOUCHER REMOTEID="'.$remote.'" VCHTYPE="'.$vchType.'" ACTION="'.self::xml($action).'" OBJVIEW="'.$view.'">'
            .'<DATE>'.$dateYmd.'</DATE>'
            .'<EFFECTIVEDATE>'.$effective.'</EFFECTIVEDATE>'
            .'<VOUCHERTYPENAME>'.$vchType.'</VOUCHERTYPENAME>'
            .'<VOUCHERNUMBER>'.$number.'</VOUCHERNUMBER>'
            .$referenceXml.$referenceDateXml.$partyBlock.$gstinXml.$gstTypeXml.$placeXml
            .'<PERSISTEDVIEW>'.$view.'</PERSISTEDVIEW>'
            .'<ISINVOICE>'.$invoiceFlag.'</ISINVOICE>'
            .'<ISOPTIONAL>'.$optional.'</ISOPTIONAL>'
            .'<ISPOSTDATED>'.$postDated.'</ISPOSTDATED>'
            .'<ISMEMO>'.$memo.'</ISMEMO>'
            .$receiptXml.$natureXml.$reverseXml.$fxXml.$foreignXml
            .'<NARRATION>'.$narrationXml.'</NARRATION>'
            .$inventory.$entries
            .'</VOUCHER>';
    }

    public static function ymd(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Ymd');
        }

        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        return strlen($digits) >= 8 ? substr($digits, 0, 8) : '';
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function bankAllocation(array $line, float $signedAmount): string
    {
        $type = trim((string) ($line['bank_transaction_type'] ?? ''));
        if ($type === '' || strcasecmp($type, 'none') === 0) {
            return '';
        }

        $known = [
            'cheque' => 'Cheque',
            'check' => 'Cheque',
            'e-fund transfer' => 'e-Fund Transfer',
            'neft' => 'e-Fund Transfer',
            'rtgs' => 'e-Fund Transfer',
            'imps' => 'e-Fund Transfer',
            'cash' => 'Cash',
            'others' => 'Others',
            'other' => 'Others',
        ];
        $tallyType = $known[strtolower($type)] ?? $type;
        $date = self::ymd($line['instrument_date'] ?? null);
        $blocks = [
            '<TRANSACTIONTYPE>'.self::xml($tallyType).'</TRANSACTIONTYPE>',
            '<AMOUNT>'.self::money($signedAmount).'</AMOUNT>',
        ];

        if ($date !== '') {
            $blocks[] = '<DATE>'.$date.'</DATE>';
            $blocks[] = '<INSTRUMENTDATE>'.$date.'</INSTRUMENTDATE>';
        }

        foreach ([
            'instrument_number' => 'INSTRUMENTNUMBER',
            'payment_favouring' => 'PAYMENTFAVOURING',
            'bank_account_number' => 'ACCOUNTNUMBER',
            'bank_ifsc' => 'IFSCODE',
        ] as $key => $tag) {
            $value = trim((string) ($line[$key] ?? ''));
            if ($value !== '') {
                $blocks[] = '<'.$tag.'>'.self::xml($value).'</'.$tag.'>';
            }
        }

        $bankName = trim((string) ($line['bank_name'] ?? ''));
        if ($bankName !== '') {
            $blocks[] = '<BANKNAME>'.self::xml($bankName).'</BANKNAME>';
        }

        return '<BANKALLOCATIONS.LIST>'.implode('', $blocks).'</BANKALLOCATIONS.LIST>';
    }
}
