<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>O1 Airlines - Under Construction</title>
    <link rel="stylesheet" href="PA1/css/global.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }

        .construction-card {
            background: var(--obsidian);
            border: 2px solid var(--primary-gold);
            border-radius: 15px;
            padding: 50px;
            max-width: 500px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(197, 160, 89, 0.1);
        }

        .icon {
            font-size: 72px;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 15px rgba(197, 160, 89, 0.4));
        }

        h1 {
            color: var(--primary-gold);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 15px;
            margin-top: 0;
        }

        p {
            color: var(--ice-white);
            font-size: 1.1rem;
            margin-bottom: 35px;
            line-height: 1.5;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            color: var(--obsidian);
            background-color: var(--primary-gold);
            padding: 15px 35px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: var(--ice-white);
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(250, 249, 246, 0.2);
        }
    </style>
</head>
<body>
    <div class="construction-card">
        <div class="icon">🚧</div>
        <h1>Under Construction</h1>
        <p>Our engineers are currently upgrading this sector of the application. Please check back later!</p>
        <a href="index.php" class="back-btn">Return to Base</a>
    </div>
</body>
</html>