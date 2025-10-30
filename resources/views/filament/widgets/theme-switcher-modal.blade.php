<div class="grid grid-cols-2 gap-3 p-4">
    @foreach($actions as $action)
        <div class="flex justify-center">
            {{ $action }}
        </div>
    @endforeach
</div>

<script>

    document.addEventListener('livewire:initialized', function () {
        @this.on('themeChanged', () => {
            window.location.reload();
        });
    });
</script>