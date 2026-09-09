<?php
/**
 * migrar_icd10_nutricion.php
 * Ingresa el catálogo ICD-10 de Nutrición / MNT desde los 2 PDFs
 * de referencia:
 *   - icd10_coding_reference.pdf
 *   - comprehensive_nutrition_icd10.pdf
 *
 * Rangos (Z68.30-Z68.39, Z68.41-Z68.45, N18.1-N18.5, O24.4xx, etc.)
 * expandidos a códigos individuales para búsqueda directa.
 *
 * Se crean 6 categorías en ENFERMEDADES_DIAGNOSTICO (upsert por NOMBRE)
 * y cada código ICD-10 en ENFE_DIAG_COD (upsert por CODIGO, case-insensitive).
 *
 * Idempotente. SISTEMA-only, POST-driven.
 */
@ini_set('display_errors', '1');
error_reporting(E_ALL);

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
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migrar catálogo ICD-10 Nutrición</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:720px;">
<h4>Ingresar catálogo ICD-10 — Nutrición / MNT</h4>
<p class="text-muted">
Inserta 6 categorías y ~85 códigos ICD-10 clínicos y de facturación en
<code>ENFERMEDADES_DIAGNOSTICO</code> + <code>ENFE_DIAG_COD</code>.
Idempotente: si un código o categoría ya existen, no se duplican.
</p>
<form method="POST"><button class="btn btn-primary" type="submit">Ejecutar migración</button>
<a class="btn btn-link" href="PNC_CIE-10Crear.php">Volver</a></form></div></body></html>
    <?php
    exit;
}

/* ────────────────────────────────────────────────────────────────
 * Categorías (ENFERMEDADES_DIAGNOSTICO)
 * ──────────────────────────────────────────────────────────────── */
$categorias = [
    'MNT01' => ['nombre' => 'Overweight, Obesity & BMI',              'desc' => 'Sobrepeso, obesidad y códigos Z68 de BMI'],
    'MNT02' => ['nombre' => 'Diabetes Mellitus & Glucose Intolerance','desc' => 'Diabetes, prediabetes, hipoglucemia y GDM'],
    'MNT03' => ['nombre' => 'Malnutrition, Underweight & Deficiencies','desc' => 'Malnutrición, bajo peso, deficiencias vitamínicas y minerales'],
    'MNT04' => ['nombre' => 'Lipids, Cardiovascular & Renal',         'desc' => 'Dislipidemias, hipertensión, insuficiencia renal'],
    'MNT05' => ['nombre' => 'GI, Hepatic & Food Intolerances',        'desc' => 'Gastrointestinal, hepático, alergias e intolerancias'],
    'MNT06' => ['nombre' => 'Counseling, Eating Disorders & Preventive','desc' => 'Consejería dietética, trastornos alimentarios, preventiva'],
];

/* ────────────────────────────────────────────────────────────────
 * Códigos ICD-10 por categoría
 * ──────────────────────────────────────────────────────────────── */
$codigos = [
    'MNT01' => [
        ['E66.3',   'Overweight (BMI 25.0–29.9)'],
        ['E66.9',   'Obesity, unspecified'],
        ['E66.01',  'Morbid (severe) obesity due to excess calories'],
        ['E66.09',  'Other obesity due to excess calories'],
        ['E66.1',   'Drug-induced obesity'],
        ['E66.2',   'Morbid obesity with alveolar hypoventilation (Pickwickian)'],
        ['E66.8',   'Other obesity'],
        ['Z68.1',   'Body mass index (BMI) 19.9 or less, adult'],
        ['Z68.25',  'BMI 25.0–25.9, adult'],
        ['Z68.26',  'BMI 26.0–26.9, adult'],
        ['Z68.27',  'BMI 27.0–27.9, adult'],
        ['Z68.28',  'BMI 28.0–28.9, adult'],
        ['Z68.29',  'BMI 29.0–29.9, adult'],
        ['Z68.30',  'BMI 30.0–30.9, adult'],
        ['Z68.31',  'BMI 31.0–31.9, adult'],
        ['Z68.32',  'BMI 32.0–32.9, adult'],
        ['Z68.33',  'BMI 33.0–33.9, adult'],
        ['Z68.34',  'BMI 34.0–34.9, adult'],
        ['Z68.35',  'BMI 35.0–35.9, adult'],
        ['Z68.36',  'BMI 36.0–36.9, adult'],
        ['Z68.37',  'BMI 37.0–37.9, adult'],
        ['Z68.38',  'BMI 38.0–38.9, adult'],
        ['Z68.39',  'BMI 39.0–39.9, adult'],
        ['Z68.41',  'BMI 40.0–44.9, adult'],
        ['Z68.42',  'BMI 45.0–49.9, adult'],
        ['Z68.43',  'BMI 50.0–59.9, adult'],
        ['Z68.44',  'BMI 60.0–69.9, adult'],
        ['Z68.45',  'BMI 70.0 or greater, adult'],
        ['Z68.51',  'Pediatric BMI < 5th percentile for age'],
        ['Z68.52',  'Pediatric BMI 5th to <85th percentile for age'],
        ['Z68.53',  'Pediatric BMI 85th to <95th percentile for age'],
        ['Z68.54',  'Pediatric BMI ≥95th percentile for age'],
    ],
    'MNT02' => [
        ['E11.9',    'Type 2 diabetes mellitus without complications'],
        ['E11.65',   'Type 2 diabetes mellitus with hyperglycemia'],
        ['E11.69',   'Type 2 diabetes with other specified complication'],
        ['E10.9',    'Type 1 diabetes mellitus without complications'],
        ['E10.65',   'Type 1 diabetes mellitus with hyperglycemia'],
        ['R73.03',   'Prediabetes'],
        ['R73.01',   'Impaired fasting glucose (100–125 mg/dL)'],
        ['R73.02',   'Impaired glucose tolerance (oral, OGTT 140–199 mg/dL)'],
        ['O24.410',  'Gestational diabetes in pregnancy, diet controlled'],
        ['O24.414',  'Gestational diabetes in pregnancy, insulin controlled'],
        ['O24.415',  'Gestational diabetes in pregnancy, oral hypoglycemic controlled'],
        ['O24.419',  'Gestational diabetes in pregnancy, unspecified control'],
        ['E16.2',    'Hypoglycemia, unspecified'],
        ['E16.1',    'Other hypoglycemia (functional / reactive)'],
    ],
    'MNT03' => [
        ['E43',     'Unspecified severe protein-calorie malnutrition'],
        ['E44.0',   'Moderate protein-calorie malnutrition'],
        ['E44.1',   'Mild protein-calorie malnutrition'],
        ['E46',     'Unspecified protein-calorie malnutrition'],
        ['R63.6',   'Underweight'],
        ['R63.4',   'Abnormal weight loss'],
        ['E55.9',   'Vitamin D deficiency, unspecified'],
        ['E53.8',   'Deficiency of other specified B group vitamins (incl. folate / B12)'],
        ['E61.1',   'Iron deficiency (nutritional)'],
        ['D50.9',   'Iron deficiency anemia, unspecified'],
        ['E61.8',   'Deficiency of other specified nutrient elements (zinc, magnesium…)'],
    ],
    'MNT04' => [
        ['E78.00',  'Pure hypercholesterolemia, unspecified'],
        ['E78.1',   'Pure hyperglyceridemia (elevated triglycerides)'],
        ['E78.2',   'Mixed hyperlipidemia'],
        ['E78.5',   'Hyperlipidemia, unspecified'],
        ['I10',     'Essential (primary) hypertension'],
        ['I11.9',   'Hypertensive heart disease without heart failure'],
        ['N18.1',   'Chronic kidney disease, stage 1'],
        ['N18.2',   'Chronic kidney disease, stage 2 (mild)'],
        ['N18.30',  'Chronic kidney disease, stage 3, unspecified'],
        ['N18.31',  'Chronic kidney disease, stage 3a'],
        ['N18.32',  'Chronic kidney disease, stage 3b'],
        ['N18.4',   'Chronic kidney disease, stage 4 (severe)'],
        ['N18.5',   'Chronic kidney disease, stage 5 (excludes ESRD)'],
        ['N18.6',   'End stage renal disease (ESRD)'],
    ],
    'MNT05' => [
        ['K90.0',   'Celiac disease'],
        ['K90.41',  'Non-celiac gluten sensitivity'],
        ['K58.0',   'Irritable bowel syndrome with diarrhea (IBS-D)'],
        ['K58.1',   'Irritable bowel syndrome with constipation (IBS-C)'],
        ['K58.2',   'Mixed irritable bowel syndrome (IBS-M)'],
        ['K58.9',   'Irritable bowel syndrome without diarrhea'],
        ['K21.9',   'Gastro-esophageal reflux disease (GERD)'],
        ['K76.89',  'Other specified diseases of liver (NAFLD)'],
        ['K75.81',  'Nonalcoholic steatohepatitis (NASH)'],
        ['Z91.010', 'Allergy to peanuts'],
        ['Z91.011', 'Allergy to milk products'],
        ['Z91.018', 'Allergy to other foods (tree nut, soy, egg, shellfish…)'],
        ['E73.9',   'Lactose intolerance, unspecified'],
    ],
    'MNT06' => [
        ['Z71.3',   'Dietary counseling and surveillance'],
        ['Z71.89',  'Other specified counseling'],
        ['F50.00',  'Anorexia nervosa, unspecified'],
        ['F50.2',   'Bulimia nervosa'],
        ['F50.81',  'Binge eating disorder'],
        ['F50.9',   'Eating disorder, unspecified (ARFID / OSFED)'],
        ['R63.0',   'Anorexia (loss of appetite — symptom code)'],
        ['R63.2',   'Polyphagia (excessive eating / hyperphagia)'],
        ['Z98.84',  'Bariatric surgery status (post-gastric bypass / sleeve)'],
    ],
];

/* ────────────────────────────────────────────────────────────────
 * Ejecución
 * ──────────────────────────────────────────────────────────────── */
$msgs = [];

// 1) categorías: upsert por NOMBRE
$catIdByKey = [];   // 'MNT01' => id numérico
foreach ($categorias as $codigoCat => $data) {
    $chk = $conexion->prepare("SELECT ID_ENFERMEDAD FROM ENFERMEDADES_DIAGNOSTICO WHERE NOMBRE = ? LIMIT 1");
    $chk->bind_param('s', $data['nombre']);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($row) {
        $catIdByKey[$codigoCat] = (int)$row['ID_ENFERMEDAD'];
        $msgs[] = ['OK',  "Categoría ya existía (id {$row['ID_ENFERMEDAD']}): {$data['nombre']}"];
    } else {
        $ins = $conexion->prepare("INSERT INTO ENFERMEDADES_DIAGNOSTICO (CODIGO, NOMBRE, DESCRIPCION) VALUES (?, ?, ?)");
        $ins->bind_param('sss', $codigoCat, $data['nombre'], $data['desc']);
        if ($ins->execute()) {
            $catIdByKey[$codigoCat] = (int)$conexion->insert_id;
            $msgs[] = ['NEW', "Categoría CREADA (id {$conexion->insert_id}): {$data['nombre']}"];
        } else {
            $msgs[] = ['ERR', "Fallo creando categoría {$data['nombre']}: ".$ins->error];
        }
        $ins->close();
    }
}

// 2) códigos: upsert por CODIGO (case-insensitive)
$chkCod = $conexion->prepare("SELECT ID_ENFE_DIAG_COD FROM ENFE_DIAG_COD WHERE LOWER(TRIM(CODIGO)) = LOWER(TRIM(?)) LIMIT 1");
$updCod = $conexion->prepare("UPDATE ENFE_DIAG_COD SET DESCRIPCION = ?, ID_ENFERMEDAD = ? WHERE ID_ENFE_DIAG_COD = ?");
$insCod = $conexion->prepare("INSERT INTO ENFE_DIAG_COD (ID_ENFERMEDAD, CODIGO, DESCRIPCION) VALUES (?, ?, ?)");

$totNew = 0; $totOk = 0; $totErr = 0;
foreach ($codigos as $catKey => $lista) {
    if (!isset($catIdByKey[$catKey])) {
        $msgs[] = ['ERR', "Sin categoría para {$catKey} — saltando ".count($lista)." códigos"];
        $totErr += count($lista);
        continue;
    }
    $catId = $catIdByKey[$catKey];
    foreach ($lista as [$codigo, $descripcion]) {
        $chkCod->bind_param('s', $codigo);
        $chkCod->execute();
        $row = $chkCod->get_result()->fetch_assoc();
        if ($row) {
            $updCod->bind_param('sii', $descripcion, $catId, $row['ID_ENFE_DIAG_COD']);
            if ($updCod->execute()) {
                $totOk++;
                $msgs[] = ['OK', "{$codigo} — ya existía (id {$row['ID_ENFE_DIAG_COD']}) — descripción / categoría actualizadas"];
            } else {
                $totErr++;
                $msgs[] = ['ERR', "{$codigo} — fallo UPDATE: ".$updCod->error];
            }
        } else {
            $insCod->bind_param('iss', $catId, $codigo, $descripcion);
            if ($insCod->execute()) {
                $totNew++;
                $msgs[] = ['NEW', "{$codigo} — CREADO (id {$conexion->insert_id}) — {$descripcion}"];
            } else {
                $totErr++;
                $msgs[] = ['ERR', "{$codigo} — fallo INSERT: ".$insCod->error];
            }
        }
    }
}
$chkCod->close(); $updCod->close(); $insCod->close();

$cls = ['NEW' => 'success', 'OK' => 'secondary', 'ERR' => 'danger'];
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resultado ICD-10</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4"><div class="container" style="max-width:900px;">
<h4>Resultado — Ingreso de ICD-10 Nutrición</h4>
<div class="mb-3">
    <span class="badge bg-success">Nuevos: <?= (int)$totNew ?></span>
    <span class="badge bg-secondary">Ya existían: <?= (int)$totOk ?></span>
    <span class="badge bg-danger">Errores: <?= (int)$totErr ?></span>
</div>
<ul class="list-group" style="font-size:12px;">
<?php foreach ($msgs as $m): [$tag, $txt] = $m; ?>
    <li class="list-group-item py-1">
        <span class="badge bg-<?= $cls[$tag] ?? 'secondary' ?> me-2"><?= htmlspecialchars($tag) ?></span>
        <?= htmlspecialchars($txt) ?>
    </li>
<?php endforeach; ?>
</ul>
<a class="btn btn-primary mt-3" href="PNC_CIE-10Crear.php">Ir a ICD-10 Code</a>
</div></body></html>
