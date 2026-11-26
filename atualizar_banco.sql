-- =====================================================================
-- EpiSafe — atualização do banco já existente
--
-- Use este arquivo se o sistema já estava instalado antes desta versão.
-- Ele adiciona as novas colunas de usuários, o índice único do estoque
-- e cria o usuário administrador inicial (se ainda não existir).
--
-- Execute no phpMyAdmin (aba SQL) ou:
--   mysql -u root gestao_epis < atualizar_banco.sql
-- =====================================================================

USE gestao_epis;

DROP PROCEDURE IF EXISTS episafe_migrar;

DELIMITER $$
CREATE PROCEDURE episafe_migrar()
BEGIN
    -- Novas colunas de usuários
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'setor'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN setor VARCHAR(100) NULL AFTER funcao;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'telefone'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL AFTER setor;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'status'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Ativo' AFTER telefone;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'ultimo_acesso'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN ultimo_acesso DATETIME NULL AFTER status;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'ultimo_ip'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN ultimo_ip VARCHAR(45) NULL AFTER ultimo_acesso;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'criado_em'
    ) THEN
        ALTER TABLE usuarios ADD COLUMN criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ultimo_ip;
    END IF;

    -- Uma única linha de estoque por EPI (evita saldo duplicado)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'estoque' AND INDEX_NAME = 'uq_estoque_epi'
    ) THEN
        ALTER TABLE estoque DROP INDEX id_epi;
        ALTER TABLE estoque ADD UNIQUE KEY uq_estoque_epi (id_epi);
    END IF;

    -- Perfis antigos: "Tecnico" passa a ser "Almoxarifado".
    UPDATE usuarios
       SET tipo_acesso = 'Almoxarifado', funcao = 'Almoxarifado'
     WHERE tipo_acesso = 'Tecnico';

    -- Mantém somente os três perfis usados pelo sistema.
    ALTER TABLE usuarios
        MODIFY COLUMN tipo_acesso ENUM('Administrador', 'Almoxarifado', 'Gestor')
        NOT NULL DEFAULT 'Almoxarifado';
END$$
DELIMITER ;

CALL episafe_migrar();
DROP PROCEDURE episafe_migrar;

-- Códigos de autorização gerados pelo administrador (tela "Códigos de acesso")
CREATE TABLE IF NOT EXISTS convites (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(32) NOT NULL,
    perfil       ENUM('Administrador', 'Almoxarifado', 'Gestor')
                 NOT NULL DEFAULT 'Almoxarifado',
    criado_por   INT NOT NULL,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em    DATETIME DEFAULT NULL,
    usado_em     DATETIME DEFAULT NULL,
    usado_por    INT DEFAULT NULL,
    revogado_em  DATETIME DEFAULT NULL,
    UNIQUE KEY uq_convites_codigo (codigo),
    KEY idx_convites_perfil (perfil),
    CONSTRAINT fk_convites_criado_por FOREIGN KEY (criado_por)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_convites_usado_por FOREIGN KEY (usado_por)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário administrador inicial (ignora se o e-mail já existir)
-- E-mail: emanuelle.email@episafe.com | Senha: Mf/1426**
INSERT IGNORE INTO usuarios
    (nome, email, senha, tipo_acesso, funcao, setor, status)
VALUES (
    'Emanuelle',
    'emanuelle.email@episafe.com',
    '$2y$10$.7kVAM253gU0EB91aAz.3.dZO7fcTMLTuH5afHKVljn1wK58mJnh.',
    'Administrador',
    'Administrador',
    'Administração',
    'Ativo'
);
