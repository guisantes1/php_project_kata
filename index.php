<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mars Rover Mission</title>
    <link rel="stylesheet" href="styles.css" /> <!-- Enlace al archivo de estilos -->
</head>

<body>
    <!-- Título principal decorativo -->
    <h1 style="text-align: center; color: #ff3c00; font-size: 3rem; margin-bottom: 1rem;">
        👽 MARS ROVER MISSION 🚀
    </h1>

    <div class="container">
        <h1>Mars Rover Mission</h1>

        <!-- Instrucciones de uso -->
        <p>Your mission is to simulate the movement of autonomous rovers on a grid-based planet surface. Provide the input in this exact format:</p>
        <ul>
            <li><strong>Planet space:</strong> Grid size [width, height], min 10x10, origin (0,0).</li>
            <li><strong>Possible obstacles:</strong> Format [x, y, w, h], where (x, y) is bottom-left corner.</li>
            <li><strong>Initial Point:</strong> Format [x, y, D] with commands. D optional (defaults to N).</li>
        </ul>

        <!-- Ejemplo de entrada -->
        <div class="example">
            Planet space: [10,10]<br>
            Possible obstacles:<br>
            [2,2,3,3]<br>
            [5,5,3,3]<br>
            [4,4,4,4]<br>
            [8,8,4,4]<br>
            Initial Point:<br>
            [5,6,N] FFRRFFFRL<br>
            [1,1] FFFRRRFFFFFFF
        </div>

        <!-- Formulario para subir o pegar datos -->
        <h2>Upload or Paste Your Planet Data</h2>
        <form action="" method="POST" enctype="multipart/form-data" id="dataForm">
            <!-- Zona de drag & drop -->
            <div class="dropzone" id="dropzone">
                Drag and drop your .txt file here or click to upload
            </div>
            <!-- Input oculto para el archivo -->
            <input type="file" name="archivo" id="fileInput" accept=".txt" style="display:none;" />

            <!-- Área de texto para pegar los datos -->
            <p>Or paste your mission instructions:</p>
            <textarea name="manual_data" id="manual_data" placeholder="Paste your mission instructions here..."></textarea><br /><br />

            <!-- Botón para procesar -->
            <button type="submit" name="accion" value="todo">Process All</button>
        </form>
    </div>
</body>

</html>


<?php
require_once 'utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = '';

    // Prioriza datos pegados manualmente
    if (!empty($_POST['manual_data'])) {
        $data = $_POST['manual_data'];
    }
    // Si no, usa el archivo cargado
    elseif (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $data = file_get_contents($_FILES['archivo']['tmp_name']);
    }

    if (!empty($data)) {
        // Guarda el contenido en un archivo temporal
        $tmpFile = tempnam(sys_get_temp_dir(), 'mars_input_');
        file_put_contents($tmpFile, $data);
        ob_start();

        // Procesa los datos de entrada
        list($size, $obstacles, $points) = leerArchivoEntrada($tmpFile);

        // Si no se detectó un planeta válido
        if (!is_array($size)) {
            echo "<div id='result-block'><strong>❌ Error:</strong> No valid planet space found.</div>";
        } else {
            echo "<div id='result-block'>";
            echo "<h2>Processing Results:</h2>";

            // Cabecera del resumen
            $output = "✔ Planet space: [" . implode(',', $size) . "]\n";
            $output .= "✔ Obstacles found: " . count($obstacles) . "\n\n";

            // Validación de obstáculos
            $output .= str_repeat("=", 40) . "\nObstacle Validation\n" . str_repeat("=", 40) . "\n";
            ob_start();
            $validObstacles = validateObstacles($obstacles, $size);
            $output .= ob_get_clean();

            // Análisis de puntos uno por uno
            $output .= "\n" . str_repeat("=", 40) . "\nPoint by Point Analysis\n" . str_repeat("=", 40) . "\n";

            foreach ($points as $i => $data) {
                [$x, $y] = $data['position'];
                $dir = $data['direction'];
                $cmds = $data['commands'];

                // Encabezado del punto
                $output .= str_repeat("-", 80) . "\n";
                $output .= "**Point $i: [$x, $y, $dir] $cmds**\n";
                $output .= str_repeat("-", 80) . "\n";

                // Validar punto inicial
                ob_start();
                $isValid = processPointsIndividually($data, $size, $validObstacles, $i);
                $output .= ob_get_clean();

                // Ejecutar comandos si es válido
                if ($isValid) {
                    ob_start();
                    executeRoverCommands([$data], $validObstacles, $size, $i);
                    $output .= ob_get_clean();
                }

                // Separador final
                $output .= str_repeat("-", 80) . "\n";
            }

            // Divide $output en líneas
            $lines = explode("\n", $output);

            // Itera sobre cada línea
            foreach ($lines as $line) {
                // Elimina espacios en blanco
                $line = trim($line);

                // Formatea la salida según el contenido de la línea
                switch (true) {

                    // Línea de separación con '==='
                    case str_starts_with($line, str_repeat("=", 40)):
                        echo "<hr>";  // Saldrá como una línea horizontal, sin color ni formato de texto.
                        break;

                    // Línea con '✔'
                    case str_starts_with($line, '✔'):
                        echo "<p style='color:#00ffcc;font-weight:bold;'>" . htmlspecialchars($line) . "</p>";
                        // Saldrá en **color verde neón** y **negrita**.
                        break;

                    // Línea con '❌'
                    case str_starts_with($line, '❌'):
                        echo "<p style='color:#ff5f5f;font-weight:bold;'>" . htmlspecialchars($line) . "</p>";
                        // Saldrá en **color rojo** y **negrita**.
                        break;

                    // Texto entre '**' 
                    case preg_match('/^\*\*(.+?)\*\*$/', $line, $m):
                        echo "<p style='color:#ffff00;font-weight:bold;'>" . htmlspecialchars($m[1]) . "</p>";
                        // Saldrá en **color amarillo** y **negrita**.
                        break;

                    // Línea con 'Valid obstacles:'
                    case str_starts_with($line, 'Valid obstacles:'):
                        echo "<h4 style='margin-top:1em;'>" . htmlspecialchars($line) . "</h4>";
                        // Saldrá como un subtítulo con formato `h4`, **color negro** (por defecto) y tamaño de fuente más grande.
                        break;

                    // Títulos de validación
                    case str_starts_with($line, 'Obstacle Validation') || str_starts_with($line, 'Point by Point Analysis'):
                        echo "<h3 style='color:#00ffcc;'>" . htmlspecialchars($line) . "</h3>";
                        // Saldrá como un **título en color verde neón** (`h3`), de mayor tamaño.
                        break;

                    // Línea normal
                    case $line !== '':
                        echo "<p>" . htmlspecialchars($line) . "</p>";
                        // Saldrá como **texto normal**, sin color ni formato adicional.
                        break;
                }
            }


            // Botón para descargar resultado en PDF
            echo "<div style='display: flex; flex-direction: column; align-items: flex-start; gap: 10px; margin-top: 1rem;'>
                    <form id='downloadForm' method='POST' action='download.php' target='_blank'>
                        <input type='hidden' name='data' value='" . htmlspecialchars(urlencode($output)) . "'>
                        <input type='hidden' name='format' value='pdf'>
                    </form>
                    <button class='download' type='button' onclick='openDownload()'>
                        📄 Download Result as PDF
                    </button>
                </div>";

            // Botón para reiniciar la interfaz
            echo "<button type='button' onclick='resetPage()' class='download'>🔄 Clean results and try again</button>";
            echo "</div>";
        }

        // Elimina el archivo temporal
        unlink($tmpFile);
    } else {
        echo "<p><strong>❌ Error:</strong> No input data provided.</p>";
    }
}
?>
<script src="script.js"></script>
</body>

</html>