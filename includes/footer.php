</div>
<?php $appJsVer = file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : '1'; ?>
<script src="assets/js/app.js?v=<?= $appJsVer ?>"></script>
<?php if (isset($extraScripts)): foreach ($extraScripts as $s): ?>
<script src="<?= htmlspecialchars($s) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
