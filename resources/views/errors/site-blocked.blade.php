<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Site Unavailable</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000000;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255,255,255,0.03) 2px, rgba(255,255,255,0.03) 4px),
                repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(255,255,255,0.03) 2px, rgba(255,255,255,0.03) 4px);
            animation: slidePattern 20s linear infinite;
        }

        @keyframes slidePattern {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .container {
            background: #ffffff;
            border: 3px solid #000000;
            padding: 60px 40px;
            max-width: 650px;
            width: 100%;
            box-shadow: 
                20px 20px 0 rgba(0, 0, 0, 1),
                20px 20px 0 2px rgba(255, 255, 255, 0.1);
            text-align: center;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .illustration {
            margin-bottom: 40px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .illustration svg {
            width: 200px;
            height: 200px;
        }

        h1 {
            color: #000000;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -1px;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        p {
            color: #333333;
            font-size: 18px;
            line-height: 1.8;
            max-width: 500px;
            margin: 0 auto;
            font-weight: 400;
            animation: fadeIn 0.8s ease-out 0.4s both;
        }

        .decorative-line {
            width: 60px;
            height: 4px;
            background: #000000;
            margin: 30px auto;
            animation: expandLine 0.6s ease-out 0.6s both;
        }

        @keyframes expandLine {
            from { width: 0; }
            to { width: 60px; }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                padding: 40px 30px;
                box-shadow: 
                    15px 15px 0 rgba(0, 0, 0, 1),
                    15px 15px 0 2px rgba(255, 255, 255, 0.1);
            }

            h1 {
                font-size: 32px;
            }

            p {
                font-size: 16px;
            }

            .illustration svg {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="illustration">
            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Shield outline -->
                <path d="M100 20 L160 40 L160 90 Q160 140 100 180 Q40 140 40 90 L40 40 Z" 
                      stroke="#000000" stroke-width="4" fill="#ffffff"/>
                
                <!-- Inner shield pattern -->
                <path d="M100 35 L145 50 L145 90 Q145 130 100 162 Q55 130 55 90 L55 50 Z" 
                      fill="#000000"/>
                
                <!-- Lock body -->
                <rect x="85" y="95" width="30" height="35" rx="3" fill="#ffffff" stroke="#000000" stroke-width="2"/>
                
                <!-- Lock shackle -->
                <path d="M90 95 L90 80 Q90 70 100 70 Q110 70 110 80 L110 95" 
                      stroke="#ffffff" stroke-width="6" fill="none" stroke-linecap="round"/>
                
                <!-- Keyhole -->
                <circle cx="100" cy="107" r="4" fill="#000000"/>
                <rect x="98" y="107" width="4" height="10" fill="#000000"/>
                
                <!-- Decorative dots -->
                <circle cx="70" cy="65" r="3" fill="#ffffff"/>
                <circle cx="130" cy="65" r="3" fill="#ffffff"/>
                <circle cx="70" cy="115" r="3" fill="#ffffff"/>
                <circle cx="130" cy="115" r="3" fill="#ffffff"/>
                
                <!-- Warning stripes -->
                <line x1="60" y1="140" x2="75" y2="155" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
                <line x1="125" y1="155" x2="140" y2="140" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>

        <h1>Site Unavailable</h1>
        
        <div class="decorative-line"></div>
        
        <p>{{ $message ?? 'The website is currently unavailable. Please try again later.' }}</p>
    </div>
</body>

</html>
