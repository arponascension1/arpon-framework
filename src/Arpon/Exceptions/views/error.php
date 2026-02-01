<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $code ?? 'Error'; ?> - Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .error-container {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .error-code {
            font-size: 72px;
            font-weight: 300;
            color: #dc3545;
            margin-bottom: 10px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 400;
            color: #495057;
            margin-bottom: 20px;
        }

        .error-message {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: #fff;
            border: 1px solid #007bff;
        }

        .btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }

        .btn-secondary {
            background: #fff;
            color: #6c757d;
            border: 1px solid #6c757d;
        }

        .btn-secondary:hover {
            background: #6c757d;
            color: #fff;
        }

        @media (max-width: 600px) {
            .error-code {
                font-size: 56px;
            }

            .error-title {
                font-size: 20px;
            }

            .error-container {
                padding: 30px 20px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $code ?? 'ERROR'; ?></div>
        <div class="error-title">
            <?php 
            $titles = [
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                419 => 'Page Expired',
                429 => 'Too Many Requests',
                503 => 'Service Unavailable',
            ];
            echo $titles[$code] ?? 'Error';
            ?>
        </div>
        <div class="error-message">
            <?php echo htmlspecialchars($message ?? 'An error occurred.'); ?>
        </div>
        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            <a href="/" class="btn btn-primary">Go Home</a>
        </div>
    </div>
</body>
</html>
