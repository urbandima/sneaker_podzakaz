<?php

/** @var string $specUrl */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Sneaker Store API — документация</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: <?= json_encode($specUrl, JSON_UNESCAPED_SLASHES) ?>,
                dom_id: '#swagger-ui',
                presets: [SwaggerUIBundle.presets.apis],
            });
        };
    </script>
</body>
</html>
