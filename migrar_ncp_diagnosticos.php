<?php
/**
 * migrar_ncp_diagnosticos.php
 * Catálogo de diagnósticos nutricionales NCP/PES (bilingüe) para insertar en
 * la nota de la consulta. Crea la tabla ncp_diagnosticos y la siembra con los
 * casos de los PDFs (formato PES: Problema / Etiología / Signos + Intervención
 * + Monitoreo). Idempotente. SISTEMA-only, POST-driven.
 */
session_start();
require_once("class/funciones.php");
require_once("class/conexionBD.php");
$conexion = conectarse();
if ($conexion) { $conexion->set_charset('utf8mb4'); }
if (!isset($_SESSION["rol"]) || strtoupper($_SESSION["rol"]) !== 'SISTEMA') {
    http_response_code(403); exit('Acceso restringido: sólo SISTEMA.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Diagnósticos NCP/PES</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:640px;">
<h4>Catálogo de diagnósticos NCP/PES</h4>
<p class="text-muted">Crea <code>ncp_diagnosticos</code> y la siembra con los casos de los PDFs
(Problema / Etiología / Signos + Intervención + Monitoreo, bilingüe). Idempotente.</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar</button>
<a class="btn btn-link" href="ncp_diagnosticos_admin.php">Ir al catálogo</a></form></div></body></html>
    <?php
    exit;
}
$msgs = [];
$ok = $conexion->query("CREATE TABLE IF NOT EXISTS ncp_diagnosticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enfermedad_es VARCHAR(160) NOT NULL,
    enfermedad_en VARCHAR(160) NOT NULL,
    codigo VARCHAR(40) NULL,
    problema_es VARCHAR(400) NULL, problema_en VARCHAR(400) NULL,
    etiologia_es VARCHAR(600) NULL, etiologia_en VARCHAR(600) NULL,
    signos_es VARCHAR(600) NULL, signos_en VARCHAR(600) NULL,
    intervencion_es TEXT NULL, intervencion_en TEXT NULL,
    monitoreo_es TEXT NULL, monitoreo_en TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$msgs[] = $ok ? ['OK','Tabla ncp_diagnosticos lista.'] : ['ERR',$conexion->error];

// Semillas (de los PDFs). Idempotente por enfermedad_en.
$seed = [
 ['Enfermedad Renal Crónica (Estadio 4)','Chronic Kidney Disease (Stage 4)',null,
  'Ingesta excesiva de minerales (Potasio/Fósforo)','Excessive mineral intake (Potassium/Phosphorus)',
  'Relacionado con alteración en la excreción renal e ingesta dietética no supervisada','Related to impaired renal excretion and unmonitored dietary intake',
  'Evidenciado por K+ sérico 5.6 mEq/L y Fósforo 5.8 mg/dL','As evidenced by serum K+ 5.6 mEq/L and serum Phos 5.8 mg/dL',
  'Limitar proteína a 0.6–0.8 g/kg/día; diseñar plan bajo en potasio y fósforo.','Limit protein to 0.6–0.8 g/kg/d; prescribe low-phosphorus/potassium meal plan.',
  'Monitorear electrólitos y perfil renal cada 4 semanas.','Monitor serum electrolytes & renal panel every 4 weeks.'],
 ['Insuficiencia Cardíaca Congestiva','Congestive Heart Failure',null,
  'Ingesta excesiva de sodio y líquidos','Excessive sodium and fluid intake',
  'Relacionado con consumo frecuente de alimentos procesados y falta de adherencia a la restricción hídrica','Related to frequent consumption of high-sodium convenience foods and lack of fluid restriction adherence',
  'Evidenciado por ganancia de 3 kg en 5 días y edema periférico 2+','As evidenced by 3 kg fluid weight gain in 5 days and 2+ peripheral edema',
  'Prescribir dieta restringida a 2,000 mg Sodio y 1.5–2.0 L de líquidos.','Prescribe 2,000 mg Sodium & 1.5–2.0 L fluid restriction diet.',
  'Monitorear peso diario en ayunas y grado de edema semanalmente.','Track daily morning weight and edema status weekly.'],
 ['Enfermedad Hepática Esteatósica (MASLD/NAFLD)','Steatotic Liver Disease (MASLD/NAFLD)',null,
  'Sobrepeso/Obesidad (Clase II)','Overweight/Obesity (Class II)',
  'Relacionado con ingesta energética excesiva de fructosa refinada/grasas e inactividad física','Related to excessive energy intake from refined fructose/fats and physical inactivity',
  'Evidenciado por IMC 34.2 kg/m², enzimas hepáticas elevadas y ecografía de esteatosis','As evidenced by BMI 34.2 kg/m², elevated liver enzymes, and ultrasound confirming hepatic steatosis',
  'Déficit calórico (reducción de 500-700 kcal) con patrón Mediterráneo.','Caloric deficit plan (500-700 kcal reduction) emphasizing Mediterranean style patterns.',
  'Meta de reducción del 7-10% de peso en 6 meses; evaluar enzimas hepáticas cada 3 meses.','Target 7-10% weight reduction over 6 months; monitor liver panel every 3 months.'],
 ['Enfermedad Inflamatoria Intestinal (Brote de Crohn)','Inflammatory Bowel Disease (Crohn\'s Flare)',null,
  'Función gastrointestinal alterada','Altered GI function',
  'Relacionado con inflamación mucosa activa y malabsorción secundaria a brote de Crohn','Related to active mucosal inflammation and malabsorption secondary to Crohn\'s flare',
  'Evidenciado por diarrea crónica, pérdida de 5 kg y albúmina sérica de 2.8 g/dL','As evidenced by chronic diarrhea, 5 kg weight loss, and serum albumin of 2.8 g/dL',
  'Dieta baja en residuo/FODMAP, hiperproteica con fórmulas hidrolizadas y suplementación de B12.','Low-residue/low-FODMAP, high-protein diet with hydrolyzed polymeric formulas & B12 supplementation.',
  'Monitorear frecuencia/consistencia de deposiciones y peso semanal.','Track stool frequency/consistency and weekly body weight.'],
 ['Diabetes Tipo 2','Type 2 Diabetes',null,
  'Ingesta inconsistente de carbohidratos','Inconsistent carbohydrate intake',
  'Relacionado con déficit de conocimientos sobre el automanejo de la diabetes','Related to food and nutrition-related knowledge deficit regarding diabetes self-management',
  'Evidenciado por HbA1c de 9.2% y consumo autoreportado de 3-4 bebidas azucaradas al día','As evidenced by HbA1c of 9.2% and self-reported consumption of 3-4 sugar-sweetened beverages daily',
  'Brindar educación individual sobre conteo de carbohidratos y sustitución de bebidas; establecer plan de 45-60g de carbohidratos por comida principal.','Provide individual education on carbohydrate counting and beverage substitution; establish a meal plan with 45-60g carbs per main meal.',
  'Seguimiento en 4 semanas para revisar diario de alimentos y glucosa; meta de HbA1c < 7.0% en 3 meses.','Follow up in 4 weeks to review food/glucose log; target HbA1c < 7.0% in 3 months.'],
 ['Oncología Aguda','Acute Oncology',null,
  'Ingesta oral inadecuada','Inadequate oral intake',
  'Relacionado con mucositis oral inducida por radiación y odinofagia severa','Related to radiation-induced oral mucositis and severe painful swallowing',
  'Evidenciado por pérdida de peso del 8% en 1 mes e ingesta energética < 50% por > 2 semanas','As evidenced by 8% weight loss in 1 month and energy intake < 50% of needs for > 2 weeks',
  'Recomendar SNO líquidos/suaves hiperproteicos; iniciar batidos densos en nutrientes fríos o a temperatura ambiente, pequeños y frecuentes.','Recommend soft/liquid high-protein oral nutrition supplements (ONS); initiate small, frequent cold or room-temperature nutrient-dense shakes.',
  'Monitorear peso diario y registro de ingesta oral dos veces por semana; meta de estabilización de peso en 7-10 días.','Monitor daily weight and oral intake logs twice weekly; target weight stabilization within 7-10 days.'],
];
$chk = $conexion->prepare("SELECT id FROM ncp_diagnosticos WHERE enfermedad_en=? LIMIT 1");
$ins = $conexion->prepare("INSERT INTO ncp_diagnosticos
  (enfermedad_es, enfermedad_en, codigo, problema_es, problema_en, etiologia_es, etiologia_en,
   signos_es, signos_en, intervencion_es, intervencion_en, monitoreo_es, monitoreo_en, orden)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$nuevos = 0; $orden = 0;
foreach ($seed as $s) {
    $orden += 10;
    $chk->bind_param('s', $s[1]); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) continue;
    $ins->bind_param('sssssssssssssi',
        $s[0],$s[1],$s[2],$s[3],$s[4],$s[5],$s[6],$s[7],$s[8],$s[9],$s[10],$s[11],$s[12],$orden);
    if ($ins->execute()) $nuevos++;
}
$chk->close(); $ins->close();
$msgs[] = ['NEW',"Diagnósticos NCP sembrados nuevos: $nuevos (de ".count($seed).")."];

$cls=['NEW'=>'success','OK'=>'secondary','ERR'=>'danger'];
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4"><div class="container" style="max-width:640px;"><h4>Resultado</h4><ul class="list-group">';
foreach ($msgs as $m) echo '<li class="list-group-item"><span class="badge bg-'.($cls[$m[0]]??'secondary').' me-2">'.htmlspecialchars($m[0]).'</span>'.htmlspecialchars($m[1]).'</li>';
echo '</ul><a class="btn btn-primary mt-3" href="ncp_diagnosticos_admin.php">Ir al catálogo NCP/PES</a></div></body></html>';
