<?php
/**
 * Gráficos acessíveis em SVG/CSS, sem dependências externas.
 *
 * Cada gráfico expõe uma descrição textual completa para leitores de tela
 * (role="img" + aria-label) e mantém os dados legíveis em alto contraste.
 */

if (!function_exists('grafico_esc')) {
    function grafico_esc($valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

/** Normaliza uma lista de dados para os gráficos. */
function grafico_dados(array $dados, int $limite = 8): array
{
    $saida = [];
    foreach ($dados as $item) {
        $rotulo = (string)($item['rotulo'] ?? '');
        $valor = (int)($item['valor'] ?? 0);
        if ($rotulo === '') {
            continue;
        }
        $saida[] = ['rotulo' => $rotulo, 'valor' => $valor];
    }
    if ($limite > 0 && count($saida) > $limite) {
        $saida = array_slice($saida, 0, $limite);
    }
    return $saida;
}

/** Gráfico de barras horizontais. */
function grafico_barras(array $dados, string $titulo): string
{
    $dados = grafico_dados($dados);
    if (!$dados) {
        return '<p class="inline-note">Sem dados para exibir.</p>';
    }

    $max = 1;
    foreach ($dados as $item) {
        $max = max($max, $item['valor']);
    }

    $descricao = $titulo . '. ';
    $html = '<div class="grafico-barras" role="img" aria-label="'
          . grafico_esc($descricao . implode('; ', array_map(function ($i) {
                return $i['rotulo'] . ': ' . $i['valor'];
            }, $dados))) . '">';

    foreach ($dados as $item) {
        $largura = (int)round(($item['valor'] / $max) * 100);
        $html .= '<div class="grafico-barra">';
        $html .= '<span class="grafico-barra-rotulo">' . grafico_esc($item['rotulo']) . '</span>';
        $html .= '<span class="grafico-barra-trilho">';
        $html .= '<span class="grafico-barra-preenchimento" style="width:' . $largura . '%"></span>';
        $html .= '</span>';
        $html .= '<span class="grafico-barra-valor">' . $item['valor'] . '</span>';
        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}

/** Gráfico de linha (evolução temporal). */
function grafico_linha(array $dados, string $titulo): string
{
    $dados = grafico_dados($dados, 12);
    if (!$dados) {
        return '<p class="inline-note">Sem dados para exibir.</p>';
    }

    $largura = 660;
    $altura = 230;
    $esquerda = 42;
    $direita = 16;
    $topo = 18;
    $base = 186;
    $areas = $base - $topo;

    $max = 1;
    foreach ($dados as $item) {
        $max = max($max, $item['valor']);
    }
    $max = (int)ceil($max / 5) * 5;
    if ($max < 5) {
        $max = 5;
    }

    $total = count($dados);
    $passo = $total > 1 ? ($largura - $esquerda - $direita) / ($total - 1) : 0;
    $pontos = [];
    $holes = [];
    foreach ($dados as $indice => $item) {
        $x = $esquerda + ($passo * $indice);
        $y = $base - ($item['valor'] / $max) * $areas;
        $pontos[] = [$x, $y, $item];
        $holes[] = $x . ',' . $y;
    }

    $descricao = $titulo . '. ' . implode('; ', array_map(function ($i) {
        return $i['rotulo'] . ': ' . $i['valor'];
    }, $dados));
    $idLinha = 'grafico-linha-' . substr(md5($titulo . count($dados)), 0, 8);

    $svg = '<svg class="grafico-svg" viewBox="0 0 ' . $largura . ' ' . $altura . '" '
         . 'role="img" aria-label="' . grafico_esc($descricao) . '" preserveAspectRatio="xMidYMid meet">';
    $svg .= '<title>' . grafico_esc($titulo) . '</title>';

    for ($n = 0; $n <= 4; $n++) {
        $y = $topo + ($areas / 4) * $n;
        $valor = (int)round($max - ($max / 4) * $n);
        $svg .= '<line x1="' . $esquerda . '" y1="' . $y . '" x2="' . ($largura - $direita)
              . '" y2="' . $y . '" class="grafico-grade"/>';
        $svg .= '<text x="' . ($esquerda - 8) . '" y="' . ($y + 4) . '" class="grafico-eixo" text-anchor="end">'
              . $valor . '</text>';
    }

    $svg .= '<polygon points="' . $esquerda . ',' . $base . ' ' . implode(' ', $holes) . ' '
          . ($esquerda + ($passo * ($total - 1))) . ',' . $base . '" class="grafico-area"/>';
    $svg .= '<polyline points="' . implode(' ', $holes) . '" class="grafico-linha"/>';

    foreach ($pontos as $ponto) {
        $svg .= '<circle cx="' . $ponto[0] . '" cy="' . $ponto[1] . '" r="4" class="grafico-ponto">'
              . '<title>' . grafico_esc($ponto[2]['rotulo'] . ': ' . $ponto[2]['valor']) . '</title>'
              . '</circle>';
        $anchor = 'middle';
        if ($ponto[0] < $esquerda + 18) {
            $anchor = 'start';
        } elseif ($ponto[0] > $largura - $direita - 18) {
            $anchor = 'end';
        }
        $svg .= '<text x="' . $ponto[0] . '" y="' . ($base + 22) . '" class="grafico-eixo" text-anchor="'
              . $anchor . '">' . grafico_esc($ponto[2]['rotulo']) . '</text>';
    }

    $svg .= '</svg>';
    return '<div class="grafico-linha-wrap" id="' . $idLinha . '">' . $svg . '</div>';
}

/** Gráfico de rosca (distribuição percentual). */
function grafico_rosca(array $dados, string $titulo): string
{
    $dados = grafico_dados($dados, 6);
    if (!$dados) {
        return '<p class="inline-note">Sem dados para exibir.</p>';
    }

    $cores = ['#f5a800', '#d98f00', '#1f7a52', '#b5590a', '#8a5200', '#64748b'];
    $total = 0;
    foreach ($dados as $item) {
        $total += $item['valor'];
    }
    if ($total <= 0) {
        return '<p class="inline-note">Sem dados para exibir.</p>';
    }

    $partes = [];
    $inicio = 0;
    $legenda = '<ul class="grafico-legenda">';
    foreach ($dados as $indice => $item) {
        $cor = $cores[$indice % count($cores)];
        $fim = $inicio + ($item['valor'] / $total) * 100;
        $partes[] = $cor . ' ' . $inicio . '% ' . $fim . '%';
        $legenda .= '<li><span class="grafico-legenda-cor" style="background:' . $cor . '"></span>'
                  . '<span>' . grafico_esc($item['rotulo']) . '</span>'
                  . '<strong>' . (int)round(($item['valor'] / $total) * 100) . '%</strong></li>';
        $inicio = $fim;
    }
    $legenda .= '</ul>';

    $descricao = $titulo . '. ' . implode('; ', array_map(function ($i) use ($total) {
        return $i['rotulo'] . ': ' . (int)round(($i['valor'] / $total) * 100) . '% (' . $i['valor'] . ')';
    }, $dados));

    $html = '<div class="grafico-rosca-layout">';
    $html .= '<div class="grafico-rosca" role="img" aria-label="' . grafico_esc($descricao)
          . '" style="background:conic-gradient(' . implode(',', $partes) . ')">';
    $html .= '<div class="grafico-rosca-centro"><strong>' . $total . '</strong><span>unidades</span></div>';
    $html .= '</div>';
    $html .= $legenda;
    $html .= '</div>';
    return $html;
}
