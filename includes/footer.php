</div>
<script src="assets/js/app.js"></script>
<?php if (isset($extraScripts)): foreach ($extraScripts as $s): ?>
<script src="<?= htmlspecialchars($s) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
