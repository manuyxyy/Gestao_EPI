<?php
// DIAGNÓSTICO TEMPORÁRIO — apagar depois.
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>diag</title></head>
<body>
<iframe id="f" src="<?= htmlspecialchars($_GET['p'] ?? '/index.php', ENT_QUOTES) ?>" style="width:100%;height:800px;border:0"></iframe>
<script>
const f = document.getElementById('f');
f.addEventListener('load', function () {
    const d = f.contentDocument;
    const de = d.documentElement;
    const over = [];
    d.querySelectorAll('body *').forEach(function (el) {
        const r = el.getBoundingClientRect();
        if (r.width > 0 && (r.right > de.clientWidth + 1 || r.left < -1)) {
            over.push(el.tagName + '.' + (el.className || '').toString().slice(0, 40) + ' [' + Math.round(r.left) + '..' + Math.round(r.right) + ']');
        }
    });
    document.title = 'client=' + de.clientWidth + ' scroll=' + de.scrollWidth +
        ' bodyScroll=' + d.body.scrollWidth + ' | overflow: ' + over.slice(0, 12).join(' ;; ');
});
</script>
</body></html>
