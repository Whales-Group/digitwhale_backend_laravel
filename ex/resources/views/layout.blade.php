<!DOCTYPE html>
<html>

<head>
    <title>
        <center>{{ $title ?? 'Notification' }}</center>
    </title>
    <style>
        /* Base Styles */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #fff;
            /* White background */
            margin: 0;
            padding: 0;
            color: #333;
            /* Dark text */
        }

        /* Container Styles */
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            background-color: #f9f9f9;
            /* Light gray background */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            /* Subtle shadow */
        }

        /* Responsive Styles */
        @media only screen and (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .content h1,
            .content h2,
            .content h3 {
                font-size: 20px;
            }
        }

        /* Header Styles */
        .header {
            padding: 20px 0;
            display: flex;
            /* Flexible layout */
            align-items: center;
            /* Center logo and title vertically */
            justify-content: space-between;
            /* Space between logo and title */
        }

        .header img {
            max-width: 120px;
            height: auto;
            /* Maintain aspect ratio */
        }

        .header h1 {
            font-size: 22px;
            margin: 0;
            font-weight: normal;
            color: #24292e;
            /* Deep gray for title */
        }

        /* Content Styles */
        .content {
            padding: 20px;
            color: #666;
            /* Lighter text */
            line-height: 1.6;
        }

        .content h1,
        .content h2,
        .content h3 {
            color: #24292e;
            margin-top: 0;
        }

        .content p {
            margin-bottom: 15px;
        }

        /* Button Styles */
        .button {
            display: inline-block;
            padding: 12px 30px;
            margin-top: 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            /* Primary color (blue) */
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0069d9;
            /* Blue hover color */
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            /* Light border */
            color: #999;
            font-size: 12px;
            background-color: #f5f5f5;
            /* Very light gray background */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            {{-- <img src="{{ $logoUrl ?? asset('images/logo.png') }}" alt="Company Logo">--}}
            <h1>{{ $title ?? 'Notification' }}</h1>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Whales Group. All rights reserved.
        </div>
    </div>
</body>

</html>