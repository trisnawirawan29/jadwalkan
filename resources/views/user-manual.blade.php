<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('Panduan lengkap menggunakan aplikasi.') }}">
    <title>{{ __('Panduan pengguna') }} · {{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <style>.manual-module-body{display:none!important}.manual-module.is-open .manual-module-body{display:block!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/user-manual.css') }}">
</head>
<body class="landing-page min-h-screen bg-slate-950 text-slate-100 antialiased">
    @include('partials.public-navbar', ['activePage' => 'manual'])

    <main class="mx-auto max-w-7xl px-5 pb-20 pt-32 lg:px-8">
        <section class="manual-hero overflow-hidden rounded-[30px] p-7 sm:p-10 lg:p-14">
            <div class="relative z-10 max-w-3xl">
                <span class="landing-kicker"><i class="fa-solid fa-book-open"></i>{{ __('Pusat bantuan') }}</span>
                <h1 class="mt-6 text-4xl font-extrabold tracking-[-.04em] text-white sm:text-6xl">{{ __('Panduan menggunakan aplikasi') }}</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">{{ __('Pelajari cara menemukan tempat, memilih jadwal, melakukan booking, mengirim bukti pembayaran, hingga melihat tiket booking Anda.') }}</p>
                <div class="mt-8 flex flex-wrap gap-3 text-xs font-bold text-indigo-100">
                    <span class="manual-pill"><i class="fa-solid fa-layer-group"></i> {{ __('8 modul pembelajaran') }}</span>
                    <span class="manual-pill"><i class="fa-solid fa-clock"></i> {{ __('Baca sekitar 5 menit') }}</span>
                </div>
            </div>
            <div class="manual-hero-orb"><i class="fa-solid fa-compass"></i></div>
        </section>

        <div class="mt-10 grid gap-6">
            <aside class="manual-toc">
                <p class="manual-overline">{{ __('Daftar modul') }}</p>
                <nav class="mt-4 grid gap-1">
                    @foreach ([['01', 'Memulai aplikasi', 'manual-start'], ['02', 'Menemukan tempat', 'manual-explore'], ['03', 'Profil dan lokasi', 'manual-profile'], ['04', 'Melakukan booking', 'manual-booking'], ['05', 'Pembayaran dan konfirmasi', 'manual-payment'], ['06', 'Detail, QR, dan hadir', 'manual-ticket'], ['07', 'Booking saya', 'manual-history'], ['08', 'Menjadi provider', 'manual-provider']] as [$number, $title, $anchor])
                        <a href="#{{ $anchor }}" class="manual-toc-link"><span>{{ $number }}</span>{{ __($title) }}</a>
                    @endforeach
                </nav>
            </aside>

            <div class="grid gap-3" data-manual-accordion>
                <article id="manual-start" class="manual-module is-open">
                    <button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="true"><span class="manual-module-heading"><span class="manual-module-number">01</span><span><span class="manual-overline">{{ __('Modul pertama') }}</span><span class="manual-module-title">{{ __('Memulai aplikasi') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button>
                    <div class="manual-module-body"><p>{{ __('Aplikasi ini membantu Anda menemukan tempat dan layanan, melihat ketersediaan jam, lalu melakukan booking secara online.') }}</p><div class="manual-steps"><div><b>1</b><strong>{{ __('Buka halaman utama') }}</strong><small>{{ __('Lihat ringkasan tempat aktif, kategori, peta sebaran, dan tombol Jelajahi.') }}</small></div><div><b>2</b><strong>{{ __('Daftar atau masuk') }}</strong><small>{{ __('Akun diperlukan sebelum jadwal dapat ditahan dan booking diproses.') }}</small></div><div><b>3</b><strong>{{ __('Lengkapi profil') }}</strong><small>{{ __('Tambahkan nomor telepon dan alamat agar informasi akun serta rekomendasi wilayah lebih akurat.') }}</small></div></div></div>
                </article>
                <article id="manual-explore" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">02</span><span><span class="manual-overline">{{ __('Modul eksplorasi') }}</span><span class="manual-module-title">{{ __('Menemukan tempat yang sesuai') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Gunakan menu Jelajahi untuk melihat seluruh tempat yang tersedia. Hasil pencarian dapat dipersempit berdasarkan wilayah dan kategori.') }}</p><ul class="manual-check-list"><li><i class="fa-solid fa-check"></i>{{ __('Pilih provinsi, kabupaten/kota, kecamatan, atau kategori.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Aktifkan rekomendasi lokasi untuk mengurutkan tempat terdekat.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Buka kartu tempat untuk melihat alamat, layanan, dan tombol Book now.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Gunakan peta untuk memahami sebaran lokasi dan membuka petunjuk arah.') }}</li></ul></div></article>
                <article id="manual-profile" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">03</span><span><span class="manual-overline">{{ __('Modul akun') }}</span><span class="manual-module-title">{{ __('Profil dan lokasi pengguna') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Profil membantu aplikasi mengenali Anda dan menampilkan tempat yang lebih relevan dengan wilayah Anda.') }}</p><div class="manual-info-grid"><div><i class="fa-solid fa-user"></i><strong>{{ __('Data pribadi') }}</strong><span>{{ __('Perbarui nama, nomor telepon, foto profil, dan kata sandi dari menu Profil.') }}</span></div><div><i class="fa-solid fa-map-location-dot"></i><strong>{{ __('Wilayah domisili') }}</strong><span>{{ __('Isi provinsi, kabupaten/kota, dan kecamatan untuk rekomendasi lokasi yang lebih tepat.') }}</span></div></div></div></article>
                <article id="manual-booking" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">04</span><span><span class="manual-overline">{{ __('Modul booking') }}</span><span class="manual-module-title">{{ __('Melakukan booking per jam') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Booking dilakukan dengan memilih tanggal dan satu atau beberapa slot jam yang tersedia pada jadwal layanan.') }}</p><div class="manual-timeline"><div><span>01</span><p><strong>{{ __('Pilih layanan') }}</strong><br>{{ __('Buka detail tempat, pilih layanan yang Anda inginkan, lalu tentukan tanggal booking.') }}</p></div><div><span>02</span><p><strong>{{ __('Pilih slot jam') }}</strong><br>{{ __('Slot mengikuti jadwal penyedia. Jam yang telah dipesan, sedang ditahan, atau sudah lewat tidak dapat dipilih.') }}</p></div><div><span>03</span><p><strong>{{ __('Tinjau biaya') }}</strong><br>{{ __('Harga dihitung otomatis berdasarkan harga per jam dan jumlah slot yang dipilih.') }}</p></div><div><span>04</span><p><strong>{{ __('Tahan jadwal') }}</strong><br>{{ __('Kirim booking untuk menahan jadwal sesuai durasi hold yang ditentukan provider, lalu lanjutkan ke pembayaran.') }}</p></div></div></div></article>
                <article id="manual-payment" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">05</span><span><span class="manual-overline">{{ __('Modul pembayaran') }}</span><span class="manual-module-title">{{ __('Pembayaran dan konfirmasi') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Setelah jadwal ditahan, buka detail booking untuk melihat instruksi pembayaran dari penyedia.') }}</p><ul class="manual-check-list"><li><i class="fa-solid fa-check"></i>{{ __('Pilih metode pembayaran yang tersedia, seperti transfer bank atau QRIS.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Lakukan pembayaran sebelum batas waktu hold berakhir.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Ambil foto atau snapshot bukti transfer dengan informasi yang terbaca.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Kirim bukti melalui tombol konfirmasi pembayaran.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Status berubah menjadi confirmed setelah bukti diverifikasi penyedia.') }}</li></ul></div></article>
                <article id="manual-ticket" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">06</span><span><span class="manual-overline">{{ __('Modul kehadiran') }}</span><span class="manual-module-title">{{ __('Detail booking, QR code, dan konfirmasi hadir') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Detail booking menyimpan informasi utama yang perlu Anda bawa saat datang ke tempat.') }}</p><div class="manual-info-grid"><div><i class="fa-solid fa-qrcode"></i><strong>{{ __('Tunjukkan QR code') }}</strong><span>{{ __('Buka detail booking dan tampilkan QR code kepada provider saat Anda tiba.') }}</span></div><div><i class="fa-solid fa-circle-check"></i><strong>{{ __('Konfirmasi kehadiran') }}</strong><span>{{ __('Provider memindai QR code untuk memeriksa data booking dan mencatat kehadiran.') }}</span></div></div></div></article>
                <article id="manual-history" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">07</span><span><span class="manual-overline">{{ __('Modul pengelolaan') }}</span><span class="manual-module-title">{{ __('Melihat booking saya') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Menu Booking saya menampilkan seluruh booking dalam format kalender atau list sesuai pilihan Anda.') }}</p><ul class="manual-check-list"><li><i class="fa-solid fa-check"></i>{{ __('Mode list menyediakan filter status dan urutan booking mendatang dari yang paling dekat.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Mode kalender menggabungkan booking mendatang dan riwayat dalam satu tampilan tanggal.') }}</li><li><i class="fa-solid fa-check"></i>{{ __('Klik booking untuk membuka detail, status pembayaran, total biaya, dan QR code.') }}</li></ul></div></article>
                <article id="manual-provider" class="manual-module"><button type="button" class="manual-module-toggle" data-manual-toggle aria-expanded="false"><span class="manual-module-heading"><span class="manual-module-number">08</span><span><span class="manual-overline">{{ __('Modul lanjutan') }}</span><span class="manual-module-title">{{ __('Mengajukan diri sebagai provider') }}</span></span></span><i class="fa-solid fa-chevron-down manual-module-chevron"></i></button><div class="manual-module-body"><p>{{ __('Jika Anda memiliki tempat atau bisnis, Anda dapat mengajukan permohonan sebagai provider dari dashboard pengguna.') }}</p><div class="manual-provider-callout"><i class="fa-solid fa-store"></i><div><strong>{{ __('Apa keuntungan menjadi provider?') }}</strong><span>{{ __('Tampilkan bisnis, kelola layanan dan jadwal, tentukan harga per jam, serta terima booking dari pelanggan.') }}</span></div><a href="{{ route('provider-application.create') }}" class="landing-primary-button">{{ __('Lihat pengajuan') }} <i class="fa-solid fa-arrow-right"></i></a></div></div></article>

                <div class="manual-footer-card"><div><p class="manual-overline">{{ __('Siap mulai?') }}</p><h2>{{ __('Temukan tempat dan buat booking pertama Anda.') }}</h2></div><a href="{{ route('places.index') }}" class="landing-primary-button">{{ __('Jelajahi tempat') }} <i class="fa-solid fa-arrow-right"></i></a></div>
            </div>
        </div>
    </main>
    <script>
        (() => {
            const accordion = document.querySelector('[data-manual-accordion]');
            if (!accordion) return;

            const openModule = module => {
                accordion.querySelectorAll('.manual-module').forEach(item => {
                    const isOpen = item === module;
                    item.classList.toggle('is-open', isOpen);
                    item.querySelector('[data-manual-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    const body = item.querySelector('.manual-module-body');
                    if (body) body.hidden = !isOpen;
                });
                document.querySelectorAll('.manual-toc-link').forEach(link => {
                    link.classList.toggle('is-active', module && link.getAttribute('href') === `#${module.id}`);
                });
            };

            accordion.querySelectorAll('[data-manual-toggle]').forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const module = toggle.closest('.manual-module');
                    openModule(module.classList.contains('is-open') ? null : module);
                });
            });

            document.querySelectorAll('.manual-toc-link').forEach(link => {
                link.addEventListener('click', event => {
                    const module = document.querySelector(link.getAttribute('href'));
                    if (!module) return;
                    event.preventDefault();
                    openModule(module);
                    module.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            openModule(accordion.querySelector('.manual-module.is-open'));
        })();
    </script>
</body>
</html>
