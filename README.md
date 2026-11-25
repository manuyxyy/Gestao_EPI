# Gestao_EPI

Sistema EpiSafe de gestão de Equipamentos de Proteção Individual (PHP + MySQL).

## Layout

- **Login e cadastro**: página única e minimalista, com um cartão centralizado
  e apenas o necessário para entrar ou criar a conta.
- **Área interna**: menu de navegação na lateral esquerda, com agrupamento por
  área e indicador da página atual. Em telas pequenas o menu vira uma gaveta,
  aberta pelo botão no canto superior esquerdo.
- **Relatórios**: gráficos de linha (entregas nos últimos 6 meses), rosca
  (distribuição por setor) e barras (unidades por EPI), além dos indicadores e
  das tabelas de apoio.

## Acessibilidade

Todas as telas abrem em **alto contraste** e contam com:

- painel flutuante **Acessibilidade** (canto inferior direito) para ajustar
  tamanho de texto/tela (100% a 200%), alto contraste, sublinhado de links e
  animações;
- **síntese de voz** (“Ler esta página”) usando a API de fala do navegador;
- preferências salvas no navegador para login, cadastro e áreas internas;
- navegação completa por teclado, links de pulo, foco visível reforçado,
  rótulos ARIA, cabeçalhos de tabela e descrições textuais nos gráficos.

Atalhos: `Alt+A` abre/fecha o painel, `Alt+L` inicia/interrompe a leitura e
`Esc` fecha o painel (ou o menu lateral).

Consulte `LEIA-ME.txt` para instalação e instruções de uso.
