<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #fff;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .error-header {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .error-type {
            font-size: 18px;
            font-weight: 600;
            color: #721c24;
            margin-bottom: 10px;
        }

        .error-message {
            font-size: 24px;
            font-weight: 300;
            color: #721c24;
            margin-bottom: 15px;
        }

        .error-location {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #856404;
            background: #fff3cd;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
        }

        .copy-button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .copy-button:hover {
            background: #0056b3;
        }

        .copy-button.copied {
            background: #28a745;
        }

        .stack-trace {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }

        .stack-trace h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #495057;
        }

        .trace-item {
            background: #fff;
            border-left: 3px solid #007bff;
            padding: 15px;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border-radius: 3px;
        }

        .trace-number {
            color: #6c757d;
            font-weight: 600;
            margin-right: 10px;
        }

        .trace-file {
            color: #007bff;
        }

        .trace-line {
            color: #dc3545;
        }

        .trace-function {
            color: #28a745;
            margin-top: 5px;
            display: block;
        }

        .no-details {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        // Check APP_DEBUG - this is the primary control like Laravel
        $appDebug = getenv('APP_DEBUG');
        $isDebugMode = in_array(strtolower($appDebug), ['true', '1', 'yes', 'on']);
        
        // If APP_DEBUG is explicitly false, never show details
        if (in_array(strtolower($appDebug), ['false', '0', 'no', 'off'])) {
            $isDebugMode = false;
        }
        
        $hasException = isset($exception) && ($exception instanceof \Throwable || $exception instanceof \Exception);
        ?>

        <?php if ($isDebugMode && $hasException): ?>
            <div class="error-header">
                <div class="error-type"><?php echo htmlspecialchars(get_class($exception)); ?></div>
                <div class="error-message"><?php echo htmlspecialchars($exception->getMessage() ?: 'No message'); ?></div>
                <div class="error-location">
                    <?php echo htmlspecialchars($exception->getFile()); ?>:<span style="color: #dc3545"><?php echo $exception->getLine(); ?></span>
                </div>
                <button class="copy-button" onclick="copyError()">Copy Error Details</button>
            </div>

            <div class="stack-trace">
                <h3>Stack Trace</h3>
                <?php 
                $trace = $exception->getTrace();
                foreach ($trace as $index => $item): 
                ?>
                    <div class="trace-item">
                        <span class="trace-number">#<?php echo $index; ?></span>
                        <?php if (isset($item['file'])): ?>
                            <span class="trace-file"><?php echo htmlspecialchars($item['file']); ?></span>:<span class="trace-line"><?php echo $item['line'] ?? '?'; ?></span>
                        <?php else: ?>
                            <span style="color: #6c757d;">[internal function]</span>
                        <?php endif; ?>
                        
                        <?php if (isset($item['class'])): ?>
                            <span class="trace-function"><?php echo htmlspecialchars($item['class'] . $item['type'] . $item['function']); ?>()</span>
                        <?php elseif (isset($item['function'])): ?>
                            <span class="trace-function"><?php echo htmlspecialchars($item['function']); ?>()</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <textarea id="errorText" style="position: absolute; left: -9999px;">
Exception: <?php echo get_class($exception); ?>

Message: <?php echo $exception->getMessage(); ?>

File: <?php echo $exception->getFile(); ?>

Line: <?php echo $exception->getLine(); ?>


Stack Trace:
<?php echo $exception->getTraceAsString(); ?>
            </textarea>

            <script>
                function copyError() {
                    const textarea = document.getElementById('errorText');
                    textarea.style.position = 'fixed';
                    textarea.style.left = '0';
                    textarea.select();
                    document.execCommand('copy');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    
                    const button = document.querySelector('.copy-button');
                    button.textContent = 'Copied!';
                    button.classList.add('copied');
                    
                    setTimeout(() => {
                        button.textContent = 'Copy Error Details';
                        button.classList.remove('copied');
                    }, 2000);
                }
            </script>

        <?php else: ?>
            <div class="no-details">
                <h1>500</h1>
                <h2>Server Error</h2>
                <p><?php echo htmlspecialchars($message ?? 'Something went wrong.'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
