<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unsubscribed</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body class="bg-body">
    <div class="page">
        <div class="container-xl py-5">
            <div class="card card-md mx-auto" style="max-width: 540px;">
                <div class="card-body text-center py-5">
                    <h2 class="mb-2">Berhasil berhenti berlangganan</h2>
                    <p class="text-muted mb-0">Kami tidak akan mengirim email campaign ke {{ $recipient->recipient_email }} lagi.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
