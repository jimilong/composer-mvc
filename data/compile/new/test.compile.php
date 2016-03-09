<html>
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
<?php if (is_array($test) or is_object($test)) { foreach($test as $k => $v) { ?>
<p><?php echo $v; ?></p>
<?php } } ?>
</body>
</html>