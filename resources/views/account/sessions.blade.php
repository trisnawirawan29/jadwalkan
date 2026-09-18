@extends('layouts.admin')

@section('title', 'Session Management')
@section('page-title', 'Session Management')
@section('page-subtitle', 'Lihat dan hentikan sesi yang sedang aktif.')

@section('content')
    <div class="content-card">
        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <div class="card-heading">
            <div><h5>Sesi aktif</h5><p>Perangkat yang sedang login ke akun Anda.</p></div>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#revokeModal">Hentikan sesi lain</button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>PERANGKAT / IP</th><th>AKTIVITAS TERAKHIR</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>
                                <strong>{{ $session->user_agent ? \Illuminate\Support\Str::limit($session->user_agent, 70) : 'Perangkat tidak dikenal' }}</strong>
                                <small class="d-block text-muted">{{ $session->ip_address }}
                                    @if ($session->id === request()->session()->getId())
                                        · <span class="text-success">Sesi ini</span>
                                    @endif
                                </small>
                            </td>
                            <td class="text-muted">{{ date('d M Y H:i', $session->last_activity) }}</td>
                            <td class="text-end">
                                @if ($session->id !== request()->session()->getId())
                                    <form method="POST" action="{{ route('sessions.revoke', $session->id) }}" data-confirm="Hentikan sesi pada perangkat ini?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light text-danger">Hentikan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Belum ada sesi tersimpan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="revokeModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="{{ route('sessions.revoke-others') }}" data-confirm="Hentikan semua sesi pada perangkat lain?">
                @csrf @method('DELETE')
                <div class="modal-header"><h5 class="modal-title">Hentikan sesi lain</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><label class="form-label" for="session-password">Konfirmasi password</label><input id="session-password" type="password" name="password" class="form-control" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Hentikan semua</button></div>
            </form>
        </div></div>
    </div>
@endsection
