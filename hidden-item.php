<?php

$map = [
    "########",
    "#......#",
    "#.###..#",
    "#...#.##",
    "#X#....#",
    "########",
];

$directions = [
    'U' => [-1, 0],
    'T' => [0, 1],
    'S' => [1, 0],
];

function parseGrid(array $map): array
{
    $grid = [];
    $start = null;
    foreach ($map as $r => $rowStr) {
        $row = str_split($rowStr);
        $grid[] = $row;
        foreach ($row as $c => $ch) {
            if ($ch === 'X') {
                $start = [$r, $c];
            }
        }
    }
    if ($start === null) {
        throw new RuntimeException("Posisi start 'X' tidak ditemukan di grid.");
    }
    return [$grid, $start];
}

function isWalkable(array $grid, int $r, int $c): bool
{
    if ($r < 0 || $r >= count($grid)) {
        return false;
    }
    if ($c < 0 || $c >= count($grid[0])) {
        return false;
    }
    return $grid[$r][$c] !== '#';
}

function move(array $grid, array $start, string $direction, int $steps, array $directions): array
{
    [$dr, $dc] = $directions[$direction];
    [$r, $c] = $start;
    for ($i = 0; $i < $steps; $i++) {
        $nr = $r + $dr;
        $nc = $c + $dc;
        if (!isWalkable($grid, $nr, $nc)) {
            break;
        }
        $r = $nr;
        $c = $nc;
    }
    return [$r, $c];
}

function simulatePath(array $grid, array $start, int $a, int $b, int $c, array $directions): array
{
    $pos = move($grid, $start, 'U', $a, $directions);
    $pos = move($grid, $pos, 'T', $b, $directions);
    $pos = move($grid, $pos, 'S', $c, $directions);
    return $pos;
}

function parseRange(string $text, int $defaultMin = 1, int $defaultMax = 4): array
{
    $text = trim($text);
    if ($text === '') {
        return range($defaultMin, $defaultMax);
    }
    if (strpos($text, '-') !== false) {
        [$lo, $hi] = array_map('intval', array_map('trim', explode('-', $text, 2)));
        if ($lo > $hi) {
            [$lo, $hi] = [$hi, $lo];
        }
        return range($lo, $hi);
    }
    $val = (int) $text;
    return [$val];
}

function findProbablePoints(array $grid, array $start, array $aRange, array $bRange, array $cRange, array $directions): array
{
    $probable = [];
    foreach ($aRange as $a) {
        foreach ($bRange as $b) {
            foreach ($cRange as $c) {
                $end = simulatePath($grid, $start, $a, $b, $c, $directions);
                [$r, $col] = $end;
                if ($end !== $start && $grid[$r][$col] !== '#') {
                    $probable["$r,$col"] = $end;
                }
            }
        }
    }
    ksort($probable, SORT_STRING);
    return array_values($probable);
}

function printGrid(array $grid): void
{
    echo "\n";
    foreach ($grid as $row) {
        echo implode('', $row) . "\n";
    }
    echo "\n";
}

function renderGridWithMarks(array $grid, array $probablePoints, array $start): void
{
    $display = $grid;
    foreach ($probablePoints as [$r, $c]) {
        $display[$r][$c] = '$';
    }
    [$r0, $c0] = $start;
    $display[$r0][$c0] = 'X';

    echo "\n";
    foreach ($display as $row) {
        echo implode('', $row) . "\n";
    }
    echo "\n";
}

function getStepInput(string $label): array
{
    while (true) {
        echo "  Masukkan jumlah langkah {$label} (contoh: 3 atau 3-5, kosongkan untuk default 1-4): ";
        $line = fgets(STDIN);
        $line = $line === false ? '' : $line;
        try {
            return parseRange($line);
        } catch (\Throwable $e) {
            echo "  Input tidak valid, coba lagi. Contoh format: 2  atau  2-5\n";
        }
    }
}

function main(array $map, array $directions): void
{
    [$grid, $start] = parseGrid($map);

    echo str_repeat('=', 50) . "\n";
    echo "  HIDDEN ITEM - GRID EXPLORER\n";
    echo str_repeat('=', 50) . "\n";
    echo "Grid awal (X = posisi pemain, # = obstacle, . = jalan):\n";
    printGrid($grid);

    echo "Urutan pergerakan pemain: Utara (U) -> Timur (T) -> Selatan (S)\n";
    echo "Karena jumlah langkah pasti tidak diketahui, masukkan rentang\n";
    echo "kemungkinan jumlah langkah untuk tiap arah.\n\n";

    $uRange = getStepInput('Utara/U');
    $tRange = getStepInput('Timur/T');
    $sRange = getStepInput('Selatan/S');

    $probablePoints = findProbablePoints($grid, $start, $uRange, $tRange, $sRange, $directions);

    echo "\n" . str_repeat('-', 50) . "\n";
    echo "HASIL: Daftar titik koordinat kemungkinan lokasi item\n";
    echo str_repeat('-', 50) . "\n";
    if (empty($probablePoints)) {
        echo "Tidak ada titik yang valid ditemukan dengan rentang tersebut.\n";
    } else {
        foreach ($probablePoints as $i => [$r, $c]) {
            $num = $i + 1;
            echo "  {$num}. (baris={$r}, kolom={$c})\n";
        }
    }

    echo "\nGrid dengan penanda '\$' pada titik-titik kemungkinan lokasi item:\n";
    renderGridWithMarks($grid, $probablePoints, $start);
}

main($map, $directions);