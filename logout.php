<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_validar()) {
    flash('erro', 'Não foi possível encerrar a sessão. Tente novamente.');
    redirecionar('dashboard_admin.php');
}

encerrar_sessao();
redirecionar('index.php?aviso=logout');
