-- =====================================================================
-- EpiSafe — estrutura do banco de dados (instalação nova)
-- Banco: gestao_epis · MySQL 8 / MariaDB 10.4+
--
-- Importe este arquivo no phpMyAdmin ou execute:
--   mysql -u root < gestao_epis.sql
--
-- Usuário inicial criado no final do arquivo.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gestao_epis
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE gestao_epis;

-- ---------------------------------------------------------------------
-- USUÁRIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    senha         VARCHAR(255) NOT NULL,
    tipo_acesso   ENUM('Administrador', 'Tecnico', 'Almoxarifado', 'Gestor')
                  NOT NULL DEFAULT 'Tecnico',
    funcao        VARCHAR(20) NOT NULL,
    setor         VARCHAR(100) DEFAULT NULL,
    telefone      VARCHAR(20) DEFAULT NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'Ativo',
    ultimo_acesso DATETIME DEFAULT NULL,
    ultimo_ip     VARCHAR(45) DEFAULT NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_status (status),
    KEY idx_usuarios_tipo (tipo_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- FUNCIONÁRIOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS funcionarios (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    nome  VARCHAR(100) NOT NULL,
    cpf   VARCHAR(14) DEFAULT NULL,
    setor VARCHAR(100) DEFAULT NULL,
    cargo VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    UNIQUE KEY uq_funcionarios_cpf (cpf),
    KEY idx_funcionarios_setor (setor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- EPIS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS epis (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL,
    descricao TEXT DEFAULT NULL,
    numero_ca VARCHAR(30) DEFAULT NULL,
    validade  DATE DEFAULT NULL,
    KEY idx_epis_validade (validade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ESTOQUE (uma linha por EPI)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS estoque (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    id_epi            INT NOT NULL,
    quantidade        INT NOT NULL DEFAULT 0,
    quantidade_minima INT NOT NULL DEFAULT 5,
    UNIQUE KEY uq_estoque_epi (id_epi),
    CONSTRAINT fk_estoque_epi FOREIGN KEY (id_epi) REFERENCES epis (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ENTREGAS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS entregas (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_funcionario INT NOT NULL,
    id_epi         INT NOT NULL,
    id_usuario     INT DEFAULT NULL,
    quantidade     INT NOT NULL DEFAULT 1,
    data_entrega   DATE NOT NULL,
    observacao     TEXT DEFAULT NULL,
    KEY idx_entregas_data (data_entrega),
    CONSTRAINT fk_entregas_funcionario FOREIGN KEY (id_funcionario)
        REFERENCES funcionarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_entregas_epi FOREIGN KEY (id_epi)
        REFERENCES epis (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_entregas_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DEVOLUÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS devolucoes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_entrega     INT NOT NULL,
    data_devolucao DATE NOT NULL,
    quantidade     INT NOT NULL DEFAULT 1,
    estado         VARCHAR(50) DEFAULT NULL,
    observacao     TEXT DEFAULT NULL,
    KEY idx_devolucoes_data (data_devolucao),
    CONSTRAINT fk_devolucoes_entrega FOREIGN KEY (id_entrega)
        REFERENCES entregas (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- MOVIMENTAÇÕES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movimentacoes (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    id_epi             INT NOT NULL,
    tipo               ENUM('Entrada', 'Saida', 'Devolucao') NOT NULL,
    quantidade         INT NOT NULL,
    data_movimentacao  DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacao         TEXT DEFAULT NULL,
    KEY idx_movimentacoes_data (data_movimentacao),
    CONSTRAINT fk_movimentacoes_epi FOREIGN KEY (id_epi)
        REFERENCES epis (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ACESSO INICIAL
-- E-mail: admin@episafe.local
-- Senha:  Admin@12345   (TROQUE NO PRIMEIRO ACESSO em Meu perfil)
-- ---------------------------------------------------------------------
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
