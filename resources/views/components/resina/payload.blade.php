@props(['id', 'data'])

{{-- Page data for resources/js/resina, read once by the Alpine component. --}}
<script type="application/json" id="{{ $id }}">@json($data)</script>
