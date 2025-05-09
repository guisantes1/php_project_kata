<?php
function leerArchivoEntrada($rute)
{
    // Verifica que el archivo exista
    if (!file_exists($rute)) {
        echo "Error: File '$rute' not found.\n";
        exit(1);
    }

    // Lee todas las líneas, ignorando vacías
    $lines = file($rute, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $mode = '';
    $planetSpace = null;
    $obstacles = [];
    $startingPoints = [];

    // Recorre cada línea del archivo
    foreach ($lines as $line) {
        $line = trim($line);
        switch (true) {

            // Detecta definición del planeta
            case str_starts_with($line, 'Planet space:'):
                $mode = 'planet';
                // Extrae ancho y alto dentro de corchetes: [width, height]
                if (preg_match('/\[(\d+),\s*(\d+)\]/', $line, $matches)) {
                    $width = (int)$matches[1];
                    $height = (int)$matches[2];
                    // Solo acepta planetas de al menos 10x10
                    if ($width >= 10 && $height >= 10) {
                        $planetSpace = [$width, $height];
                    }
                }
                break;

            // Cambia al modo lectura de obstáculos
            case $line === 'Possible obstacles:':
                $mode = 'obstacles';
                break;

            // Cambia al modo lectura de puntos iniciales
            case str_starts_with($line, 'Initial Point'):
                $mode = 'points';
                break;

            // Guarda obstáculos válidos encontrados
            case $mode === 'obstacles':
                // Extrae coordenadas y tamaño del obstáculo: [x, y, w, h]
                preg_match('/\[(\d+),\s*(\d+),\s*(\d+),\s*(\d+)\]/', $line, $m);
                if ($m) {
                    $obstacles[] = array_map('intval', array_slice($m, 1));
                }
                break;


            // Guarda puntos iniciales con comandos
            case $mode === 'points':
                // Extrae posición, dirección (si se ha puesto) y comandos del rover con el formato [x, y, D]
                if (preg_match('/\[(\d+),\s*(\d+)(?:,\s*([NSEW]))?\]\s*([FLRflr]+)/i', $line, $m)) {
                    $startingPoints[] = [
                        'position' => [intval($m[1]), intval($m[2])],
                        'direction' => (!empty($m[3]) && in_array(strtoupper($m[3]), ['N', 'S', 'E', 'W'])) ? strtoupper($m[3]) : 'N',
                        'commands' => strtoupper($m[4])
                    ];
                }
                break;
        }
    }

    // Verifica que las dimensiones del planeta sean válidas
    if (!$planetSpace || $planetSpace[0] < 10 || $planetSpace[1] < 10) {
        echo "❌ Cannot execute the program: no valid planet dimensions detected (minimum 10x10 required).\n";
        return [null, [], []];
    }

    // Devuelve el tamaño del planeta, los obstáculos y los puntos de inicio
    return [$planetSpace, $obstacles, $startingPoints];
}

function validateObstacles($obstacles, $size)
{
    $n = count($obstacles); // Número total de obstáculos
    $outOfBoundsErrors = []; // Errores por salirse del planeta
    $overlapErrors = [];     // Errores por solapamientos
    $validObstacles = [];    // Obstáculos válidos

    // Recorre cada obstáculo para validarlo
    for ($i = 0; $i < $n; $i++) {
        $hasError = false;
        [$x, $y, $w, $h] = $obstacles[$i];

        // Verifica que esté dentro de los límites del planeta
        $withinBounds = ($x >= 0 && $y >= 0 && ($x + $w) <= $size[0] && ($y + $h) <= $size[1]);

        if (!$withinBounds) {
            $outOfBoundsErrors[] = "❌ Obstacle $i out of bounds: [$x, $y, $w, $h].";
            $hasError = true;
        }

        // Verifica si se solapa con alguno de los obstáculos válidos previos
        foreach ($validObstacles as $j => $obs) {
            [$x2, $y2, $w2, $h2] = $obs;
            $overlaps = !($x + $w <= $x2 || $x2 + $w2 <= $x || $y + $h <= $y2 || $y2 + $h2 <= $y);
            if ($overlaps) {
                $overlapErrors[] = "❌ Obstacles $i and $j overlap: [$x, $y, $w, $h] and [$x2, $y2, $w2, $h2].";
                $hasError = true;
            }
        }

        // Si no hay errores, se considera válido
        if (!$hasError) {
            $validObstacles[] = [$x, $y, $w, $h];
            echo "✅ Obstacle $i loaded correctly: [$x, $y, $w, $h].\n";
        }
    }

    // Muestra errores encontrados
    foreach ($outOfBoundsErrors as $error) echo "$error\n";
    foreach (array_unique($overlapErrors) as $error) echo "$error\n";

    // Muestra resumen de obstáculos válidos
    if (count($validObstacles) > 0) {
        echo "Valid obstacles:\n";
        foreach ($validObstacles as $obs) {
            echo "✅ " . implode(', ', $obs) . "\n";
        }
    } else {
        echo "No valid obstacles found.\n";
    }

    return $validObstacles; // Devuelve solo los obstáculos válidos
}


// Verifica si un punto está dentro de algún obstáculo definido
function pointInsideObstacle($point, $obstacles)
{
    [$px, $py] = $point; // Coordenadas del punto a evaluar

    foreach ($obstacles as $obs) {
        [$ox, $oy, $w, $h] = $obs; // Coordenadas y tamaño del obstáculo

        // Comprueba si el punto cae dentro del área ocupada por el obstáculo
        if ($px >= $ox && $px < $ox + $w && $py >= $oy && $py < $oy + $h) {
            return true; // Está dentro
        }
    }
    return false; // No está dentro de ningún obstáculo
}

// Valida si el punto inicial del rover es válido (dentro del planeta y fuera de obstáculos)
function processPointsIndividually($data, $size, $obstacles, $index = 0)
{
    [$x, $y] = $data['position']; // Coordenadas iniciales del rover
    $dir = $data['direction'];   // Dirección inicial
    $valid = true;

    switch (true) {

        // Fuera de los límites del planeta
        case ($x < 0 || $x >= $size[0] || $y < 0 || $y >= $size[1]):
            echo "❌ Point $index is invalid: ($x, $y) is out of bounds.\n";
            $valid = false;
            break;

        // Dentro de un obstáculo
        case pointInsideObstacle([$x, $y], $obstacles):
            echo "❌ Point $index is invalid: ($x, $y) is inside an obstacle.\n";
            $valid = false;
            break;

        // Si pasa ambas condiciones, es válido
        default:
            echo "➔ ✅ Point $index is valid: ($x, $y)\n";
            break;
    }

    // Mensaje adicional si no es válido
    if (!$valid) {
        echo "❌ Rover $index cannot start its journey at ($x,$y) due to an invalid starting point.\n";
    }
    return $valid;
}


// Ejecuta los comandos de movimiento para cada rover
function executeRoverCommands($roverCommands, $obstacles, $size, $indexOffset = 0)
{
    // Direcciones posibles y sus desplazamientos
    $directions = ['N', 'E', 'S', 'W'];
    $moves = ['N' => [0, 1], 'E' => [1, 0], 'S' => [0, -1], 'W' => [-1, 0]];

    foreach ($roverCommands as $i => $data) {
        [$x, $y] = $data['position']; // Posición inicial
        $cmds = $data['commands']; // Comandos
        $dir = strtoupper($data['direction'] ?? 'N'); // Dirección inicial

        echo "➔ 🚀 Rover " . ($indexOffset + $i) . " starting at ($x,$y) facing $dir\n";

        $aborted = false;

        // Ejecutar cada comando
        for ($j = 0; $j < strlen($cmds); $j++) {
            $cmd = strtoupper($cmds[$j]);

            switch (true) {
                // Gira a la izquierda (sentido antihorario)
                case $cmd === 'L':
                    // Retroceder un puesto en el array de direcciones ($directions)
                    // Usamos +3 ya que -1 = 3%4 , y restando podemos encontrar problemas si estamos en la pos. 0 
                    $dir = $directions[(array_search($dir, $directions) + 3) % 4];
                    echo "  🔄 Rotate left → Now facing $dir\n";
                    break;

                // Gira a la derecha (sentido horario)
                case $cmd === 'R':
                    // Avanzar 1 posición en el array de direcciones
                    $dir = $directions[(array_search($dir, $directions) + 1) % 4];
                    echo "  🔄 Rotate right → Now facing $dir\n";
                    break;

                // Intenta avanzar hacia adelante
                case $cmd === 'F':
                    $newX = $x + $moves[$dir][0];
                    $newY = $y + $moves[$dir][1];

                    switch (true) {
                        // Fuera del planeta
                        case ($newX < 0 || $newX >= $size[0] || $newY < 0 || $newY >= $size[1]):
                            if (!$aborted) { // Solo mostrar el mensaje de aborto una vez
                                echo "  ❌ Rover " . ($indexOffset + $i) . " aborted: would leave the planet at ($newX, $newY)\n";
                                $aborted = true;
                            }
                            break 2;

                        // Choca con obstáculo
                        case pointInsideObstacle([$newX, $newY], $obstacles):
                            if (!$aborted) { // Solo mostrar el mensaje de aborto una vez
                                echo "  ❌ Rover " . ($indexOffset + $i) . " aborted: obstacle at ($newX, $newY)\n";
                                $aborted = true;
                            }
                            break 2;

                        // Movimiento válido
                        default:
                            $x = $newX;
                            $y = $newY;
                            echo "  ➡️ Move to ($x,$y) facing $dir\n";
                            break;
                    }
                    break;
            }

            // Si se aborta, se sale del ciclo y no se continúa con más movimientos
            if ($aborted) break;
        }

        // Si no se abortó, mostrar posición final
        if (!$aborted) {
            echo "✅ Rover " . ($indexOffset + $i) . " completed commands. Final position: ($x, $y) facing $dir\n";
        }
    }
}
