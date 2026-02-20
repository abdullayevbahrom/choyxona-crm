<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Hisobot</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1, h2 { margin: 0 0 8px 0; }
        h2 { margin-top: 18px; font-size: 13px; }
        p { margin: 0 0 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Choyxona CRM Hisobot</h1>
    <p>Yaratilgan vaqt: {{ now()->format('Y-m-d H:i:s') }}</p>
    <p>Sana oralig'i: {{ $filters['date_from'] ?? '-' }} - {{ $filters['date_to'] ?? '-' }}</p>

    <h2>Umumiy</h2>
    <table>
        <tr><th>Ko'rsatkich</th><th>Qiymat</th></tr>
        <tr><td>Jami daromad</td><td>{{ number_format((float) $reportData['totalRevenue'], 2) }}</td></tr>
        <tr><td>Yopilgan buyurtmalar</td><td>{{ (int) $reportData['ordersCount'] }}</td></tr>
    </table>

    <h2>Kunlik daromad</h2>
    <table>
        <tr><th>Sana</th><th>Daromad</th></tr>
        @forelse($reportData['dailyRevenue'] as $row)
            <tr><td>{{ $row->day }}</td><td>{{ number_format((float) $row->revenue, 2) }}</td></tr>
        @empty
            <tr><td colspan="2">Ma'lumot yo'q</td></tr>
        @endforelse
    </table>

    <h2>Oylik daromad</h2>
    <table>
        <tr><th>Oy</th><th>Daromad</th></tr>
        @forelse($reportData['monthlyRevenue'] as $row)
            <tr><td>{{ $row->ym }}</td><td>{{ number_format((float) $row->revenue, 2) }}</td></tr>
        @empty
            <tr><td colspan="2">Ma'lumot yo'q</td></tr>
        @endforelse
    </table>

    <h2>TOP-10 mahsulot</h2>
    <table>
        <tr><th>Nomi</th><th>Soni</th><th>Daromad</th></tr>
        @forelse($reportData['topItems'] as $row)
            <tr>
                <td>{{ $row->item_name }}</td>
                <td>{{ (int) $row->total_qty }}</td>
                <td>{{ number_format((float) $row->revenue, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Ma'lumot yo'q</td></tr>
        @endforelse
    </table>
</body>
</html>
