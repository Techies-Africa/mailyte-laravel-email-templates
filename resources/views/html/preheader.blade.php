{{-- Hidden inbox preview text. The zero-width spaces stop clients from
     spilling body copy into the preview when the preheader is short. --}}
@if($props['text'] !== '')
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
    {{ $props['text'] }}
    @for($i = 0; $i < 60; $i++)&#847;&zwnj;&nbsp;@endfor
</div>
@endif
