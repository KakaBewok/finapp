<table>
    <thead>
        <tr>
            <th colspan="4" style="text-align: center; font-size: 14px; font-weight: bold;">
                {{ __('Laporan Keuangan') }} ({{ $reportType === 'monthly' ? __('Bulanan') : __('Tahunan') }})
            </th>
        </tr>
        <tr>
            <th colspan="4" style="text-align: center;">
                {{ __('Periode') }}: {{ $period }}
            </th>
        </tr>
        <tr>
            <th colspan="4"></th>
        </tr>
        <tr>
            <th colspan="2" style="font-weight: bold;">{{ __('Ringkasan') }}</th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">{{ __('Total Pemasukan') }}</td>
            <td colspan="2">Rp {{ number_format($data['totalIncome'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2">{{ __('Total Pengeluaran') }}</td>
            <td colspan="2">Rp {{ number_format($data['totalExpense'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">{{ __('Saldo') }}</td>
            <td colspan="2" style="font-weight: bold;">Rp {{ number_format($data['balance'], 0, ',', '.') }}</td>
        </tr>
        
        <tr><td colspan="4"></td></tr>
        
        <tr>
            <th colspan="4" style="font-weight: bold;">{{ __('Rincian per Kategori') }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; border-bottom: 1px solid #000;">{{ __('Kategori') }}</th>
            <th style="font-weight: bold; border-bottom: 1px solid #000;">{{ __('Tipe') }}</th>
            <th colspan="2" style="font-weight: bold; border-bottom: 1px solid #000; text-align: right;">{{ __('Total') }}</th>
        </tr>
        @foreach($data['categoryBreakdown'] as $category)
            <tr>
                <td>{{ $category['category_name'] }}</td>
                <td>{{ $category['type'] === 'income' ? __('Pemasukan') : __('Pengeluaran') }}</td>
                <td colspan="2" style="text-align: right;">Rp {{ number_format($category['total'], 0, ',', '.') }}</td>
            </tr>
        @endforeach

        <tr><td colspan="4"></td></tr>

        <tr>
            <th colspan="4" style="font-weight: bold;">{{ __('Riwayat Transaksi') }}</th>
        </tr>
        <tr>
            <th style="font-weight: bold; border-bottom: 1px solid #000;">{{ __('Tanggal') }}</th>
            <th style="font-weight: bold; border-bottom: 1px solid #000;">{{ __('Deskripsi') }} / {{ __('Merchant') }}</th>
            <th style="font-weight: bold; border-bottom: 1px solid #000;">{{ __('Kategori') }}</th>
            <th style="font-weight: bold; border-bottom: 1px solid #000; text-align: right;">{{ __('Jumlah') }}</th>
        </tr>
        @foreach($data['transactions'] as $tx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}</td>
                <td>{{ $tx->description ?? $tx->merchant ?? '-' }}</td>
                <td>{{ $tx->category ? $tx->category->name : __('Tanpa Kategori') }}</td>
                <td style="text-align: right; color: {{ $tx->type === 'income' ? 'green' : 'red' }}">
                    {{ $tx->type === 'income' ? '+' : '-' }}Rp {{ number_format($tx->amount, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
