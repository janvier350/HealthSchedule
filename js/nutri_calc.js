/**
 * nutri_calc.js — Calculadora de necesidades nutricionales para adultos.
 *
 * Fuentes:
 *   - Clinical Nutrition Pocket Reference (dual language)
 *   - "A Dietitian's Cheat Sheet for Calculating Estimated Needs" (Dietitians On Demand, 2025)
 *
 * Fórmulas centrales
 *   BMR (Mifflin-St. Jeor):
 *      Hombres:  10·kg + 6.25·cm − 5·edad + 5
 *      Mujeres:  10·kg + 6.25·cm − 5·edad − 161
 *   TEE = BMR × factor de actividad
 *   Peso Ideal (Devine, para talla > 5 ft = 60 in):
 *      Hombres:  50   + 2.3 kg × (in − 60)
 *      Mujeres:  45.5 + 2.3 kg × (in − 60)
 *   ABW (Adjusted Body Weight) = Ideal + 0.4·(Real − Ideal)
 *   Fluidos Holliday-Segar (mL/día):
 *      ≤10 kg:  100 mL/kg
 *      ≤20 kg:  1000 + 50 mL por cada kg > 10
 *      >20 kg:  1500 + 20 mL por cada kg > 20
 *
 * API
 *   NutriCalc.bmr(sex, kg, cm, ageYrs) → number
 *   NutriCalc.tee(bmr, factor)         → number
 *   NutriCalc.idealWeightKg(sex, cm)   → number
 *   NutriCalc.adjustedBodyWeight(actualKg, idealKg) → number
 *   NutriCalc.hollidaySegar(kg)        → number  (mL/día)
 *   NutriCalc.ACTIVITY                  → { key: factor }
 *   NutriCalc.CONDITIONS                → [{id, en, es, subgroups:[...]}]
 *   NutriCalc.weightForBasis(basis, actualKg, idealKg, adjKg) → number
 */
(function(global){

    function idealWeightKg(sex, heightCm) {
        if (!heightCm || heightCm <= 0) return 0;
        var heightIn = heightCm / 2.54;
        var over5ft  = Math.max(0, heightIn - 60);
        var base     = (sex === 'F' || sex === 'f') ? 45.5 : 50;
        return base + 2.3 * over5ft;
    }

    function adjustedBodyWeight(actualKg, idealKg) {
        if (!actualKg || !idealKg) return 0;
        return idealKg + 0.4 * (actualKg - idealKg);
    }

    function bmr(sex, weightKg, heightCm, ageYrs) {
        if (!weightKg || !heightCm || !ageYrs) return 0;
        var base = 10 * weightKg + 6.25 * heightCm - 5 * ageYrs;
        return (sex === 'F' || sex === 'f') ? base - 161 : base + 5;
    }

    function tee(bmrVal, factor) {
        if (!bmrVal) return 0;
        return bmrVal * (factor || 1.2);
    }

    function hollidaySegar(weightKg) {
        if (!weightKg || weightKg <= 0) return 0;
        if (weightKg <= 10) return weightKg * 100;
        if (weightKg <= 20) return 1000 + (weightKg - 10) * 50;
        return 1500 + (weightKg - 20) * 20;
    }

    var ACTIVITY = {
        sedentary:   { factor: 1.2,   en: 'Sedentary',   es: 'Sedentario' },
        light:       { factor: 1.375, en: 'Lightly active', es: 'Poco activo' },
        moderate:    { factor: 1.55,  en: 'Moderately active', es: 'Moderadamente activo' },
        active:      { factor: 1.725, en: 'Very active', es: 'Muy activo' },
        very_active: { factor: 1.9,   en: 'Extra active', es: 'Extra activo' }
    };

    // basis: 'actual' | 'ideal' | 'adjusted'  |  'dry' (equal to actual on this app)
    function weightForBasis(basis, actualKg, idealKg, adjKg) {
        switch (basis) {
            case 'ideal':    return idealKg;
            case 'adjusted': return adjKg;
            case 'dry':      // dry weight (cirrhosis) — el usuario ajusta manualmente
            case 'actual':
            default:         return actualKg;
        }
    }

    // ── Catálogo de condiciones (Adulto) ─────────────────────────────────
    // Cada subgroup tiene:
    //   id           : identificador
    //   en / es      : etiqueta
    //   kcal         : { min, max, basis } o array (varias opciones)
    //   protein      : array de { min, max, basis, note_en?, note_es? }
    //   fluid        : { min?, max?, unit: 'mL/kg' | 'mL/day', basis?, note_en?, note_es? } o array o null
    //   note_en/es   : nota adicional
    var CONDITIONS = [
        {
            id: 'baseline',
            en: 'Adult Baseline (healthy)',
            es: 'Adulto sano',
            subgroups: [{
                id: 'baseline', en: 'Baseline', es: 'Basal',
                kcal:    { min: 25, max: 30, basis: 'actual' },
                protein: [{ min: 0.8, max: 1.0, basis: 'actual' }],
                fluid:   { min: 30, max: 35, unit: 'mL/kg', basis: 'actual' }
            }]
        },
        {
            id: 'obesity_1_2',
            en: 'Obesity Class I–II (BMI 30–39.9)',
            es: 'Obesidad Clase I–II (IMC 30–39.9)',
            subgroups: [{
                id: 'ob12', en: 'Class I–II', es: 'Clase I–II',
                kcal_options: [
                    { min: 11, max: 14, basis: 'actual', label_en: 'per actual weight', label_es: 'por peso real' },
                    { min: 22, max: 25, basis: 'ideal',  label_en: 'per ideal weight',  label_es: 'por peso ideal' }
                ],
                protein: [{ min: 1.2, max: 1.5, basis: 'ideal' }],
                fluid:   null
            }]
        },
        {
            id: 'obesity_3',
            en: 'Obesity Class III (BMI ≥ 40) / Critical care',
            es: 'Obesidad Clase III (IMC ≥ 40) / Críticos',
            subgroups: [{
                id: 'ob3', en: 'Hypocaloric, high-protein', es: 'Hipocalórica, hiperproteica',
                kcal_options: [
                    { min: 11, max: 14, basis: 'actual', label_en: 'per actual weight', label_es: 'por peso real' },
                    { min: 22, max: 25, basis: 'ideal',  label_en: 'per ideal weight',  label_es: 'por peso ideal' }
                ],
                protein: [
                    { min: 2.0, max: 2.5, basis: 'ideal',    label_en: 'per ideal weight',    label_es: 'por peso ideal' },
                    { min: 1.5, max: 2.0, basis: 'adjusted', label_en: 'per adjusted weight', label_es: 'por peso ajustado' }
                ],
                fluid:   null
            }]
        },
        {
            id: 'aki',
            en: 'Acute Kidney Injury (AKI)',
            es: 'Lesión Renal Aguda (AKI)',
            subgroups: [{
                id: 'aki', en: 'AKI', es: 'AKI',
                kcal:    { min: 20, max: 30, basis: 'actual', note_en: '100–130% REE', note_es: '100–130% REE' },
                protein: [
                    { min: 0.8, max: 1.0, basis: 'actual', label_en: 'no dialysis, non-catabolic', label_es: 'sin diálisis, no catabólico' },
                    { min: 1.0, max: 1.5, basis: 'actual', label_en: 'on dialysis and/or catabolic', label_es: 'con diálisis y/o catabólico' },
                    { min: 1.7, max: 2.5, basis: 'actual', label_en: 'critically ill on CRRT', label_es: 'críticamente enfermo con CRRT' }
                ],
                fluid:   { note_en: 'Individualized. Consider restriction with anuria/oliguria; no restriction with CRRT.', note_es: 'Individualizado. Considerar restricción con anuria/oliguria; sin restricción con CRRT.' }
            }]
        },
        {
            id: 'pancreatitis',
            en: 'Acute Pancreatitis',
            es: 'Pancreatitis Aguda',
            subgroups: [{
                id: 'panc', en: 'Acute pancreatitis', es: 'Pancreatitis aguda',
                kcal:    { min: 25, max: 35, basis: 'actual', note_en: 'or indirect calorimetry', note_es: 'o calorimetría indirecta' },
                protein: [
                    { min: 1.2, max: 1.5, basis: 'actual' },
                    { min: 2.0, max: 2.0, basis: 'actual', label_en: 'up to 2 g/kg severe (critical illness)', label_es: 'hasta 2 g/kg severo (enfermedad crítica)' }
                ],
                fluid:   null
            }]
        },
        {
            id: 'breastfeeding',
            en: 'Breastfeeding',
            es: 'Lactancia',
            subgroups: [
                {
                    id: 'bf_0_6',  en: '0–6 months (exclusive)', es: '0–6 meses (exclusiva)',
                    kcal_note_en: 'TEE + 540 (milk production) − 140 (energy mobilization)',
                    kcal_note_es: 'TEE + 540 (producción de leche) − 140 (movilización)',
                    protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day', label_en: 'RDA 71 g/day', label_es: 'RDA 71 g/día' }],
                    fluid:   { min: 3.8, max: 3.8, unit: 'L/day', basis: 'fixed', note_en: 'AI 3.8 L/day (16 cups)', note_es: 'AI 3.8 L/día (16 tazas)' }
                },
                {
                    id: 'bf_7_12', en: '7–12 months (partial)', es: '7–12 meses (parcial)',
                    kcal_note_en: 'TEE + 380 (milk production)',
                    kcal_note_es: 'TEE + 380 (producción de leche)',
                    protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day' }],
                    fluid:   { min: 3.8, max: 3.8, unit: 'L/day', basis: 'fixed' }
                }
            ]
        },
        {
            id: 'burns',
            en: 'Burns',
            es: 'Quemaduras',
            subgroups: [{
                id: 'burns', en: 'Burns', es: 'Quemaduras',
                kcal:    { note_en: 'Indirect calorimetry (repeat weekly)', note_es: 'Calorimetría indirecta (repetir semanalmente)' },
                protein: [
                    { min: 1.5, max: 2.0, basis: 'actual', label_en: '>20% TBSA burns', label_es: '>20% SCTQ' },
                    { min: 3.0, max: 4.0, basis: 'actual', label_en: 'larger TBSA burns', label_es: 'SCTQ mayor' }
                ],
                fluid:   { note_en: 'Close intake/output monitoring', note_es: 'Monitoreo estricto de ingesta/eliminación' }
            }]
        },
        {
            id: 'cancer',
            en: 'Cancer',
            es: 'Cáncer',
            subgroups: [
                { id: 'cancer_inact', en: 'Inactive / non-stressed', es: 'Inactivo / sin estrés',
                  kcal: { min: 25, max: 30, basis: 'actual' },
                  protein: [{ min: 1.0, max: 1.5, basis: 'actual' }] },
                { id: 'cancer_gain',  en: 'Weight gain / repletion', es: 'Ganancia / recuperación',
                  kcal: { min: 30, max: 35, basis: 'actual' },
                  protein: [{ min: 1.0, max: 1.5, basis: 'actual' }] },
                { id: 'cancer_hyper', en: 'Hypermetabolic / stressed', es: 'Hipermetabólico / con estrés',
                  kcal: { min: 35, max: 35, basis: 'actual' },
                  protein: [{ min: 1.0, max: 1.5, basis: 'actual' }] },
                { id: 'cancer_cach',  en: 'Cachexia', es: 'Caquexia',
                  kcal: { min: 30, max: 35, basis: 'actual' },
                  protein: [{ min: 1.5, max: 2.0, basis: 'actual' }] }
            ]
        },
        {
            id: 'ckd',
            en: 'Chronic Kidney Disease (CKD)',
            es: 'Enfermedad Renal Crónica (ERC)',
            subgroups: [
                { id: 'ckd_35', en: 'Stage 3–5 (no dialysis)', es: 'Etapa 3–5 (sin diálisis)',
                  kcal: { min: 25, max: 35, basis: 'actual' },
                  protein: [
                      { min: 0.55, max: 0.60, basis: 'actual', label_en: 'Low protein diet', label_es: 'Dieta hipoproteica' },
                      { min: 0.28, max: 0.43, basis: 'actual', label_en: 'Very low + keto acid analogs', label_es: 'Muy hipoproteica + análogos cetoácidos' },
                      { min: 0.6,  max: 0.8,  basis: 'actual', label_en: 'If diabetes comorbid', label_es: 'Si hay comorbilidad de diabetes' }
                  ],
                  fluid: { note_en: 'Individualized (comorbidities, urine output)', note_es: 'Individualizado (comorbilidades, diuresis)' } },
                { id: 'ckd_hd', en: 'Stage 5D — Hemodialysis', es: 'Etapa 5D — Hemodiálisis',
                  kcal: { min: 25, max: 35, basis: 'actual' },
                  protein: [{ min: 1.0, max: 1.2, basis: 'actual' }],
                  fluid: { note_en: '≥1 L urine: 2000 mL/day · <1 L: 1000–1500 · Oliguria: 24-h + 750 mL', note_es: '≥1 L de orina: 2000 mL/día · <1 L: 1000–1500 · Oliguria: 24-h + 750 mL' } },
                { id: 'ckd_pd', en: 'Stage 5D — Peritoneal dialysis', es: 'Etapa 5D — Diálisis peritoneal',
                  kcal: { min: 25, max: 35, basis: 'actual' },
                  protein: [{ min: 1.0, max: 1.2, basis: 'actual' }],
                  fluid: { min: 1000, max: 3000, unit: 'mL/day', basis: 'fixed', note_en: 'Individualized to maintain fluid balance', note_es: 'Individualizado para mantener balance hídrico' } },
                { id: 'ckd_kt_acute', en: 'Kidney transplant, acute', es: 'Trasplante renal, agudo',
                  kcal: { min: 30, max: 35, basis: 'actual', note_en: 'or BEE × 1.3–1.5', note_es: 'o BEE × 1.3–1.5' },
                  protein: [{ min: 1.2, max: 2.0, basis: 'actual' }],
                  fluid: { note_en: 'Unrestricted unless graft dysfunction', note_es: 'Sin restricción salvo disfunción del injerto' } },
                { id: 'ckd_kt_chron', en: 'Kidney transplant, chronic', es: 'Trasplante renal, crónico',
                  kcal: { min: 25, max: 30, basis: 'actual' },
                  protein: [
                      { min: 0.6, max: 0.8, basis: 'actual', label_en: 'Without diabetes', label_es: 'Sin diabetes' },
                      { min: 0.8, max: 0.9, basis: 'actual', label_en: 'With diabetes',    label_es: 'Con diabetes' }
                  ],
                  fluid: { note_en: 'Unrestricted unless graft dysfunction', note_es: 'Sin restricción salvo disfunción del injerto' } }
            ]
        },
        {
            id: 'copd',
            en: 'COPD',
            es: 'EPOC',
            subgroups: [{
                id: 'copd', en: 'COPD', es: 'EPOC',
                kcal:    { min: 30, max: 30, basis: 'actual', note_en: 'consider 30 kcal/kg without obesity', note_es: 'considerar 30 kcal/kg sin obesidad' },
                protein: [{ min: 1.0, max: 1.5, basis: 'actual' }],
                fluid:   { min: 1.5, max: 2.0, unit: 'L/day', basis: 'fixed', note_en: 'Restrict to 1.5–2.0 L/day for fluid overload', note_es: 'Restringir a 1.5–2.0 L/día si sobrecarga hídrica' }
            }]
        },
        {
            id: 'cirrhosis',
            en: 'Cirrhosis / Liver Disease',
            es: 'Cirrosis / Enfermedad Hepática',
            subgroups: [
                { id: 'cirr', en: 'Cirrhosis', es: 'Cirrosis',
                  kcal: { min: 30, max: 35, basis: 'dry', note_en: 'per estimated dry weight', note_es: 'por peso seco estimado' },
                  protein: [
                      { min: 1.2, max: 1.5, basis: 'dry' },
                      { min: 2.0, max: 2.0, basis: 'dry', label_en: 'up to 2 g/kg with critical illness', label_es: 'hasta 2 g/kg con enfermedad crítica' }
                  ],
                  fluid: { min: 1.0, max: 1.5, unit: 'L/day', basis: 'fixed', note_en: 'Restrict if hyponatremia (<125 mEq/L) or severe ascites', note_es: 'Restringir si hiponatremia (<125 mEq/L) o ascitis grave' } },
                { id: 'liver_acute', en: 'Liver transplant, acute', es: 'Trasplante hepático, agudo',
                  kcal: { min: 30, max: 35, basis: 'actual' },
                  protein: [{ min: 1.5, max: 2.0, basis: 'actual' }],
                  fluid: { min: 30, max: 35, unit: 'mL/kg', basis: 'actual' } },
                { id: 'liver_chron', en: 'Liver transplant, chronic', es: 'Trasplante hepático, crónico',
                  kcal_note_en: 'BEE × 1.0–1.3 depending on weight maintenance/loss',
                  kcal_note_es: 'BEE × 1.0–1.3 según mantención/pérdida de peso',
                  protein: [{ min: 0.8, max: 1.0, basis: 'actual' }],
                  fluid: { min: 30, max: 35, unit: 'mL/kg', basis: 'actual' } }
            ]
        },
        {
            id: 'critical',
            en: 'Critical Illness / Sepsis / Trauma',
            es: 'Enfermedad Crítica / Sepsis / Trauma',
            subgroups: [
                { id: 'ci_norm', en: 'BMI < 30', es: 'IMC < 30',
                  kcal: { min: 12, max: 25, basis: 'actual', note_en: '12–25 kcal/kg first 7–10 days in ICU, or Penn State equation', note_es: '12–25 kcal/kg primeros 7–10 días en UCI, o ecuación Penn State' },
                  protein: [{ min: 1.2, max: 2.0, basis: 'actual' }] },
                { id: 'ci_ob12', en: 'BMI 30–39.9', es: 'IMC 30–39.9',
                  kcal: { min: 11, max: 14, basis: 'actual' },
                  protein: [{ min: 2.0, max: 2.0, basis: 'ideal' }] },
                { id: 'ci_ob3',  en: 'BMI ≥ 40', es: 'IMC ≥ 40',
                  kcal: { min: 22, max: 25, basis: 'ideal' },
                  protein: [{ min: 2.5, max: 2.5, basis: 'ideal' }] }
            ]
        },
        {
            id: 'chf',
            en: 'Heart Failure (CHF)',
            es: 'Insuficiencia Cardíaca (ICC)',
            subgroups: [{
                id: 'chf', en: 'CHF / cardiac cachexia', es: 'ICC / caquexia cardíaca',
                kcal:    { min: 30, max: 30, basis: 'actual', note_en: 'elevated WOB / cardiac cachexia', note_es: 'aumento del trabajo respiratorio' },
                protein: [{ min: 1.1, max: 1.5, basis: 'actual' }],
                fluid:   { min: 1.5, max: 2.0, unit: 'L/day', basis: 'fixed', note_en: 'Restrict to 1.5–2.0 L/day for fluid overload', note_es: 'Restringir a 1.5–2.0 L/día si sobrecarga hídrica' }
            }]
        },
        {
            id: 'pregnancy',
            en: 'Pregnancy',
            es: 'Embarazo',
            subgroups: [
                { id: 'preg_1',    en: '1st trimester', es: '1er trimestre',
                  kcal_note_en: 'Nonpregnant EER (no additional energy)', kcal_note_es: 'EER no embarazo (sin energía adicional)',
                  protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day', label_en: 'RDA 71 g/day', label_es: 'RDA 71 g/día' }] },
                { id: 'preg_23_uw', en: '2nd/3rd trimester — Underweight', es: '2do/3er trimestre — Bajo peso',
                  kcal_note_en: 'TEE + 300 kcal/day', kcal_note_es: 'TEE + 300 kcal/día',
                  protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day' }] },
                { id: 'preg_23_nw', en: '2nd/3rd trimester — Normal weight', es: '2do/3er trimestre — Peso normal',
                  kcal_note_en: 'TEE + 200 kcal/day', kcal_note_es: 'TEE + 200 kcal/día',
                  protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day' }] },
                { id: 'preg_23_ow', en: '2nd/3rd trimester — Overweight', es: '2do/3er trimestre — Sobrepeso',
                  kcal_note_en: 'TEE + 150 kcal/day', kcal_note_es: 'TEE + 150 kcal/día',
                  protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day' }] },
                { id: 'preg_23_ob', en: '2nd/3rd trimester — Obese', es: '2do/3er trimestre — Obesidad',
                  kcal_note_en: 'TEE − 50 kcal/day', kcal_note_es: 'TEE − 50 kcal/día',
                  protein: [{ min: 71, max: 71, basis: 'fixed', unit: 'g/day' }] }
            ]
        },
        {
            id: 'pressure_wounds',
            en: 'Pressure Injuries / Wounds',
            es: 'Úlceras por Presión / Heridas',
            subgroups: [{
                id: 'pw', en: 'Pressure injuries / wounds', es: 'Úlceras / heridas',
                kcal:    { min: 30, max: 35, basis: 'actual' },
                protein: [
                    { min: 1.2, max: 1.5, basis: 'actual', label_en: 'stage 1–2',        label_es: 'etapa 1–2' },
                    { min: 1.5, max: 2.0, basis: 'actual', label_en: 'stage 3–4 or increased wound size', label_es: 'etapa 3–4 o herida amplia' }
                ],
                fluid:   { min: 30, max: 35, unit: 'mL/kg', basis: 'actual', note_en: '30 mL/kg minimum, or 1–1.5 mL/kcal for chronic wounds', note_es: '30 mL/kg mínimo, o 1–1.5 mL/kcal en heridas crónicas' }
            }]
        }
    ];

    global.NutriCalc = {
        idealWeightKg: idealWeightKg,
        adjustedBodyWeight: adjustedBodyWeight,
        bmr: bmr,
        tee: tee,
        hollidaySegar: hollidaySegar,
        ACTIVITY: ACTIVITY,
        CONDITIONS: CONDITIONS,
        weightForBasis: weightForBasis
    };
})(typeof window !== 'undefined' ? window : this);
