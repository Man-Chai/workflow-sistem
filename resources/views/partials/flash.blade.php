@if(session('success'))
    <div class="alert alert-success" role="status">
        <x-icon name="circle-check" :size="18" />
        <div>{{ session('success') }}</div>
        <button type="button" class="alert-close" data-dismiss-alert aria-label="Tutup">
            <x-icon name="x" :size="15" />
        </button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info" role="status">
        <x-icon name="info" :size="18" />
        <div>{{ session('info') }}</div>
        <button type="button" class="alert-close" data-dismiss-alert aria-label="Tutup">
            <x-icon name="x" :size="15" />
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error" role="alert">
        <x-icon name="alert" :size="18" />
        <div>{{ session('error') }}</div>
        <button type="button" class="alert-close" data-dismiss-alert aria-label="Tutup">
            <x-icon name="x" :size="15" />
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <x-icon name="alert" :size="18" />
        <div>
            <strong>Periksa kembali data berikut:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="alert-close" data-dismiss-alert aria-label="Tutup">
            <x-icon name="x" :size="15" />
        </button>
    </div>
@endif
