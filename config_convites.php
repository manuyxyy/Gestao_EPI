<?php
/**
 * Códigos de autorização para criar qualquer conta no EpiSafe.
 *
 * IMPORTANTE: troque estes valores antes de usar o sistema. Eles não aparecem
 * em nenhuma página e só devem ser informados pelo ADMINISTRADOR. O Gestor não
 * tem poder de autorizar cadastros.
 *
 * Também é possível definir as variáveis de ambiente abaixo no servidor; elas
 * têm prioridade sobre os valores deste arquivo. Para desativar um perfil,
 * deixe o valor vazio.
 *
 *   EPISAFE_CONVITE_ALMOXARIFADO
 *   EPISAFE_CONVITE_GESTOR
 *   EPISAFE_CONVITE_ADMIN
 */
return [
    'Almoxarifado'  => getenv('EPISAFE_CONVITE_ALMOXARIFADO') ?: 'AL-5wQ1rD8hJ4s',
    'Gestor'        => getenv('EPISAFE_CONVITE_GESTOR') ?: 'GS-7kR4mT9qX2v',
    'Administrador' => getenv('EPISAFE_CONVITE_ADMIN') ?: 'AD-3nP8wZ5cL1y',
];
