<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-amber-50 text-gray-800">

    <x-layouts::app.header>
        {{ $slot ?? '' }}
    </x-layouts::app.header>

</body>
</html>
