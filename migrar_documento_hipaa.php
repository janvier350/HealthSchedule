<?php
/**
 * migrar_documento_hipaa.php
 * Inserta (si no existe) el aviso legal HIPAA (español) en la tabla `documentos`
 * para que se pueda enviar y firmar a través del módulo Documents.
 * Idempotente. SISTEMA-only.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }

if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403);
    exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración HIPAA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Migrar documento — HIPAA Privacy Notice (Spanish)</h4>
<p class="text-muted">Inserta el aviso HIPAA en la tabla <code>documentos</code>. Va al módulo <b>Documents</b> (se envía y firma).</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="gestionar_documentos.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

$titulo = '(4) HIPAA Privacy Notice (Spanish)';
$contenido = <<<'HTML'
<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#2b2b2b;line-height:1.35;">
<h4 style="color:#5a2d82;margin:0 0 10px 0;">Notificación de Prácticas de Privacidad — SRoss Nutrition PLLC</h4>

<p><b>Su información. Sus derechos. Nuestras responsabilidades.</b><br>
Esta notificación describe cómo puede utilizarse y divulgarse su información médica, y cómo puede acceder usted a esta información. <b>Revísela con cuidado.</b></p>

<h6 style="color:#5a2d82;">Sus derechos</h6>

<p><b>Obtener una copia en formato electrónico o en papel de su historial médico</b></p>
<ul>
<li>Puede solicitar que le muestren o le entreguen una copia en formato electrónico o en papel de su historial médico y otra información médica que tengamos de usted. Pregúntenos cómo hacerlo.</li>
<li>Le entregaremos una copia o un resumen de su información médica, generalmente dentro de 30 días de su solicitud. Podemos cobrar un cargo razonable en base al costo.</li>
</ul>

<p><b>Solicitarnos que corrijamos su historial médico</b></p>
<ul>
<li>Puede solicitarnos que corrijamos la información médica sobre usted que piensa que es incorrecta o está incompleta. Pregúntenos cómo hacerlo.</li>
<li>Podemos decir "no" a su solicitud, pero le daremos una razón por escrito dentro de 60 días.</li>
</ul>

<p><b>Solicitar comunicaciones confidenciales</b></p>
<ul>
<li>Puede solicitarnos que nos comuniquemos con usted de una manera específica (por ejemplo, por teléfono particular o laboral) o que enviemos la correspondencia a una dirección diferente.</li>
<li>Le diremos "sí" a todas las solicitudes razonables.</li>
<li>Puede solicitarnos que no utilicemos ni compartamos determinada información médica para el tratamiento, pago o para nuestras operaciones. No estamos obligados a aceptar su solicitud, y podemos decir "no" si esto afectara su atención.</li>
<li>Si paga por un servicio o artículo de atención médica por cuenta propia en su totalidad, puede solicitarnos que no compartamos esa información con el propósito de pago o nuestras operaciones con su aseguradora médica. Diremos "sí" a menos que una ley requiera que compartamos dicha información.</li>
</ul>

<p><b>Recibir una lista de aquellos con quienes hemos compartido información</b></p>
<ul>
<li>Puede solicitar una lista (informe) de las veces que hemos compartido su información médica durante los seis años previos a la fecha de su solicitud, con quién la hemos compartido y por qué.</li>
<li>Incluiremos todas las divulgaciones excepto aquellas sobre el tratamiento, pago y operaciones de atención médica, y otras divulgaciones determinadas (como cualquiera de las que usted nos haya solicitado hacer). Le proporcionaremos un informe gratis por año pero cobraremos un cargo razonable en base al costo si usted solicita otro dentro de los 12 meses.</li>
</ul>

<p><b>Obtener una copia de esta notificación de privacidad</b></p>
<ul><li>Puede solicitar una copia en papel de esta notificación en cualquier momento, incluso si acordó recibir la notificación de forma electrónica. Le proporcionaremos una copia en papel de inmediato.</li></ul>

<p><b>Elegir a alguien para que actúe en su nombre</b></p>
<ul>
<li>Si usted le ha otorgado a alguien la representación médica o si alguien es su tutor legal, aquella persona puede ejercer sus derechos y tomar decisiones sobre su información médica.</li>
<li>Nos aseguraremos de que la persona tenga esta autoridad y pueda actuar en su nombre antes de tomar cualquier medida.</li>
</ul>

<p><b>Presentar una queja si considera que se violaron sus derechos</b></p>
<ul>
<li>Si considera que hemos violado sus derechos, puede presentar una queja comunicándose con nosotros por medio de la información de la página 1.</li>
<li>Puede presentar una queja en la Oficina de Derechos Civiles del Departamento de Salud y Servicios Humanos enviando una carta a: Department of Health and Human Services, 200 Independence Avenue, S.W., Washington, D.C. 20201, llamando al 1-800-368-1019 o visitando su sitio web.</li>
<li>No tomaremos represalias en su contra por la presentación de una queja.</li>
</ul>

<h6 style="color:#5a2d82;">Sus opciones</h6>
<p><b>Para determinada información médica, puede decirnos sus decisiones sobre qué compartimos.</b> Si tiene una preferencia clara de cómo compartimos su información en las situaciones descritas debajo, comuníquese con nosotros. Díganos qué quiere que hagamos, y seguiremos sus instrucciones.</p>

<p><b>En estos casos, tiene tanto el derecho como la opción de pedirnos que:</b></p>
<ul>
<li>Compartamos información con su familia, amigos cercanos u otras personas involucradas en su atención.</li>
<li>Compartamos información en una situación de alivio en caso de una catástrofe.</li>
<li>Incluyamos su información en un directorio hospitalario. Si no puede decirnos su preferencia, por ejemplo, si se encuentra inconsciente, podemos seguir adelante y compartir su información si creemos que es para beneficio propio. También podemos compartir su información cuando sea necesario para reducir una amenaza grave e inminente a la salud o seguridad.</li>
</ul>

<p><b>En estos casos, nunca compartiremos su información a menos que nos entregue un permiso por escrito:</b></p>
<ul>
<li>Propósitos de mercadeo.</li>
<li>Venta de su información.</li>
<li>La mayoría de los casos en que se comparten notas de psicoterapia.</li>
</ul>

<p><b>En el caso de recaudación de fondos:</b></p>
<ul><li>Podemos comunicarnos con usted por temas de recaudación, pero puede pedirnos que no lo volvamos a contactar.</li></ul>

<h6 style="color:#5a2d82;">Nuestros usos y divulgaciones</h6>

<p><b>Tratamiento</b><br>
Podemos utilizar su información médica y compartirla con otros profesionales que lo estén tratando. <b>Ejemplo:</b> Un médico que lo está tratando por una lesión le consulta a otro doctor sobre su estado de salud general.</p>

<p><b>Dirigir nuestra organización</b><br>
Podemos utilizar y divulgar su información para llevar a cabo nuestra práctica, mejorar su atención y comunicarnos con usted cuando sea necesario. <b>Ejemplo:</b> Utilizamos información médica sobre usted para administrar su tratamiento y servicios.</p>

<p><b>Facturar por sus servicios</b><br>
Podemos utilizar y compartir su información para facturar y obtener el pago de los planes de salud y otras entidades. <b>Ejemplo:</b> Entregamos información acerca de usted a su plan de seguro médico para que éste pague por sus servicios.</p>

<h6 style="color:#5a2d82;">¿De qué otra manera podemos utilizar o compartir su información médica?</h6>
<p>Se nos permite o exige compartir su información de otras maneras (por lo general, de maneras que contribuyan al bien público, como la salud pública e investigaciones médicas). Tenemos que reunir muchas condiciones legales antes de poder compartir su información con dichos propósitos.</p>

<p><b>Ayudar con asuntos de salud pública y seguridad</b></p>
<ul>
<li>Prevención de enfermedades.</li>
<li>Ayuda con el retiro de productos del mercado.</li>
<li>Informe de reacciones adversas a los medicamentos.</li>
<li>Informe de sospecha de abuso, negligencia o violencia doméstica.</li>
<li>Prevención o reducción de amenaza grave hacia la salud o seguridad de alguien.</li>
</ul>

<p><b>Realizar investigaciones médicas</b> · <b>Cumplir con la ley</b> · <b>Responder a solicitudes de donación de órganos y tejidos</b> · <b>Trabajar con un médico forense o director funerario</b> · <b>Tratar la compensación de trabajadores, cumplimiento de la ley y otras solicitudes gubernamentales</b> · <b>Responder a demandas y acciones legales</b>.</p>

<h6 style="color:#5a2d82;">Nuestras responsabilidades</h6>
<ul>
<li>Estamos obligados por ley a mantener la privacidad y seguridad de su información médica protegida.</li>
<li>Le haremos saber de inmediato si ocurre un incumplimiento que pueda haber comprometido la privacidad o seguridad de su información.</li>
<li>Debemos seguir los deberes y prácticas de privacidad descritas en esta notificación y entregarle una copia de la misma.</li>
<li>No utilizaremos ni compartiremos su información de otra manera distinta a la aquí descrita, a menos que usted nos diga por escrito que podemos hacerlo. Si nos dice que podemos, puede cambiar de parecer en cualquier momento. Háganos saber por escrito si usted cambia de parecer.</li>
</ul>

<h6 style="color:#5a2d82;">Cambios a los términos de esta notificación</h6>
<p>Podemos modificar los términos de esta notificación, y los cambios se aplicarán a toda la información que tenemos sobre usted. La nueva notificación estará disponible según se solicite, en nuestra oficina, y en nuestro sitio web.</p>

<p><i>Esta Notificación de Prácticas de Privacidad se aplica a las siguientes organizaciones: <b>SRoss Nutrition PLLC</b>.</i></p>

<div style="margin-top:24px;border-top:1px solid #ccc;padding-top:12px;">
<b>Reconocimiento del paciente:</b> He recibido y leído esta Notificación de Prácticas de Privacidad y acepto sus términos.
</div>
</div>
HTML;

$stmt = $conexion->prepare("SELECT id_documento FROM documentos WHERE titulo = ? LIMIT 1");
$stmt->bind_param('s', $titulo);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Migración</title>'
   . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
   . '<body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4>';

if ($row) {
    $up = $conexion->prepare("UPDATE documentos SET contenido = ? WHERE id_documento = ?");
    $up->bind_param('si', $contenido, $row['id_documento']);
    echo $up->execute()
        ? '<div class="alert alert-info">Documento actualizado (id '.(int)$row['id_documento'].').</div>'
        : '<div class="alert alert-danger">ERROR: '.htmlspecialchars($up->error).'</div>';
    $up->close();
} else {
    $ins = $conexion->prepare("INSERT INTO documentos (titulo, contenido, archivo_pdf, estado) VALUES (?, ?, NULL, 1)");
    $ins->bind_param('ss', $titulo, $contenido);
    echo $ins->execute()
        ? '<div class="alert alert-success">Documento creado (id '.(int)$conexion->insert_id.').</div>'
        : '<div class="alert alert-danger">ERROR: '.htmlspecialchars($ins->error).'</div>';
    $ins->close();
}
echo '<a class="btn btn-primary mt-3" href="gestionar_documentos.php">Ir a Documents</a></div></body></html>';
