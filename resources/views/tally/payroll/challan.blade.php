<x-tally::layouts.app title="PF and ESI Challan">
    <x-tally::shell.page title="PF and ESI Challan" section="Transactions" :description="$company->name">
        <p class="form-note">Employee and employer PF and ESI come from processed payroll. A challan pays that statutory total from the bank into the deduction ledger.</p>
        @forelse ($runs as $run)
            @php
                $employeePf = $run->lines->sum(fn ($line) => (float) $line->pf_amount);
                $employerPf = $run->lines->sum(fn ($line) => (float) $line->employer_pf_amount);
                $employeeEsi = $run->lines->sum(fn ($line) => (float) $line->esi_amount);
                $employerEsi = $run->lines->sum(fn ($line) => (float) $line->employer_esi_amount);
                $total = $employeePf + $employerPf + $employeeEsi + $employerEsi;
            @endphp
            <h2>{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th></th><th class="money">Employee</th><th class="money">Employer</th></tr></thead>
                    <tbody>
                        <tr><td>PF</td><td class="money">{{ number_format($employeePf, 2, '.', '') }}</td><td class="money">{{ number_format($employerPf, 2, '.', '') }}</td></tr>
                        <tr><td>ESI</td><td class="money">{{ number_format($employeeEsi, 2, '.', '') }}</td><td class="money">{{ number_format($employerEsi, 2, '.', '') }}</td></tr>
                        <tr><th>Total</th><th colspan="2" class="money">{{ number_format($total, 2, '.', '') }}</th></tr>
                    </tbody>
                </table>
            </div>
            @if ($run->statutory_voucher_id)
                <p>Challan posted on voucher {{ $run->statutoryVoucher?->voucher_number }}.</p>
            @elseif ($total > 0 && $banks->isNotEmpty() && $run->deduction_ledger_id)
                <form class="panel" method="POST" action="{{ tally_route('books.tally.payroll.challan.store', $run) }}">
                    @csrf
                    <x-tally::form.field name="bank_ledger_id" label="Bank ledger" required>
                        <select id="bank_ledger_id" name="bank_ledger_id" class="input" required>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </x-form.field>
                    <button class="btn btn-primary" type="submit">Post challan</button>
                </form>
            @else
                <p>No PF or ESI to pay on this payroll.</p>
            @endif
        @empty
            <p>No processed payroll in this financial year.</p>
        @endforelse
        <p><a href="{{ tally_route('books.tally.payroll.report') }}">Payroll return</a></p>
    </x-shell.page>
</x-layouts.app>
