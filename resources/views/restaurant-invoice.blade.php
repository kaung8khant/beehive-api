<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style>
        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="center">
        <img src="{{ public_path() . '/beehive-logo.png' }}" alt="restaurant-logo" width="100px">
    </div>

    <h2 class="center">{{ $branchInfo['restaurant']['name'] }} ({{ $branchInfo['name'] }})</h2>
    <p class="center">
        {{ $branchInfo['address'] }}
        <br>
        {{ $branchInfo['contact_number'] }}
    </p>

    <hr>
</body>

</html>