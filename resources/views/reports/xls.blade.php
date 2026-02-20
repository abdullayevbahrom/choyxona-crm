<table border="1">
    <tr>
        <th colspan="3">Choyxona CRM Hisobot</th>
    </tr>
    <tr>
        <td>Yaratilgan vaqt</td>
        <td colspan="2">{{ now()->format('Y-m-d H:i:s') }}</td>
    </tr>
    <tr>
        <td>Sana oralig'i</td>
        <td colspan="2">{{ $filters['date_from'] ?? '-' }} - {{ $filters['date_to'] ?? '-' }}</td>
    </tr>
    <tr><td colspan="3"></td></tr>

    <tr>
        <th>Bo'lim</th>
        <th>Nom</th>
        <th>Qiymat</th>
    </tr>
    <tr>
        <td>Umumiy</td>
        <td>Jami daromad</td>
        <td>{{ (float) $reportData['totalRevenue'] }}</td>
    </tr>
    <tr>
        <td>Umumiy</td>
        <td>Yopilgan buyurtmalar</td>
        <td>{{ (int) $reportData['ordersCount'] }}</td>
    </tr>

    <tr><td colspan="3"></td></tr>
    <tr>
        <th colspan="3">Kunlik daromad</th>
    </tr>
    <tr>
        <th>Bo'lim</th>
        <th>Sana</th>
        <th>Daromad</th>
    </tr>
    @foreach ($reportData['dailyRevenue'] as $row)
        <tr>
            <td>Kunlik daromad</td>
            <td>{{ $row->day }}</td>
            <td>{{ (float) $row->revenue }}</td>
        </tr>
    @endforeach

    <tr><td colspan="3"></td></tr>
    <tr>
        <th colspan="3">Oylik daromad</th>
    </tr>
    <tr>
        <th>Bo'lim</th>
        <th>Oy</th>
        <th>Daromad</th>
    </tr>
    @foreach ($reportData['monthlyRevenue'] as $row)
        <tr>
            <td>Oylik daromad</td>
            <td>{{ $row->ym }}</td>
            <td>{{ (float) $row->revenue }}</td>
        </tr>
    @endforeach

    <tr><td colspan="3"></td></tr>
    <tr>
        <th colspan="3">TOP-10 mahsulot</th>
    </tr>
    <tr>
        <th>Nomi</th>
        <th>Soni</th>
        <th>Daromad</th>
    </tr>
    @foreach ($reportData['topItems'] as $row)
        <tr>
            <td>{{ $row->item_name }}</td>
            <td>{{ (int) $row->total_qty }}</td>
            <td>{{ (float) $row->revenue }}</td>
        </tr>
    @endforeach
</table>
