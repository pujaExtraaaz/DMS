@if ($rows === [])
    <p class="muted"></p>
@else
    @php
        $values = array_map(fn (array $row) => (float) str_replace(',', '', $row['total']), $rows);
        $max = max($values) ?: 1;
        $width = 220;
        $height = 72;
        $step = count($values) > 1 ? $width / (count($values) - 1) : 0;
        $points = [];

        foreach ($values as $index => $value) {
            $x = round($index * $step, 1);
            $y = round($height - (($value / $max) * ($height - 8)) - 4, 1);
            $points[] = $x.','.$y;
        }
    @endphp
    <svg class="trend" viewBox="0 0 {{ $width }} {{ $height }}" role="img">
        <polyline points="{{ implode(' ', $points) }}"></polyline>
    </svg>
    <table class="data">
        <tbody>
            @foreach ($rows as $row)
                <tr><td>{{ $row['period'] }}</td><td class="money">{{ $row['total'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif
