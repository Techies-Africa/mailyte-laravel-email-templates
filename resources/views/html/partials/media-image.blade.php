@if($props['image'])
    <img class="m-img-fill" src="{{ $props['image'] }}" alt="{{ $props['image_alt'] }}" width="{{ $props['image_width'] }}" style="display:block;border:0;width:100%;max-width:{{ $props['image_width'] }}px;height:auto;border-radius:{{ $props['radius'] }};">
@endif
