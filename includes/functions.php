<?php
require_once 'vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errorfunctionphp_log'); // Asegúrate de especificar la ruta correcta a tu archivo de log
error_reporting(E_ALL);

function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatearNota($nota)
{
    return number_format($nota, 1, '.', '');
}

function paginate($total_items, $current_page = 1, $per_page = 10)
{
    $total_pages = ceil($total_items / $per_page);
    $offset = ($current_page - 1) * $per_page;

    return [
        'total_items' => $total_items,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'limit' => $per_page
    ];
}

function validate_rut_format($rut)
{
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    $body = substr($rut, 0, -1);
    $dv = strtoupper(substr($rut, -1));

    if (strlen($body) < 7) {
        return false;
    }

    $suma = 0;
    $mult = 2;

    for ($i = strlen($body) - 1; $i >= 0; $i--) {
        $suma += $body[$i] * $mult;
        $mult = $mult == 7 ? 2 : $mult + 1;
    }

    $res = 11 - ($suma % 11);

    if ($res == 11) {
        $res = 0;
    } else if ($res == 10) {
        $res = 'K';
    }

    if ($dv != $res) {
        return false;
    }

    return $body . '-' . $dv;
}

function generarSerie($years, $quarter, $rut)
{
    $yearPart = substr($years, -2);
    $quarterPart = "";
    if (strpos($quarter, 'First Quarter') !== false) {
        $quarterPart = "1";
    } elseif (strpos($quarter, 'Second Quarter') !== false) {
        $quarterPart = "2";
    } elseif (strpos($quarter, 'Third Quarter') !== false) {
        $quarterPart = "3";
    } elseif (strpos($quarter, 'Summer') !== false) {
        $quarterPart = "0";
    }
    return $yearPart . $quarterPart . $rut;
}

function get_user_role($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT role FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['role'];
}
function obtenerMesPorTrimestre($trimestre)
{
    switch ($trimestre) {
        case 'First Quarter':
            return 'Junio';
        case 'Second Quarter':
            return 'junio';
        case 'Third Quarter':
            return 'septiembre';
        case 'Summer':
            return 'diciembre';
        default:
            return 'diciembre'; // Mes por defecto si el trimestre no es válido
    }
}
function generarCertificadoPDF($alumno_id, $curso_id, $pdo)
{
    $certificado_datos = [];

    $sql = "SELECT e.id as estudiante_id, e.rut, e.nombre, e.apellido_p, e.apellido_m, e.email,
                   c.id as curso_id, c.anio, c.trimestre, c.course as curso, c.profesor, c.cefr, c.categoria, c.horas,
                   n.final_grade as nota
            FROM estudiantes e
            JOIN matriculas m ON e.id = m.estudiante_id
            JOIN cursos c ON m.curso_id = c.id
            JOIN notas n ON m.notas_id = n.id
            WHERE e.id = ? AND c.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$alumno_id, $curso_id]);
    $certificado_datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($certificado_datos) {
        // Generar y actualizar la serie
        $mes = obtenerMesPorTrimestre($certificado_datos['trimestre']);
        $serie = generarSerie($certificado_datos['anio'], $certificado_datos['trimestre'], $certificado_datos['rut']);
        $certificado_datos['serie'] = $serie;
    } else {
        logMessage("No se encontraron datos para el alumno_id: $alumno_id y curso_id: $curso_id");
        return false;
    }

    try {
        ob_start();

        // Definir el contenido HTML del certificado
        $html_content = '
        <style>
            @font-face { font-family: Cinzel; }
            body { margin: 0; font-family: Cinzel, Arial, sans-serif; }
            .certificado-container { position: absolute; width: 11.0156in; height: 8.0059in; }
            .texto-centrado { text-align: center; }
        </style>
        <page backtop="0" backbottom="0" backleft="0" backright="0">
            <div class="certificado">
                <!-- Fondo del certificado -->
                <img class="absolute" src="images/image388.png" style="width: 279.4mm; height: 215.9mm;">
                
                <!-- Logo -->
                <img src="images/image315.png" style="position:absolute; left:360px; top:69px; width:336px; height:114px;">
                
                <!-- Texto del certificado -->
                <div style="position:absolute; left:48px; top:185px; width:940px; height:48px;" class="texto-centrado">
                    <p style="font-size:24pt;">INSTITUTO CHILENO</p>
                </div>
                <div style="position:absolute; left:57px; top:215px; width:946px; height:48px;" class="texto-centrado">
                    <p style="font-size:24pt;">Norteamericano de Cultura</p>
                </div>
                <div style="position:absolute; left:57px; top:245px; width:946px; height:48px;" class="texto-centrado">
                    <p style="font-size:24pt;">De Concepcion</p>
                </div>
                <div style="position:absolute; left:54px; top:335px; width:946px; height:26px;" class="texto-centrado">
                    <p style="font-size:16pt;">We here certify that</p>
                </div>
                
                <!-- Nombre del estudiante -->
                <div style="position:absolute; left:48px; top:350px; width:960px; height:97px;" class="texto-centrado">
                    <p style="font-size:36pt; font-weight:bold;">
                        ' . htmlspecialchars($certificado_datos['nombre'] . ' ' . $certificado_datos['apellido_p'] . ' ' . $certificado_datos['apellido_m']) . '
                    </p>
                </div>
                
                <!-- RUT -->
                <div style="position:absolute; left:48px; top:420px; width:960px; height:97px;" class="texto-centrado">
                    <p style="font-size:25pt;">National id: ' . htmlspecialchars($certificado_datos['rut']) . '</p>
                </div>
                
                <!-- Detalles del curso -->
                <div style="position:absolute; left:48px; top:490px; width:960px; height:63px;" class="texto-centrado">
                    <p style="font-size: 18pt;">
                        has successfully completed the ' . htmlspecialchars($certificado_datos['horas']) . ' hour ' . htmlspecialchars($certificado_datos['curso']) . ' Course,<br>
                        corresponding to CEFR level ' . htmlspecialchars($certificado_datos['cefr']) . ' during the ' . htmlspecialchars($certificado_datos['trimestre']) . ' of ' . htmlspecialchars($certificado_datos['anio']) . '.
                    </p>
                </div>
                
                <!-- Nota -->
                <div style="position:absolute; left:48px; top:545px; width:960px; height:44px;" class="texto-centrado">
                    <p style="font-size: 18pt;">The bearer obtained ' . formatearNota($certificado_datos['nota']) . '  in a 1.0 to 7.0 scale, </p>
                </div>
                
                <!-- Fecha y lugar -->
                <div style="position:absolute; left:120px; top:681px; width:305px; height:37px;" class="texto-centrado">
                    <p style="font-size: 11pt;">CONCEPCIÓN, ' . mb_strtoupper($mes, 'UTF-8') . ' ' . htmlspecialchars($certificado_datos['anio']) . '</p>
                </div>
                
                <!-- Timbre -->
                <div style="position:absolute; left:450px; top:600px;">
                    <img src="images/image326.png" style="width:235px; height:auto;">
                </div>
                
                <!-- Serie -->
                <div style="position:absolute; left:715px; top:681px; width:228px; height:24px;">
                    <p>SERIE: ' . htmlspecialchars($certificado_datos['serie']) . '</p>
                </div>
                
                <!-- Verificación -->
                <div style="position:absolute; left:200px; top:774px; width:1200px; height:18px;">
                    <p style="font-size: 8pt; font-family:Calibri;">Puedes verificar la validez de este certificado en https://norteamericanoconcepcion.cl/certificacion</p>
                </div>
            </div>
        </page>';
        

        $html2pdf = new Html2Pdf('L', array(280, 220), 'es', true, 'UTF-8', array(1, 1, 1, 1));
        $html2pdf->pdf->SetDisplayMode('fullpage');
        $html2pdf->setDefaultFont('Cinzel');
        $html2pdf->writeHTML($html_content);

        return $html2pdf->output('', 'S');
    } catch (Html2PdfException $e) {
        $formatter = new ExceptionFormatter($e);
        logMessage($formatter->getHtmlMessage());
        return false;
    }
}


function enviarCertificadoPorEmail($alumnoId, $cursoId, $pdo)
{
    // Generar el PDF del certificado
    $pdfContent = generarCertificadoPDF($alumnoId, $cursoId, $pdo);

    if (!$pdfContent) {
        error_log("No se pudo generar el certificado para alumno_id: $alumnoId, curso_id: $cursoId");
        return false;
    }

    // Obtener el email del estudiante
    $stmt = $pdo->prepare("SELECT email FROM estudiantes WHERE id = ?");
    $stmt->execute([$alumnoId]);
    $email = $stmt->fetchColumn();

    if (!$email) {
        error_log("No se encontró email para alumno_id: $alumnoId");
        return false;
    }

    // Configurar el email
    $to = $email;
    $subject = "Tu certificado de curso";
    $message = "Adjunto encontrarás tu certificado de curso. Felicitaciones por completar el curso exitosamente.";

    // Generar un límite único para el email multipart
    $boundary = md5(time());

    // Cabeceras del email
    $headers = "From: certificados@norteamericanoconcepcion.cl\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";

    // Cuerpo del email
    $body = "--" . $boundary . "\r\n";
    $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($message));

    // Adjuntar el PDF
    $body .= "--" . $boundary . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"certificado.pdf\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment\r\n\r\n";
    $body .= chunk_split(base64_encode($pdfContent));

    $body .= "--" . $boundary . "--";

    // Enviar el email
    if (mail($to, $subject, $body, $headers)) {
        return true;
    } else {
        error_log("Error al enviar email a: $email para alumno_id: $alumnoId");
        return false;
    }
}

function generatePagination($total_items, $current_page, $limit, $url_params = '')
{
    $output = '';
    if ($total_items > 0) {
        $total_pages = ceil($total_items / $limit);
        $visible_pages = 5;
        $start_page = max(1, $current_page - floor($visible_pages / 2));
        $end_page = min($total_pages, $start_page + $visible_pages - 1);
        $start_page = max(1, $end_page - $visible_pages + 1);

        $output .= '<nav aria-label="Page navigation" class="my-4">';
        $output .= '<ul class="pagination pagination-lg justify-content-center">';

        // Botón "Anterior"
        $prev_disabled = ($current_page == 1) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $prev_disabled . '">';
        $output .= '<a class="page-link rounded-circle d-flex align-items-center justify-content-center" href="?page=' . ($current_page - 1) . $url_params . '" aria-label="Previous" style="width: 50px; height: 50px;">';
        $output .= '<span aria-hidden="true">&laquo;</span>';
        $output .= '</a></li>';

        // Números de página
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = ($i == $current_page) ? 'active' : '';
            $scale = ($i == $current_page) ? 'transform: scale(1.2);' : '';
            $output .= '<li class="page-item ' . $active . '">';
            $output .= '<a class="page-link rounded-circle d-flex align-items-center justify-content-center mx-1" href="?page=' . $i . $url_params . '" style="width: 50px; height: 50px; transition: all 0.3s ease; ' . $scale . '">' . $i . '</a>';
            $output .= '</li>';
        }

        // Botón "Siguiente"
        $next_disabled = ($current_page == $total_pages) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $next_disabled . '">';
        $output .= '<a class="page-link rounded-circle d-flex align-items-center justify-content-center" href="?page=' . ($current_page + 1) . $url_params . '" aria-label="Next" style="width: 50px; height: 50px;">';
        $output .= '<span aria-hidden="true">&raquo;</span>';
        $output .= '</a></li>';

        $output .= '</ul>';
        $output .= '</nav>';
    }
    return $output;
}

// Función para generar o actualizar certificados buscar_certificado.php
function actualizarCertificados($pdo)
{
    $query = "SELECT m.id AS matricula_id, m.estudiante_id, m.curso_id, n.final_grade, c.course, c.cefr, c.categoria, c.horas, c.trimestre, c.anio, c.profesor, m.certificado_id
              FROM matriculas m
              JOIN notas n ON m.notas_id = n.id
              JOIN cursos c ON m.curso_id = c.id
              WHERE m.certificado_id IS NULL OR (
                  SELECT nota FROM certificados WHERE id = m.certificado_id
              ) != n.final_grade";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($resultados as $resultado) {
        $pdo->beginTransaction();
        try {
            if ($resultado['certificado_id'] === null) {
                // Insertar nuevo certificado
                $insertQuery = "INSERT INTO certificados (estudiante_id, nota, curso, cefr, horas, trimestre, categoria, curso_siguiente, profesor, anio)
                                VALUES (:estudiante_id, :nota, :curso, :cefr, :horas, :trimestre, :categoria, :curso_siguiente, :profesor, :anio)";

                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([
                    ':estudiante_id' => $resultado['estudiante_id'],
                    ':nota' => $resultado['final_grade'],
                    ':curso' => $resultado['course'],
                    ':cefr' => $resultado['cefr'],
                    ':horas' => $resultado['horas'],
                    ':trimestre' => $resultado['trimestre'],
                    ':categoria' => $resultado['categoria'],
                    ':curso_siguiente' => '', // Añadir lógica para determinar el curso siguiente si es necesario
                    ':profesor' => $resultado['profesor'],
                    ':anio' => $resultado['anio']
                ]);

                $certificado_id = $pdo->lastInsertId();
            } else {
                // Actualizar certificado existente
                $updateQuery = "UPDATE certificados SET
                                nota = :nota, cefr = :cefr, horas = :horas, trimestre = :trimestre, 
                                categoria = :categoria, curso_siguiente = :curso_siguiente, 
                                profesor = :profesor, anio = :anio
                                WHERE id = :certificado_id";

                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([
                    ':nota' => $resultado['final_grade'],
                    ':cefr' => $resultado['cefr'],
                    ':horas' => $resultado['horas'],
                    ':trimestre' => $resultado['trimestre'],
                    ':categoria' => $resultado['categoria'],
                    ':curso_siguiente' => '',
                    ':profesor' => $resultado['profesor'],
                    ':anio' => $resultado['anio'],
                    ':certificado_id' => $resultado['certificado_id']
                ]);

                $certificado_id = $resultado['certificado_id'];
            }

            // Actualizar la matrícula con el ID del certificado
            $updateMatriculaQuery = "UPDATE matriculas SET certificado_id = :certificado_id WHERE id = :matricula_id";
            $updateMatriculaStmt = $pdo->prepare($updateMatriculaQuery);
            $updateMatriculaStmt->execute([
                ':certificado_id' => $certificado_id,
                ':matricula_id' => $resultado['matricula_id']
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error al actualizar certificado: " . $e->getMessage());
        }
    }
}

function countEnrolledStudents($conn, $anio, $trimestre) {
    $sql = "SELECT COUNT(DISTINCT m.estudiante_id) as count
            FROM matriculas m
            JOIN cursos c ON m.curso_id = c.id
            WHERE c.anio = ? AND c.trimestre = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $anio, $trimestre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}


