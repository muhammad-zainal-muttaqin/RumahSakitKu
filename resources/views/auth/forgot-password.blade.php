<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h1>Forgot Password</h1>
    <form method="POST" action="/forgot-password">
        @csrf
        <input type="email" name="email" placeholder="Email" required>

        <button type="submit">Send Reset Link</button>
    </form>
</body>
</html>
