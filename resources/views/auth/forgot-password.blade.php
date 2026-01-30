<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lupa Password - MyRVM</title>
    <link rel="stylesheet" href="/vendor/assets/vendor/css/core.css" />
  </head>
  <body class="container py-6">
    <h3 class="mb-4">Lupa Password</h3>
    <p>Masukkan email Anda untuk menerima instruksi reset password (demo).</p>
    <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Demo: tidak mengirim email.');">
      <input type="email" class="form-control mb-3" placeholder="Email" required />
      <button class="btn btn-primary">Kirim</button>
      <a href="{{ route('login') }}" class="btn btn-link">Kembali ke Login</a>
    </form>
  </body>
</html>

