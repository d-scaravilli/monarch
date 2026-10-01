@if ($errors->any())
    <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-100 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
        <p class="font-medium">Controlla i dati:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach (collect($errors->all())->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
