<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>UTAS Paid Order</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Notifikasi Order Paid dari UTAS</h2>

    <p style="margin: 0 0 8px;"><strong>Store:</strong> {{ $payload['store'] ?? '-' }}</p>
    <p style="margin: 0 0 8px;"><strong>Nama:</strong> {{ $payload['name'] ?? '-' }}</p>
    <p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $payload['email'] ?? '-' }}</p>
    <p style="margin: 0 0 8px;"><strong>Alamat:</strong> {{ $payload['address'] ?? '-' }}</p>
    <p style="margin: 0 0 8px;"><strong>Total:</strong> {{ $payload['total'] ?? '-' }}</p>
    <p style="margin: 0 0 16px;"><strong>Store Link:</strong> {{ $payload['store_link'] ?? '-' }}</p>

    @if ($items !== [])
        <h3 style="margin: 16px 0 8px;">Items</h3>
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th align="left">Item</th>
                    <th align="left">Qty</th>
                    <th align="left">Harga</th>
                    <th align="left">Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item['item_name'] ?? '-' }}</td>
                        <td>{{ $item['item_qty'] ?? '-' }}</td>
                        <td>{{ $item['item_price'] ?? '-' }}</td>
                        <td>{{ $item['item_data'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
