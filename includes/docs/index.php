<?php
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function loadCssContent($href) {
    $fullPath = __DIR__ . '/' . $href;
    if (!file_exists($fullPath)) {
        return "/* Failed to load CSS from $href */";
    }
    return file_get_contents($fullPath);
}

function loadJsContent($src) {
    $fullPath = __DIR__ . '/' . $src;
    if (!file_exists($fullPath)) {
        return "// Failed to load JS from $src";
    }
    return file_get_contents($fullPath);
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swagger UI</title>
    <style><?php echo loadCssContent('swagger-ui.css'); ?></style>
    <style><?php echo loadCssContent('index.css'); ?></style>
    <link rel="icon" type="image/png" href="./favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="./favicon-16x16.png" sizes="16x16" />
</head>
<body>
    <div id="swagger-ui"></div>

    <script charset="UTF-8">
        <?php echo loadJsContent('swagger-ui-bundle.js'); ?>
    </script>

    <script charset="UTF-8">
        <?php echo loadJsContent('swagger-ui-standalone-preset.js'); ?>
    </script>

    <script charset="UTF-8">
        <?php echo loadJsContent('swagger-initializer.js'); ?>
    </script>
</body>
</html>
