<?php
session_start();

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/utilities.php';
require_once 'includes/auth.php';
check_role('admin');

if (!isset($_SESSION['token']) || !validate_jwt($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}


$ficha_datos = [];

if (isset($_GET['id'])) {
    $id_matricula = $_GET['id'];

    $sql = "SELECT e.rut, e.nombre, e.apellido_p, e.apellido_m, e.telefono, e.email, 
                   m.fecha_matricula, c.course, c.horario, c.trimestre, c.anio,
                   c.categoria, c.modalidad
            FROM estudiantes e
            JOIN matriculas m ON e.id = m.estudiante_id
            JOIN cursos c ON m.curso_id = c.id
            WHERE m.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_matricula]);
    $ficha_datos = $stmt->fetch(PDO::FETCH_ASSOC);

}



include 'includes/header.php';
?>

<?php if ($ficha_datos): ?>
    
    <p>&nbsp;</p>
    <center>
        <button onclick="printFicha()" class="btn btn-primary">Imprimir Ficha</button>
        <p class="mt-2 mb-2">Número de matriculado en <?php echo $trimestre . " " . $anio; ?>: <strong><?php echo $numeroMatriculado; ?></strong></p>
        <button type="button" class="btn btn-success" onclick="window.location.href='dashboard.php'">Proceso completado</button>
    </center>
    <p>&nbsp;</p>

    <main class="container-md">
    <div id="fichaMatricula">
        <!DOCTYPE html>
        <html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
            xmlns="http://www.w3.org/TR/REC-html40">

        <head>
            <meta http-equiv=Content-Type content="text/html; charset=utf-8">
            <meta name=ProgId content=Word.Document>
            <meta name=Generator content="Microsoft Word 15">
            <meta name=Originator content="Microsoft Word 15">
            <link rel=File-List href="Ficha_Plantilla_archivos/filelist.xml">
            <link rel=Edit-Time-Data href="Ficha_Plantilla_archivos/editdata.mso">
            <link rel=dataStoreItem href="Ficha_Plantilla_archivos/item0008.xml"
                target="Ficha_Plantilla_archivos/props009.xml">
            <link rel=themeData href="Ficha_Plantilla_archivos/themedata.thmx">
            <link rel=colorSchemeMapping href="Ficha_Plantilla_archivos/colorschememapping.xml">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

            <style>
                /* Estilos generales */
                body {
                    font-family: "Segoe UI", sans-serif;
                    font-size: 11pt;
                    line-height: 1.15;
                }

                .container-md {
                    max-width: 750px;
                    margin: auto;
                    padding: 20px;
                    box-shadow: 0 4px 6px rgba(212, 7, 7, 0.1);
                }

                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }

                .header img {
                    width: 300px;
                    height: auto;
                }

                h2 {
                    text-align: center;
                    color: #365F91;
                    font-size: 13pt;
                    font-weight: normal;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 50px;
                }

                table,
                th,
                td {
                    border: 1px solid #ccc;
                    padding: 8px;
                }

                th {
                    background-color: #f4f4f4;
                }

                /* Estilos para impresión */
                @media print {
                    body * {
                        visibility: hidden;
                    }

                    #fichaMatricula,
                    #fichaMatricula * {
                        visibility: visible;
                    }

                    #fichaMatricula {
                        position: absolute;
                        left: 0;
                        top: 0;
                    }
                }

                /* Estilos específicos de Word */
                .MsoNormal {
                    margin: 0cm;
                    line-height: 115%;
                }

                .MsoListParagraph {
                    margin-left: 36pt;
                }

                /* Estilos para enlaces */

                span.MsoHyperlink {
                    color: blue;
                    text-decoration: underline;
                }

                a:visited,
                span.MsoHyperlinkFollowed {
                    color: purple;
                    text-decoration: underline;
                }

                /* Estilos para comentarios */
                .MsoCommentText,
                .MsoCommentSubject {
                    font-size: 10pt;
                }

                /* Definiciones de página */
                @page WordSection1 {
                    size: 200pt 192pt;
                    margin: 14.2pt 42.55pt 28.4pt 42.55pt;
                }

                div.WordSection1 {
                    page: WordSection1;
                }
            </style>
        </head>

        <body lang="ES-CL" link="blue" vlink="purple" style="tab-interval:35.4pt;word-wrap:break-word">
            <div class="WordSection1">
                <section class="container-ficha">
                    <p class="MsoNormal" align="center" style="text-align:center">
                        <center><img src="images/logoficha.png" alt="Logo" style="height: 90px; margin-top: 10px;">
                        </center>
                    </p>
                    <p class="MsoNormal" align="center" style="text-align:center">
                        <b style="mso-bidi-font-weight:normal">
                            <span style="font-size:12.0pt;line-height:115%;mso-ansi-language:ES-CL">FICHA MATRÍCULA
                                NORTEAMERICANO CONCEPCIÓN</span>
                        </b>
                    </p>
                    <p class="MsoNormal" align="center" style="text-align:center">
                        <b style="mso-bidi-font-weight:normal">
                            <span style="font-size:12.0pt;line-height:115%;mso-ansi-language:ES-CL">
                                <?php echo htmlspecialchars($ficha_datos['trimestre']); ?> Quarter 
                                <?php echo htmlspecialchars($ficha_datos['anio']); ?>
                            </span>
                        </b>
                    </p>
                    <p> </p>
                    <p class="MsoNormal" style="text-align:justify">
                        <span style="font-size:10.0pt;line-height:115%;mso-ansi-language:ES-CL">
                            Por favor completar la siguiente ficha de matrícula por cada alumno. En el caso de los
                            alumnos de los programas
                            niños (<span class="SpellE">Kids</span>) y adolescentes (<span class="SpellE">Teens</span>)
                            se solicita, además, completar la información del apoderado.
                        </span>
                    </p>
                    <p></p>
                    <p class="MsoNormal" style="text-align:justify">
                        <b style="mso-bidi-font-weight:normal">
                            <span style="font-size:10.0pt;line-height:115%;color:red;mso-ansi-language:ES-CL">
                                Sin esta ficha de matrícula, no será posible contactar a los alumnos y/o apoderados en
                                caso de ser necesario.
                            </span>
                        </b>
                    </p>

                    <ul class="MsoListParagraphCxSpFirst"
                        style="text-align:justify;text-indent:12pt;mso-list:l0 level1 lfo2">
                        <li style="font-size:10.0pt;line-height:115%;mso-ansi-language:ES-CL">
                            Cabe mencionar que toda solicitud de POSTERGACIÓN de un curso será aceptada sólo si el
                            alumno alcanzó a participar a menos del 50% de las clases.
                        </li>
                    </ul>

                    <ul class="MsoListParagraphCxSpLast"
                        style="text-align:justify;text-indent:12pt;mso-list:l0 level1 lfo2">
                        <li style="font-size:10.0pt;line-height:115%;mso-ansi-language:ES-CL">
                            La solicitud de DEVOLUCION de dinero será autorizada sólo en casos de enfermedad del alumno
                            o pérdida de su fuente laboral (se debe
                            adjuntar documentación que lo acredite) hasta dos meses después de iniciado el curso y en
                            este caso, previo a la devolución, se descontarán las clases en las que participó el alumno.
                        </li>
                    </ul>
                    <p>&nbsp;</p>

                    <p class="MsoNormal"><b style="mso-bidi-font-weight:normal"><u><span
                                    style="mso-ansi-language:ES-CL">DATOS DEL ALUMNO</span></u></b></p>
                    <p> </p>
                    <div align="center">
                        <table class=MsoTableGrid border=0 cellspacing=0 cellpadding=0
                            style='border-collapse:collapse;border:none;mso-yfti-tbllook:1184;mso-padding-alt: 0cm 5.4pt 0cm 5.4pt;mso-border-insideh:none;mso-border-insidev:none'>
                            <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Nombre
                                            Completo<b style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['nombre']); ?>
                                                    <?php echo htmlspecialchars($ficha_datos['apellido_p']); ?>
                                                    <?php echo htmlspecialchars($ficha_datos['apellido_m']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:1'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>RUT<b style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['rut']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:2'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Teléfono(s)<b
                                                style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['telefono']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:3'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Correo
                                            Electrónico<b style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['email']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:4'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Curso<b style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['course']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:5'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Horario<b
                                                style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['horario']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:6'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Trimestre<b
                                                style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <?php echo htmlspecialchars($ficha_datos['trimestre']); ?>
                                                    <?php echo htmlspecialchars($ficha_datos['anio']); ?>
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:7;mso-yfti-lastrow:yes'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Firma<b style='mso-bidi-font-weight:normal'><u>
                                                    <o:p></o:p>
                                                </u></b></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><b
                                            style='mso-bidi-font-weight:normal'><u><span style='mso-ansi-language:ES-CL'>:
                                                    <o:p></o:p>
                                                </span></u></b></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <p class="MsoNormal"><b style="mso-bidi-font-weight:normal"><u><span
                                    style="mso-ansi-language:ES-CL">INFORMACIÓN APODERADO</span></u></b><b
                            style="mso-bidi-font-weight:normal"><span style="mso-ansi-language:ES-CL"> (Cursos <span
                                    class="SpellE">Kids</span> y <span class="SpellE">Teens</span>):</span></b></p>
                    <p></p>
                    <div align="center">
                        <table class="MsoTableGrid" border="0" cellspacing="0" cellpadding="0"
                            style="border-collapse:collapse;border:none;mso-yfti-tbllook:1184;mso-padding-alt: 0cm 5.4pt 0cm 5.4pt;mso-border-insideh:none;mso-border-insidev:none">
                            <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Nombre Apoderado<o:p></o:p></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span style='mso-ansi-language:ES-CL'>:
                                            <o:p></o:p>
                                        </span></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:1'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Teléfono(s)<o:p></o:p></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span style='mso-ansi-language:ES-CL'>:
                                            <o:p></o:p>
                                        </span></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:2'>
                                <td width=160 valign=top style='width:120.25pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span
                                            style='mso-ansi-language:ES-CL'>Correo Electrónico<o:p></o:p></span></p>
                                </td>
                                <td width=542 valign=top style='width:406.15pt;padding:0cm 5.4pt 0cm 5.4pt'>
                                    <p class=MsoNormal style='line-height:normal'><span style='mso-ansi-language:ES-CL'>:
                                            <o:p></o:p>
                                        </span></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:3;mso-yfti-lastrow:yes'>
                        </table>
                    </div>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p class="MsoNormal" align="center" style="text-align:center"><b
                            style="mso-bidi-font-weight:normal"><u><span style="mso-ansi-language:ES-CL">INFORMACIÓN
                                    PAGO CURSO:</span></u></b></p>
                    <p>&nbsp;</p>

                    <div align="center">
                        <table class="MsoTableGrid" border="1" cellspacing="0" cellpadding="0" width="671"
                            style="width:503.0pt;border-collapse:collapse;border:none;mso-border-alt:solid windowtext .5pt; mso-yfti-tbllook:1184;mso-padding-alt:0cm 5.4pt 0cm 5.4pt">
                            <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Valor curso<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border:solid windowtext 1.0pt;border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border:solid windowtext 1.0pt;border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:1;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Monto cancelado<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:2;height:37.45pt'>
                                <td width=137 rowspan=2
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Forma de pago<o:p></o:p></span></b></p>
                                </td>
                                <td width=103
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>EFECTIVO<o:p></o:p></span></b></p>
                                </td>
                                <td width=137
                                    style='width:102.85pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>TRANSFERENCIA<o:p></o:p></span></b></p>
                                </td>
                                <td width=81
                                    style='width:60.7pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>TARJETA<o:p></o:p></span></b></p>
                                </td>
                                <td width=103
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>FLOW<o:p></o:p></span></b></p>
                                </td>
                                <td width=109
                                    style='width:82.1pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>CUOTAS MENSUALES<o:p></o:p></span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:3;height:37.45pt'>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=137 valign=top
                                    style='width:102.85pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=81 valign=top
                                    style='width:60.7pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=109 valign=top
                                    style='width:82.1pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:4;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Fecha de pago<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:5;mso-yfti-lastrow:yes;height:40.65pt'>
                        </table>
                    </div>

                    <p class="MsoNormal" align="center" style="text-align:center"><b
                            style="mso-bidi-font-weight:normal"><u><span style="mso-ansi-language:ES-CL">INFORMACIÓN
                                    PAGO TEXTO:</span></u></b></p>
                    <p>&nbsp;</p>
                    <div align="center">
                        <table class="MsoTableGrid" border="1" cellspacing="0" cellpadding="0" width="671"
                            style="width:503.0pt;border-collapse:collapse;border:none;mso-border-alt:solid windowtext .5pt; mso-yfti-tbllook:1184;mso-padding-alt:0cm 5.4pt 0cm 5.4pt">
                            <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Valor curso<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border:solid windowtext 1.0pt;border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border:solid windowtext 1.0pt;border-left:none;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:1;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Monto cancelado<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:2;height:37.45pt'>
                                <td width=137 rowspan=2
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Forma de pago<o:p></o:p></span></b></p>
                                </td>
                                <td width=103
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>EFECTIVO<o:p></o:p></span></b></p>
                                </td>
                                <td width=137
                                    style='width:102.85pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>TRANSFERENCIA<o:p></o:p></span></b></p>
                                </td>
                                <td width=81
                                    style='width:60.7pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>TARJETA<o:p></o:p></span></b></p>
                                </td>
                                <td width=103
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>FLOW<o:p></o:p></span></b></p>
                                </td>
                                <td width=109
                                    style='width:82.1pt;border-top:none;border-left:none; border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>CUOTAS MENSUALES<o:p></o:p></span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:3;height:37.45pt'>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=137 valign=top
                                    style='width:102.85pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=81 valign=top
                                    style='width:60.7pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=109 valign=top
                                    style='width:82.1pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:37.45pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:4;height:40.65pt'>
                                <td width=137
                                    style='width:102.85pt;border:solid windowtext 1.0pt;border-top:none;mso-border-top-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>Fecha de pago<o:p></o:p></span></b></p>
                                </td>
                                <td width=103 valign=top
                                    style='width:77.25pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                                <td width=431 colspan=4 valign=top
                                    style='width:322.9pt;border-top:none;border-left:none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;mso-border-top-alt:solid windowtext .5pt;mso-border-left-alt:solid windowtext .5pt;mso-border-alt:solid windowtext .5pt;padding:0cm 5.4pt 0cm 5.4pt;height:40.65pt'>
                                    <p class=MsoNormal align=center style='text-align:center;line-height:normal'><b><span
                                                style='mso-ansi-language:ES-CL'>
                                                <o:p>&nbsp;</o:p>
                                            </span></b></p>
                                </td>
                            </tr>
                            <tr style='mso-yfti-irow:5;mso-yfti-lastrowS:yes;height:40.65pt'>
                        </table>
                    </div>

                    <p class="MsoNormal" align="center" style="text-align:center">
                        <b style="mso-bidi-font-weight:normal"><u><span
                                    style="mso-ansi-language:ES-CL">____________________________________________</span></u></b>
                    </p>

                    <p class="MsoNormal" align="center" style="text-align:center">
                        <b style="mso-bidi-font-weight:normal"><span style="mso-ansi-language:ES-CL">Timbre
                                contabilidad</span></b>
                    </p>
                </section>
            </div>
        </body>

        </html>

        <script>
            function printFicha() {
                window.print();
            }
        </script>
    </div>
    </main>

<?php else: ?>
    <p>No se encontraron datos para el estudiante.</p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>