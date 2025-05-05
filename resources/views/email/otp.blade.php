<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>OTP Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f4f4f4;
        }
        .otp-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
        }
        .otp-code {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            letter-spacing: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
   <div class="otp-container">
       <p>Your OTP Code:</p>
       <p class="otp-code">{{ $otp }}</p>
   </div>
</body>
</html>
