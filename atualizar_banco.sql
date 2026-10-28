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
END$$
DELIMITER ;

CALL episafe_migrar();
DROP PROCEDURE episafe_migrar;

-- Usuário administrador inicial (ignora se o e-mail já existir)
INSERT IGNORE INTO usuarios
    (nome, email, senha, tipo_acesso, funcao, setor, status)
VALUES (
    'Administrador',
    'admin@episafe.local',
    '$2y$10$X44NLDaoZSZirk.S891vfeBSNB6hRgemeSvlQOfdUP.Q1tgBsoG5y',
    'Administrador',
    'Administrador',
    'Administração',
    'Ativo'
);
