<!doctype html>
<html>
<head>
    <title>Email Already Verified</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-xl text-center">
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Already Verified</h1>
        <p class="text-gray-600">The email <strong>{{ $email ?? '' }}</strong> was already verified earlier.</p>
    </div>
</body>
</html>
