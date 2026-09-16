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

 // ── Lote 2 (PDFs NCP/PES ampliados) ──────────────────────────────────
 ['Hipermetabolismo (Quemaduras)','Hypermetabolism (Burns)','NI-1.1',
  'Hipermetabolismo','Hypermetabolism',
  'Relacionado con respuesta inflamatoria sistémica severa secundaria a quemaduras térmicas del 35% de superficie corporal','Related to severe systemic inflammatory response secondary to 35% TBSA thermal burns',
  'Evidenciado por gasto energético en reposo (GER) medido al 160% del predicho, FC 125 lpm y excreción elevada de nitrógeno','As evidenced by measured resting energy expenditure (REE) 160% of predicted, heart rate 125 bpm, and rapid nitrogen wasting',
  'Plan hipercalórico e hiperproteico guiado por calorimetría indirecta; asegurar micronutrientes para la cicatrización.','High-calorie, high-protein plan guided by indirect calorimetry; ensure micronutrients for wound healing.',
  'Repetir GER/calorimetría semanal, balance nitrogenado y peso; ajustar según fase de recuperación.','Repeat REE/calorimetry weekly, nitrogen balance and weight; adjust to recovery phase.'],
 ['Ingesta Excesiva de Energía','Excessive Energy Intake','NI-1.3',
  'Ingesta excesiva de energía','Excessive energy intake',
  'Relacionada con alto consumo de alimentos hipercalóricos y bebidas azucaradas','Related to high intake of energy-dense foods and sugar-sweetened beverages',
  'Evidenciado por ingesta que supera los requerimientos en 800 kcal/día y ganancia de 7 kg en 4 meses','As evidenced by caloric intake exceeding needs by 800 kcal/day and 7 kg weight gain in 4 months',
  'Déficit calórico moderado (300-500 kcal); sustituir bebidas azucaradas y alimentos ultraprocesados.','Moderate caloric deficit (300-500 kcal); replace sugar-sweetened beverages and ultra-processed foods.',
  'Peso y circunferencia de cintura mensual; diario de alimentos en cada control.','Monthly weight and waist circumference; food log at each visit.'],
 ['Infusión Insuficiente de Nutrición Enteral','Inadequate Enteral Nutrition Infusion','NI-2.3',
  'Infusión insuficiente de nutrición enteral','Inadequate enteral nutrition infusion',
  'Relacionada con interrupciones frecuentes de la alimentación por sonda por estudios diagnósticos e interpretación errónea del residuo gástrico','Related to frequent tube-feeding holds for diagnostic procedures and misinterpreted gastric residual volumes',
  'Evidenciado por registros que muestran administración de solo el 55% del volumen prescrito en 7 días','As evidenced by delivery logs showing only 55% of prescribed volume over 7 days',
  'Proteger el horario de infusión; recuperar volumen con protocolo de compensación; revisar el protocolo de residuo gástrico.','Protect the infusion schedule; use catch-up/volume-based feeding; revise the gastric residual protocol.',
  'Auditar el volumen administrado vs. prescrito diariamente; meta ≥ 80% del objetivo.','Audit delivered vs. prescribed volume daily; target ≥ 80% of goal.'],
 ['Ingesta Insuficiente de Proteína','Inadequate Protein Intake','NI-5.2',
  'Ingesta insuficiente de proteína','Inadequate protein intake',
  'Relacionada con disgeusia severa y saciedad precoz con evitación de carnes y lácteos','Related to severe dysgeusia and early satiety with avoidance of meat and dairy',
  'Evidenciado por recordatorio de 24 h con ingesta proteica de 0.4 g/kg/día (35% del requerimiento de 1.2 g/kg/día)','As evidenced by 24-hour recall showing protein intake of 0.4 g/kg/day (35% of the 1.2 g/kg/day requirement)',
  'Enriquecer con módulos proteicos y SNO hiperproteicos; estrategias para la disgeusia (sabores fríos, condimentos).','Fortify with protein modules and high-protein ONS; dysgeusia strategies (cold flavors, seasoning).',
  'Registro proteico en cada comida; meta 1.2 g/kg/día; reevaluar en 1-2 semanas.','Track protein per meal; target 1.2 g/kg/day; reassess in 1-2 weeks.'],
 ['Ingesta Insuficiente de Hierro','Inadequate Micronutrient Intake (Iron)','NI-5.4',
  'Ingesta insuficiente de micronutrientes (hierro)','Inadequate micronutrient intake (iron)',
  'Relacionada con dieta vegana restrictiva sin fuentes de hierro y ausencia de suplementación','Related to restrictive vegan diet lacking iron sources and absent supplementation',
  'Evidenciado por ferritina sérica de 8 ng/mL, hemoglobina de 9.2 g/dL y fatiga crónica','As evidenced by serum ferritin 8 ng/mL, hemoglobin 9.2 g/dL, and chronic fatigue',
  'Aumentar hierro dietético (legumbres, vegetales de hoja) con vitamina C; iniciar suplemento de hierro según indicación médica.','Increase dietary iron (legumes, leafy greens) with vitamin C; start iron supplement per medical order.',
  'Reevaluar ferritina y hemoglobina en 8-12 semanas; vigilar síntomas.','Recheck ferritin and hemoglobin in 8-12 weeks; monitor symptoms.'],
 ['Alteración de la Función GI (Intestino Corto)','Altered Gastrointestinal Function (Short Bowel)','NC-1.4',
  'Alteración de la función gastrointestinal','Altered gastrointestinal function',
  'Relacionada con síndrome de intestino corto secundario a resección ileal extensa','Related to short bowel syndrome secondary to extensive ileal resection',
  'Evidenciado por diarrea > 2.5 L/día, esteatorrea y pérdida electrolítica','As evidenced by high-output diarrhea (> 2.5 L/day), steatorrhea, and electrolyte wasting',
  'Dieta fraccionada baja en grasa/oxalato; hidratación con soluciones de rehidratación oral; considerar vitaminas liposolubles y B12.','Small frequent low-fat/low-oxalate meals; oral rehydration solutions; consider fat-soluble vitamin and B12 supplementation.',
  'Balance hídrico y electrólitos; peso y volumen de deposiciones; vitaminas liposolubles periódicas.','Fluid balance and electrolytes; weight and stool output; periodic fat-soluble vitamin levels.'],
 ['Utilización Alterada de Nutrientes (Cirrosis)','Impaired Nutrient Utilization (Cirrhosis)','NC-2.1',
  'Utilización alterada de nutrientes','Impaired nutrient utilization',
  'Relacionada con disfunción hepática secundaria a cirrosis descompensada','Related to hepatic dysfunction secondary to decompensated cirrhosis',
  'Evidenciado por amonio sérico de 110 umol/L, ictericia y desgaste muscular','As evidenced by elevated blood ammonia (110 umol/L), jaundice, and muscle wasting',
  'Energía 30-35 kcal/kg y proteína 1.2-1.5 g/kg de peso seco; comidas frecuentes con colación nocturna; considerar BCAA.','Energy 30-35 kcal/kg and protein 1.2-1.5 g/kg dry weight; frequent meals with a late-evening snack; consider BCAA.',
  'Vigilar encefalopatía, amonio, peso seco y estado muscular.','Monitor encephalopathy, ammonia, dry weight, and muscle status.'],
 ['Obesidad Grado II','Obesity Class II','NC-3.3',
  'Obesidad Grado II','Obesity Class II',
  'Relacionada con balance energético positivo crónico e inactividad física','Related to chronic positive energy balance and physical inactivity',
  'Evidenciado por IMC de 37.4 kg/m² y circunferencia de cintura de 108 cm','As evidenced by BMI 37.4 kg/m² and waist circumference 108 cm',
  'Déficit de 500-750 kcal/día con patrón alto en fibra; plan de actividad física progresivo; metas SMART.','Caloric deficit of 500-750 kcal/day with high-fiber pattern; progressive physical activity plan; SMART goals.',
  'Peso y cintura mensual; meta de 5-10% de pérdida en 6 meses.','Monthly weight and waist; target 5-10% weight loss over 6 months.'],
 ['Desnutrición Grave del Adulto (Enfermedad Aguda)','Severe Acute Adult Malnutrition (Acute Illness)','NC-4.1',
  'Desnutrición grave del adulto asociada a enfermedad aguda','Severe acute adult malnutrition',
  'Relacionada con ingesta insuficiente en el contexto de sepsis severa','Related to inadequate intake secondary to severe sepsis',
  'Evidenciado por ingesta energética < 50% del requerimiento durante > 10 días, pérdida muscular orbital/temporal moderada y edema severo','As evidenced by energy intake < 50% of requirement for > 10 days, moderate orbital/temporal muscle wasting, and severe edema',
  'Aumento gradual de energía/proteína (meta 25-30 kcal/kg, 1.2-2.0 g/kg); SNO o soporte enteral si la vía oral no cubre.','Gradually advance energy/protein (target 25-30 kcal/kg, 1.2-2.0 g/kg); ONS or enteral support if oral intake insufficient.',
  'Vigilar realimentación (P, K, Mg) los primeros días; ingesta diaria y peso; NFPE semanal.','Monitor refeeding (P, K, Mg) early days; daily intake and weight; weekly NFPE.'],
 ['Falta de Preparación para el Cambio','Not Ready for Diet/Lifestyle Change','NB-1.3',
  'Falta de preparación para cambios en la dieta/estilo de vida','Not ready for diet/lifestyle change',
  'Relacionada con baja autoeficacia percibida y temor a la restricción social','Related to low perceived self-efficacy and fear of social restriction',
  'Evidenciado por la negativa explícita a modificar el consumo de sodio pese a hipertensión','As evidenced by explicit unwillingness to modify sodium intake despite hypertension',
  'Entrevista motivacional; metas pequeñas y alcanzables; reforzar autoeficacia; educación breve.','Motivational interviewing; small achievable goals; build self-efficacy; brief education.',
  'Evaluar disposición al cambio y adherencia en cada visita.','Assess readiness to change and adherence at each visit.'],
 ['Dificultad para Preparar Alimentos','Impaired Ability to Prepare Foods','NB-2.4',
  'Dificultad para preparar alimentos','Impaired ability to prepare foods',
  'Relacionada con pérdida de destreza manual por artritis reumatoide bilateral severa','Related to loss of hand dexterity secondary to severe bilateral rheumatoid arthritis',
  'Evidenciado por incapacidad para abrir enlatados o picar verduras y dependencia de ultraprocesados','As evidenced by inability to open cans or chop vegetables and reliance on ultra-processed convenience foods',
  'Recomendar alimentos de fácil preparación y utensilios adaptados; opciones saludables listas para consumir; apoyo/recursos comunitarios.','Recommend easy-prep foods and adaptive utensils; healthy ready-to-eat options; community support/resources.',
  'Reevaluar el acceso a comidas y la calidad de la dieta en cada control.','Reassess meal access and diet quality at each visit.'],
 ['Acceso Limitado a los Alimentos','Limited Access to Food','NB-3.1',
  'Acceso limitado a los alimentos','Limited access to food',
  'Relacionado con dificultad económica severa y barreras de transporte (desierto alimentario)','Related to severe financial hardship and transportation barriers (food desert)',
  'Evidenciado por omisión de comidas 3 días por semana por falta de alimentos en el hogar','As evidenced by skipping meals 3 days per week due to lack of food availability',
  'Referir a recursos de asistencia alimentaria (SNAP, bancos de alimentos); plan económico y denso en nutrientes.','Refer to food assistance resources (SNAP, food banks); low-cost nutrient-dense meal plan.',
  'Verificar seguridad alimentaria y uso de recursos en cada visita.','Check food security and resource use at each visit.'],
 ['Diabetes Tipo 2 y Síndrome Metabólico','Type 2 Diabetes & Metabolic Syndrome','NI-5.4',
  'Ingesta excesiva de carbohidratos','Excessive carbohydrate intake',
  'Relacionada con el consumo frecuente de carbohidratos refinados y bebidas azucaradas','Related to frequent consumption of refined carbohydrates and sugar-sweetened beverages',
  'Evidenciado por HbA1c 9.2%, glucosa en ayunas 188 mg/dL y recordatorio de 24 h','As evidenced by HbA1c 9.2%, fasting glucose 188 mg/dL, and 24-hr dietary recall',
  'Prescribir 1800 kcal/día con carbohidrato constante (45-50 g por comida, 15-20 g por merienda), bajo índice glucémico; enseñar conteo de carbohidratos y lectura de etiquetas; meta SMART para eliminar bebidas azucaradas.','Prescribe 1800 kcal/day with consistent carbohydrate (45-50 g/meal, 15-20 g/snack), low glycemic index; teach carbohydrate counting and label reading; SMART goal to eliminate sugar-sweetened drinks.',
  'Meta HbA1c < 7.0% en 3 meses; glucosa en ayunas automonitoreada < 130 mg/dL; pérdida de 2-3 kg a las 6 semanas.','Target HbA1c < 7.0% in 3 months; self-monitored fasting glucose < 130 mg/dL; 2-3 kg weight loss at 6-week follow-up.'],
 ['ERC Estadio 4 — Valores de Laboratorio Alterados','CKD Stage 4 — Altered Lab Values (K/Phos)','NC-2.2',
  'Alteración de valores de laboratorio relacionados con la nutrición (potasio y fósforo)','Altered nutrition-related laboratory values (potassium & phosphorus)',
  'Relacionada con alteración de la excreción renal secundaria a ERC Estadio 4','Related to impaired renal excretion secondary to Stage 4 CKD',
  'Evidenciado por potasio sérico de 5.6 mEq/L y fósforo sérico de 5.8 mg/dL','As evidenced by serum potassium 5.6 mEq/L and serum phosphorus 5.8 mg/dL',
  'Proteína controlada 0.6-0.8 g/kg/día (35-45 g/día); sodio < 2000 mg/día; potasio < 2000 mg/día; fósforo 800-1000 mg/día; coordinar quelantes con las comidas.','Controlled protein 0.6-0.8 g/kg/day (35-45 g/day); sodium < 2000 mg/day; potassium < 2000 mg/day; phosphorus 800-1000 mg/day; coordinate phosphate binders with meals.',
  'Potasio < 5.0 mEq/L; fósforo < 4.5 mg/dL; albúmina ≥ 3.5 g/dL; resolución del edema.','Potassium < 5.0 mEq/L; phosphorus < 4.5 mg/dL; albumin ≥ 3.5 g/dL; resolution of edema.'],
 ['Desnutrición Grave Crónica con Soporte Enteral','Severe Chronic Malnutrition (Enteral Support)','NC-4.1',
  'Desnutrición grave crónica del adulto','Severe chronic adult malnutrition',
  'Relacionada con ingesta oral insuficiente persistente secundaria a disfagia post-ACV','Related to persistent inadequate oral intake secondary to post-stroke dysphagia',
  'Evidenciado por pérdida de peso del 12% en 3 meses, IMC 16.4 kg/m² y desgaste temporal severo','As evidenced by 12% weight loss in 3 months, BMI 16.4 kg/m², and severe temporal muscle wasting',
  'Nutrición enteral por SNG: meta 30 kcal/kg (~1560 kcal/día) y 1.3 g/kg proteína (~68 g/día); iniciar a 10 mL/h con fórmula polimérica 1.2 kcal/mL y avanzar en 4 días corrigiendo electrólitos.','Enteral nutrition via NGT: goal 30 kcal/kg (~1560 kcal/day) and 1.3 g/kg protein (~68 g/day); start at 10 mL/hr with 1.2 kcal/mL polymeric formula, advance over 4 days while correcting electrolytes.',
  'Monitoreo diario de fósforo, potasio y magnesio los primeros 7 días (síndrome de realimentación); meta de ganancia de 0.5 kg/semana.','Daily phosphorus, potassium, and magnesium monitoring first 7 days (refeeding syndrome); weight-gain goal 0.5 kg/week.'],
 ['Desnutrición Grave (Enf. Crónica – Crohn)','Severe Malnutrition (Chronic Illness – Crohn\'s)','NC-4.1',
  'Desnutrición grave (enfermedad crónica)','Severe malnutrition (chronic illness)',
  'Relacionada con inflamación crónica e hipermetabolismo secundarios a enfermedad de Crohn activa','Related to chronic inflammation and hypermetabolism secondary to active Crohn\'s disease',
  'Evidenciado por pérdida involuntaria > 12% en 3 meses, desgaste muscular temporal/clavicular (NFPE grado 3) e ingesta oral < 50% por > 3 semanas','As evidenced by unintentional weight loss > 12% over 3 months, temporal/clavicular muscle wasting (NFPE Stage 3), and oral intake < 50% for > 3 weeks',
  'Aumentar energía/proteína (30-35 kcal/kg, 1.2-1.5 g/kg); SNO hiperproteicos; considerar soporte enteral; tratar deficiencias (hierro, B12, D).','Increase energy/protein (30-35 kcal/kg, 1.2-1.5 g/kg); high-protein ONS; consider enteral support; treat deficiencies (iron, B12, D).',
  'Peso semanal, NFPE, ingesta y marcadores inflamatorios; meta de recuperación ponderal gradual.','Weekly weight, NFPE, intake, and inflammatory markers; goal of gradual weight repletion.'],
 ['Ingesta Energética Inadecuada (Post-Quimioterapia)','Inadequate Energy Intake (Post-Chemotherapy)','NI-1.2',
  'Ingesta energética inadecuada','Inadequate energy intake',
  'Relacionada con saciedad precoz severa, náuseas y anorexia post-quimioterapia','Related to severe early satiety, nausea, and poor appetite post-chemotherapy',
  'Evidenciado por conteo calórico con promedio de 800 kcal/día (45% de las necesidades) y pérdida del 6% en 30 días','As evidenced by calorie count averaging 800 kcal/day (45% of needs) and 6% weight loss in 30 days',
  'Comidas pequeñas y frecuentes densas en energía; SNO hiperproteicos; manejo de náuseas y saciedad; enriquecimiento calórico.','Small, frequent energy-dense meals; high-protein ONS; nausea/satiety management; calorie fortification.',
  'Registro de ingesta y peso 2 veces por semana; meta de estabilización del peso.','Intake log and weight twice weekly; target weight stabilization.'],
 ['Patrón de Alimentación Desordenado (Restrictivo/Anorexia)','Disordered Eating Pattern (Restrictive/Anorexia)','NB-1.5',
  'Patrón de alimentación desordenado','Disordered eating pattern',
  'Relacionado con temor severo a ganar peso, reglas alimentarias rígidas y distorsión de la imagen corporal','Related to severe fear of weight gain, rigid food rules, and body image distortion',
  'Evidenciado por restricción a < 500 kcal/día, eliminación de grupos de macronutrientes, amenorrea secundaria y bradicardia','As evidenced by restriction to < 500 kcal/day, elimination of macronutrient groups, secondary amenorrhea, and bradycardia',
  'Plan estructurado de comidas (sistema de intercambios/regla de 3); realimentación 10-15 kcal/kg si alto riesgo; principios CBT-E y exposición a alimentos temidos; equipo multidisciplinario.','Structured meal plan (exchange system/rule of 3s); refeeding 10-15 kcal/kg if high risk; CBT-E principles and fear-food exposure; multidisciplinary team.',
  'Laboratorios diarios (P, K, Mg, glucosa) durante la realimentación; signos vitales ortostáticos, FC y registros conductuales.','Daily labs (P, K, Mg, glucose) during refeeding; orthostatic vitals, heart rate, and behavioral logs.'],
 ['Creencias/Actitudes Dañinas (Bulimia)','Harmful Beliefs/Attitudes About Food (Bulimia)','NB-1.2',
  'Creencias/actitudes dañinas sobre los alimentos o la nutrición','Harmful beliefs/attitudes about food or nutrition',
  'Relacionadas con conductas compensatorias de purga tras percepción de sobreingesta','Related to compensatory purging behaviors following perceived overeating',
  'Evidenciado por vómito autoinducido 4-5 veces/semana, hipokalemia (K+ 3.1 mEq/L) y erosión del esmalte dental','As evidenced by self-induced vomiting 4-5x/week, hypokalemia (serum potassium 3.1 mEq/L), and dental enamel erosion',
  'Entrevista motivacional y CBT-E para reestructurar creencias; normalizar el patrón de comidas; equipo multidisciplinario (psiquiatría/psicología).','Motivational interviewing and CBT-E to restructure beliefs; normalize meal pattern; multidisciplinary team (psychiatry/psychology).',
  'Frecuencia de episodios de atracón/purga; electrólitos (K, Mg); signos vitales.','Frequency of binge/purge episodes; electrolytes (K, Mg); vital signs.'],
 ['Valores de Laboratorio Alterados (Riesgo de Realimentación)','Altered Lab Values (Refeeding Risk)','NC-2.2',
  'Alteración de valores de laboratorio relacionados con la nutrición','Altered nutrition-related laboratory values',
  'Relacionada con desplazamientos intracelulares de electrólitos por realimentación rápida tras inanición prolongada','Related to intracellular electrolyte shifts secondary to rapid refeeding following prolonged starvation',
  'Evidenciado por descenso brusco del fósforo sérico (de 3.8 a 1.9 mg/dL) e hipomagnesemia en 48 h','As evidenced by sudden drop in serum phosphorus (3.8 to 1.9 mg/dL) and hypomagnesemia within 48 hours',
  'Iniciar realimentación a 10-15 kcal/kg y avanzar lentamente; reponer y monitorear electrólitos; tiamina antes de alimentar.','Start refeeding at 10-15 kcal/kg and advance slowly; replete and monitor electrolytes; thiamine before feeding.',
  'Fósforo, potasio y magnesio diarios los primeros días; ECG/vitales según riesgo.','Daily phosphorus, potassium, and magnesium first days; ECG/vitals per risk.'],
 ['Dificultad para Deglutir (Post-ACV)','Swallowing Difficulty (Post-Stroke)','NC-1.1',
  'Dificultad para deglutir','Swallowing difficulty',
  'Relacionada con daño neurológico secundario a accidente cerebrovascular','Related to neurological damage secondary to stroke',
  'Evidenciado por estudio videofluoroscópico que muestra aspiración','As evidenced by videofluoroscopic swallow study showing aspiration',
  'Modificar textura (dieta suave/puré) y líquidos espesados según fonoaudiología; posición segura; vigilar hidratación.','Modify texture (soft/pureed) and thickened liquids per SLP; safe positioning; monitor hydration.',
  'Reevaluación de la deglución por fonoaudiología; signos de aspiración; ingesta y peso.','SLP swallow re-evaluation; signs of aspiration; intake and weight.'],
 ['Ingesta Oral Inadecuada (Disfagia)','Inadequate Oral Intake (Dysphagia)','NI-2.1',
  'Ingesta oral inadecuada','Inadequate oral intake',
  'Relacionada con deterioro de la deglución/masticación','Related to impaired swallowing/chewing',
  'Evidenciado por recordatorio de 24 h con < 30% de las necesidades cubiertas y diagnóstico de disfagia','As evidenced by 24-hr recall showing < 30% of needs met and dysphagia diagnosis',
  'Dieta de textura modificada densa en energía/proteína; SNO adaptados; considerar soporte enteral si persiste el déficit.','Energy/protein-dense texture-modified diet; adapted ONS; consider enteral support if deficit persists.',
  'Ingesta diaria vs. necesidades; peso; tolerancia a texturas.','Daily intake vs. needs; weight; texture tolerance.'],
 ['Déficit de Conocimientos en Nutrición','Food and Nutrition-Related Knowledge Deficit','NB-1.1',
  'Déficit de conocimientos relacionados con alimentos y nutrición','Food and nutrition-related knowledge deficit',
  'Relacionado con falta de educación nutricional previa','Related to lack of prior nutrition education',
  'Evidenciado por incapacidad para identificar fuentes de carbohidratos o leer etiquetas nutricionales','As evidenced by inability to identify carbohydrate sources or read food nutrition labels',
  'Educación estructurada sobre grupos de alimentos, etiquetas y planificación de comidas; materiales adaptados al nivel de alfabetización.','Structured education on food groups, labels, and meal planning; literacy-appropriate materials.',
  'Evaluar comprensión (enseñanza-devolución) y aplicación en cada visita.','Assess understanding (teach-back) and application at each visit.'],

 // ── Lote 3 — PEDIÁTRICO (separado) ───────────────────────────────────
 ['PEDIÁTRICO — Sobrepeso','PEDIATRIC — Overweight','NC-3.3.5',
  'Sobrepeso pediátrico','Pediatric overweight',
  'Relacionado con ingesta excesiva de bebidas azucaradas y tiempo de pantalla elevado','Related to high intake of sugar-sweetened drinks and excessive screen time',
  'Evidenciado por IMC para la edad entre el percentil 85 y 94','As evidenced by BMI-for-age between the 85th and 94th percentile',
  'Educación familiar sobre porciones y bebidas; reducir azúcar y pantallas; promover actividad física (sin dietas restrictivas durante el crecimiento).','Family education on portions and beverages; reduce sugar and screen time; promote physical activity (no restrictive dieting during growth).',
  'IMC para la edad/percentil y hábitos en cada control; meta de mantener el peso mientras crece en talla.','BMI-for-age/percentile and habits at each visit; goal to maintain weight while growing in height.'],
 ['PEDIÁTRICO — Obesidad','PEDIATRIC — Obesity','NC-3.3.5',
  'Obesidad pediátrica','Pediatric obesity',
  'Relacionada con consumo elevado de bebidas azucaradas y sedentarismo','Related to high intake of sugar-sweetened drinks and sedentary lifestyle',
  'Evidenciado por IMC para la edad en el percentil 97 (≥ percentil 95)','As evidenced by BMI-for-age at the 97th percentile (≥ 95th percentile)',
  'Plan familiar estructurado; reducir bebidas azucaradas y ultraprocesados; actividad física ≥ 60 min/día; metas conductuales; involucrar a los cuidadores.','Structured family plan; reduce sugary drinks and ultra-processed foods; physical activity ≥ 60 min/day; behavioral goals; involve caregivers.',
  'IMC para la edad/percentil, hábitos y comorbilidades cada 4-8 semanas.','BMI-for-age/percentile, habits, and comorbidities every 4-8 weeks.'],
 ['PEDIÁTRICO — Malnutrición Relacionada con Enfermedad','PEDIATRIC — Illness-Related Malnutrition',null,
  'Malnutrición pediátrica relacionada con enfermedad','Illness-related pediatric malnutrition',
  'Relacionada con necesidades aumentadas y/o ingesta insuficiente secundaria a enfermedad o lesión','Related to increased needs and/or inadequate intake secondary to disease or injury',
  'Evidenciado por deterioro del peso/talla para la edad, pérdida de reservas musculares/grasas e ingesta insuficiente','As evidenced by declining weight/length-for-age, loss of muscle/fat stores, and inadequate intake',
  'Aumentar energía/proteína según necesidades pediátricas; SNO/enriquecimiento; tratar la causa; soporte enteral si es necesario.','Increase energy/protein per pediatric needs; ONS/fortification; treat underlying cause; enteral support if needed.',
  'Peso, talla y perímetros; percentiles/velocidad de crecimiento; ingesta; laboratorios según caso.','Weight, length/height, and circumferences; growth percentiles/velocity; intake; labs as indicated.'],
 ['PEDIÁTRICO — Malnutrición No Relacionada con Enfermedad','PEDIATRIC — Non-Illness-Related Malnutrition',null,
  'Malnutrición pediátrica no relacionada con enfermedad','Non-illness-related pediatric malnutrition',
  'Relacionada con factores ambientales/conductuales (inseguridad alimentaria, prácticas de alimentación)','Related to environmental/behavioral factors (food insecurity, feeding practices)',
  'Evidenciado por peso/talla para la edad por debajo del estándar y desaceleración del crecimiento','As evidenced by weight/length-for-age below standard and growth deceleration',
  'Educación a cuidadores sobre alimentación adecuada para la edad; referir a recursos alimentarios; plan denso en nutrientes; seguimiento del crecimiento.','Caregiver education on age-appropriate feeding; refer to food resources; nutrient-dense plan; growth follow-up.',
  'Curvas de crecimiento y hábitos alimentarios en cada visita.','Growth curves and feeding habits at each visit.'],
 ['PEDIÁTRICO — Ingesta Energética Inadecuada (Falla de Crecimiento)','PEDIATRIC — Inadequate Energy Intake (Faltering Growth)','NI-1.2',
  'Ingesta energética inadecuada (pediátrica)','Inadequate energy intake (pediatric)',
  'Relacionada con apetito reducido/selectividad alimentaria y necesidades aumentadas','Related to poor appetite/food selectivity and increased needs',
  'Evidenciado por ingesta < necesidades estimadas y cruce descendente de percentiles de peso','As evidenced by intake below estimated needs and downward crossing of weight percentiles',
  'Enriquecimiento calórico apropiado para la edad; comidas/meriendas estructuradas; consejería a cuidadores; SNO pediátricos si procede.','Age-appropriate calorie fortification; structured meals/snacks; caregiver counseling; pediatric ONS if appropriate.',
  'Velocidad de crecimiento y registro de ingesta; ajuste según percentiles.','Growth velocity and intake log; adjust per percentiles.'],

 // ── Lote 3 — Adulto (códigos NC adicionales) ─────────────────────────
 ['Dificultad para Masticar','Chewing (Masticatory) Difficulty','NC-1.2',
  'Dificultad para masticar','Chewing difficulty',
  'Relacionada con prótesis dental mal ajustada','Related to poorly fitting dentures',
  'Evidenciado por incapacidad para consumir carnes sólidas y verduras crudas','As evidenced by inability to consume solid meats/raw vegetables',
  'Dieta de textura modificada (blanda/picada); referir a odontología; asegurar densidad de nutrientes.','Texture-modified diet (soft/minced); refer to dentistry; ensure nutrient density.',
  'Tolerancia a texturas, ingesta y peso.','Texture tolerance, intake, and weight.'],
 ['Interacción Fármaco-Nutrimento (Warfarina)','Food-Medication Interaction (Warfarin)','NC-2.3',
  'Interacción fármaco-nutrimento','Food-medication interaction',
  'Relacionada con uso de warfarina e ingesta irregular de verduras de hoja verde (vitamina K)','Related to warfarin therapy with irregular leafy green (vitamin K) intake',
  'Evidenciado por valores de INR fluctuantes entre 1.2 y 4.5','As evidenced by erratic INR values ranging from 1.2 to 4.5',
  'Educar sobre consistencia diaria de vitamina K; evitar cambios bruscos; coordinar con el médico/clínica de anticoagulación.','Educate on consistent daily vitamin K intake; avoid abrupt changes; coordinate with physician/anticoagulation clinic.',
  'Estabilidad del INR y regularidad de la ingesta de vitamina K.','INR stability and regularity of vitamin K intake.'],
 ['Bajo Peso','Underweight','NC-3.1',
  'Bajo peso','Underweight',
  'Relacionado con ingesta energética inadecuada crónica secundaria a anorexia','Related to chronic inadequate energy intake secondary to anorexia',
  'Evidenciado por IMC 16.8 kg/m² y peso < 85% del peso deseable','As evidenced by BMI 16.8 kg/m² and body weight < 85% of desirable',
  'Plan hipercalórico e hiperproteico progresivo; comidas frecuentes; SNO; abordar causas subyacentes.','Progressive high-calorie, high-protein plan; frequent meals; ONS; address underlying causes.',
  'Peso e IMC; ingesta; meta de recuperación ponderal gradual.','Weight and BMI; intake; goal of gradual weight repletion.'],
 ['Aumento Involuntario de Peso','Unintended Weight Gain','NC-3.4',
  'Aumento involuntario de peso','Unintended weight gain',
  'Relacionado con retención de líquidos secundaria a insuficiencia cardíaca','Related to fluid retention secondary to heart failure',
  'Evidenciado por ganancia de 6 kg en 7 días y edema bilateral +3','As evidenced by 6 kg weight gain in 7 days and +3 bilateral pitting edema',
  'Restricción de sodio (< 2000 mg) y líquidos según indicación; educación; coordinar con cardiología.','Sodium (< 2000 mg) and fluid restriction as indicated; education; coordinate with cardiology.',
  'Peso diario en ayunas, edema y balance hídrico.','Daily morning weight, edema, and fluid balance.'],
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
