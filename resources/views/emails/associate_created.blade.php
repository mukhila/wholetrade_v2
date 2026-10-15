<!DOCTYPE html>
<html>
<head>
    <title>Account Created</title>
</head>
<body>
    <h1>Welcome, {{ $associate->name }}!</h1>
    <p>Your account has been created successfully.</p>
    <p><strong>Code:</strong> {{ $associate->code }}</p>
    <p><strong>Login Email:</strong> {{ $associate->email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>
        <a href="{{ route('login') }}">Click here to Login</a>
    </p>
    <p>Thank you!</p>
</body>
</html>
