/**
 * nutri_calc_ui.js — UI para la calculadora nutricional.
 * Depende de: js/nutri_calc.js
 *
 *   NutriCalcUI.mount(rootSelector, { lang })
 *   NutriCalcUI.prefill({ sex, age, weightKg, heightCm })
 *   NutriCalcUI.getReportHtml() → HTML de la tabla lista para pegar en un informe
 *   NutriCalcUI.insertInReport() → llama a window._nutriInsertHandler(html) si existe
 */
(function(global){

    var CFG = { lang: 'en' };
    var STATE = { mode: 'adult', sex: 'M', age: 0, weightKg: 0, heightCm: 0, activityKey: 'sedentary', conditionId: 'baseline', subgroupId: null };

    function L(en, es){ return (CFG.lang === 'es') ? es : en; }

    function _fmt(x, d){ if (typeof x !== 'number' || !isFinite(x)) return '—'; return d != null ? x.toFixed(d) : Math.round(x); }
    function _range(min, max, d){
        if (min == null && max == null) return '—';
        if (min === max || max == null) return _fmt(min, d);
        return _fmt(min, d) + '–' + _fmt(max, d);
    }

    function _basisLabel(basis){
        switch (basis) {
            case 'ideal':    return L('ideal wt', 'peso ideal');
            case 'adjusted': return L('adjusted wt', 'peso ajustado');
            case 'dry':      return L('dry wt', 'peso seco');
            case 'fixed':    return L('fixed', 'fijo');
            case 'actual':
            default:         return L('actual wt', 'peso real');
        }
    }

    function _pickWeight(basis){
        var ideal = NutriCalc.idealWeightKg(STATE.sex, STATE.heightCm);
        var adj   = NutriCalc.adjustedBodyWeight(STATE.weightKg, ideal);
        return NutriCalc.weightForBasis(basis, STATE.weightKg, ideal, adj);
    }

    function _bmi(){ if (!STATE.weightKg || !STATE.heightCm) return 0; var m = STATE.heightCm / 100; return STATE.weightKg / (m * m); }

    function _renderBase(){
        var bmrV = NutriCalc.bmr(STATE.sex, STATE.weightKg, STATE.heightCm, STATE.age);
        var factor = (NutriCalc.ACTIVITY[STATE.activityKey] || NutriCalc.ACTIVITY.sedentary).factor;
        var teeV = NutriCalc.tee(bmrV, factor);
        var ideal = NutriCalc.idealWeightKg(STATE.sex, STATE.heightCm);
        var adj   = NutriCalc.adjustedBodyWeight(STATE.weightKg, ideal);
        var hs    = NutriCalc.hollidaySegar(STATE.weightKg);
        $('#ncBmr').html(_fmt(bmrV) + '<small>kcal</small>');
        $('#ncTee').html(_fmt(teeV) + '<small>kcal</small>');
        $('#ncIdeal').html(_fmt(ideal, 1) + '<small>kg</small>');
        $('#ncAbw').html((STATE.weightKg > 0 && _bmi() >= 30) ? (_fmt(adj, 1) + '<small>kg</small>') : ('—<small>kg</small>'));
        $('#ncBmi').html(_fmt(_bmi(), 1) + '<small>kg/m²</small>');
        $('#ncHs').html(_fmt(hs) + '<small>mL/día</small>');
    }

    function _kcalLine(kcal, kcalOptions, subgroup){
        var lines = [];
        if (kcal) {
            var w = _pickWeight(kcal.basis || 'actual');
            var min = kcal.min ? kcal.min * w : null;
            var max = kcal.max ? kcal.max * w : null;
            lines.push('<div><b>' + _range(kcal.min, kcal.max) + ' kcal/kg</b>'
                + ' <span class="badge bg-light text-dark badge-basis">' + _basisLabel(kcal.basis) + '</span>'
                + (w > 0 ? ' → <b>' + _range(min, max) + ' kcal/día</b>' : '')
                + (kcal.note_en ? ' <span class="text-muted small">(' + L(kcal.note_en, kcal.note_es || kcal.note_en) + ')</span>' : '')
                + '</div>');
        }
        if (kcalOptions) {
            kcalOptions.forEach(function(k){
                var w = _pickWeight(k.basis || 'actual');
                var min = k.min * w, max = k.max * w;
                lines.push('<div><b>' + _range(k.min, k.max) + ' kcal/kg</b>'
                    + ' <span class="badge bg-light text-dark badge-basis">' + L(k.label_en || _basisLabel(k.basis), k.label_es || _basisLabel(k.basis)) + '</span>'
                    + (w > 0 ? ' → <b>' + _range(min, max) + ' kcal/día</b>' : '')
                    + '</div>');
            });
        }
        var noteEn = subgroup.kcal_note_en, noteEs = subgroup.kcal_note_es;
        if (noteEn) lines.push('<div class="nc-cond-note">' + L(noteEn, noteEs || noteEn) + '</div>');
        return lines.join('');
    }

    function _proteinLine(protein){
        if (!protein || !protein.length) return '';
        return protein.map(function(p){
            var w = _pickWeight(p.basis || 'actual');
            var min, max, unit = p.unit || 'g/kg';
            if (unit === 'g/day') {
                min = p.min; max = p.max;
                return '<div><b>' + _range(min, max) + ' g/día</b>'
                    + (p.label_en ? ' <span class="text-muted small">(' + L(p.label_en, p.label_es || p.label_en) + ')</span>' : '')
                    + '</div>';
            }
            min = p.min * w; max = p.max * w;
            return '<div><b>' + _range(p.min, p.max, 2) + ' g/kg</b>'
                + ' <span class="badge bg-light text-dark badge-basis">' + _basisLabel(p.basis) + '</span>'
                + (w > 0 ? ' → <b>' + _range(min, max, 0) + ' g/día</b>' : '')
                + (p.label_en ? ' <span class="text-muted small">(' + L(p.label_en, p.label_es || p.label_en) + ')</span>' : '')
                + '</div>';
        }).join('');
    }

    function _fluidLine(fluid){
        if (!fluid) return '<div class="text-muted small">' + L('Individualized', 'Individualizado') + '</div>';
        if (Array.isArray(fluid)) return fluid.map(function(f){ return _fluidLine(f); }).join('');
        var unit = fluid.unit || '', w = _pickWeight(fluid.basis || 'actual');
        var out = '';
        if (fluid.min != null || fluid.max != null) {
            if (unit === 'mL/kg') {
                var minL = fluid.min * w, maxL = fluid.max * w;
                out = '<div><b>' + _range(fluid.min, fluid.max) + ' mL/kg</b>'
                    + ' <span class="badge bg-light text-dark badge-basis">' + _basisLabel(fluid.basis) + '</span>'
                    + (w > 0 ? ' → <b>' + _range(minL, maxL) + ' mL/día</b>' : '') + '</div>';
            } else if (unit === 'L/day') {
                out = '<div><b>' + _range(fluid.min, fluid.max, 1) + ' L/día</b></div>';
            } else if (unit === 'mL/day') {
                out = '<div><b>' + _range(fluid.min, fluid.max) + ' mL/día</b></div>';
            }
        }
        if (fluid.note_en) out += '<div class="nc-cond-note">' + L(fluid.note_en, fluid.note_es || fluid.note_en) + '</div>';
        return out;
    }

    function _findCondition(id){ for (var i = 0; i < NutriCalc.CONDITIONS.length; i++) if (NutriCalc.CONDITIONS[i].id === id) return NutriCalc.CONDITIONS[i]; return null; }
    function _findSubgroup(cond, id){ if (!cond || !cond.subgroups) return null; for (var i = 0; i < cond.subgroups.length; i++) if (cond.subgroups[i].id === id) return cond.subgroups[i]; return cond.subgroups[0]; }

    function _renderCondition(){
        var cond = _findCondition(STATE.conditionId);
        if (!cond) { $('#ncCondResult').html('—'); return; }
        var subgroup = _findSubgroup(cond, STATE.subgroupId);
        if (!subgroup) { $('#ncCondResult').html('—'); return; }
        $('#ncCondTitle').text(L(cond.en, cond.es) + (cond.subgroups.length > 1 ? ' — ' + L(subgroup.en, subgroup.es) : ''));

        var html = '<div class="row g-3">'
            + '<div class="col-md-4"><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Calories', 'Calorías') + '</div>'
            + _kcalLine(subgroup.kcal, subgroup.kcal_options, subgroup) + '</div>'
            + '<div class="col-md-4"><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Protein', 'Proteínas') + '</div>'
            + _proteinLine(subgroup.protein) + '</div>'
            + '<div class="col-md-4"><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Fluid', 'Líquidos') + '</div>'
            + _fluidLine(subgroup.fluid) + '</div>'
            + '</div>';
        $('#ncCondResult').html(html);
    }

    // ── Pediatría ────────────────────────────────────────────────────────
    function _renderPed(){
        var eer   = NutriCalc.eerPediatric(STATE.sex, STATE.weightKg, STATE.heightCm, STATE.age, STATE.activityKey);
        var pKg   = NutriCalc.proteinPerKgPediatric(STATE.age);
        var protG = (pKg != null && STATE.weightKg > 0) ? pKg * STATE.weightKg : null;
        var hs    = NutriCalc.hollidaySegar(STATE.weightKg);
        var fiber = NutriCalc.fiberPediatric(STATE.age);
        var quick = NutriCalc.kcalPerKgQuickPediatric(STATE.age);
        var bmi   = _bmi();

        $('#ncPedEer').html((eer.kcal > 0 ? _fmt(eer.kcal) : '—') + '<small>kcal/día</small>');
        $('#ncPedProt').html((protG != null ? _fmt(protG) : '—') + '<small>g/día</small>');
        $('#ncPedFluid').html((hs > 0 ? _fmt(hs) : '—') + '<small>mL/día</small>');
        $('#ncPedFiber').html((fiber != null ? _fmt(fiber) : '—') + '<small>g/día</small>');
        $('#ncPedKcalKg').html((quick ? _range(quick.min, quick.max) : '—') + '<small>kcal/kg</small>');
        $('#ncPedBmi').html(_fmt(bmi, 1) + '<small>kg/m²</small>');

        // Detalle
        var rows = [];
        // Energía
        var eLbl = L('Energy (EER)', 'Energía (EER)');
        var eForm = _pedFormulaLabel(eer.formula);
        var eLine = (eer.kcal > 0) ? '<b>' + _fmt(eer.kcal) + ' kcal/día</b>' : '—';
        if (quick && STATE.weightKg > 0) {
            eLine += ' · <span class="text-muted">' + L('quick', 'rápido') + ': ' + _range(quick.min, quick.max) + ' kcal/kg → <b>'
                   + _range(quick.min * STATE.weightKg, quick.max * STATE.weightKg) + ' kcal/día</b></span>';
        }
        rows.push('<div class="mb-2"><div class="small fw-semibold text-uppercase text-muted mb-1">' + eLbl + '</div>'
                + '<div>' + eLine + (eForm ? ' <span class="badge bg-light text-dark badge-basis">' + eForm + '</span>' : '') + '</div></div>');
        // Proteínas
        rows.push('<div class="mb-2"><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Protein', 'Proteínas') + '</div>'
                + '<div>' + (pKg != null ? '<b>' + pKg.toFixed(2) + ' g/kg</b>' + (protG != null ? ' → <b>' + _fmt(protG) + ' g/día</b>' : '') : '—') + '</div></div>');
        // Líquidos
        rows.push('<div class="mb-2"><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Fluid (Holliday-Segar)', 'Líquidos (Holliday-Segar)') + '</div>'
                + '<div>' + (hs > 0 ? '<b>' + _fmt(hs) + ' mL/día</b>' : '—') + '</div></div>');
        // Fibra
        rows.push('<div><div class="small fw-semibold text-uppercase text-muted mb-1">' + L('Fiber', 'Fibra') + '</div>'
                + '<div>' + (fiber != null ? '<b>' + fiber + ' g/día</b> <span class="text-muted small">(' + L('Age + 5 rule', 'regla Edad + 5') + ')</span>' : '—') + '</div></div>');

        $('#ncPedDetail').html(rows.join(''));
    }

    function _pedFormulaLabel(formula){
        switch (formula) {
            case 'infant_0_3':    return L('0–3 mo (IOM)', '0–3 meses (IOM)');
            case 'infant_4_6':    return L('4–6 mo (IOM)', '4–6 meses (IOM)');
            case 'infant_7_12':   return L('7–12 mo (IOM)', '7–12 meses (IOM)');
            case 'toddler_13_35': return L('13–35 mo (IOM)', '13–35 meses (IOM)');
            case 'child_3_8':     return L('3–8 yr (IOM)', '3–8 años (IOM)');
            case 'child_9_18':    return L('9–18 yr (IOM)', '9–18 años (IOM)');
            default:              return '';
        }
    }

    function _applyMode(){
        var isPed = (STATE.mode === 'ped');
        $('#ncAdultResults').toggle(!isPed);
        $('#ncPedResults').toggle(isPed);
        $('#ncAdultInputs').toggle(!isPed);
        if (isPed) { $('#ncSubgroupWrap').hide(); }
        $('#ncAgeHint').toggleClass('d-none', !isPed);
        _buildActivity();
    }

    function _buildActivity(){
        var $act = $('#ncActivity').empty();
        if (STATE.mode === 'ped') {
            Object.keys(NutriCalc.PED_ACTIVITY).forEach(function(k){
                var a = NutriCalc.PED_ACTIVITY[k];
                var f = (STATE.sex === 'F' || STATE.sex === 'f') ? a.F : a.M;
                $act.append('<option value="' + k + '">' + L(a.en, a.es) + ' (PA ×' + f.toFixed(2) + ')</option>');
            });
            if (!NutriCalc.PED_ACTIVITY[STATE.activityKey]) STATE.activityKey = 'sedentary';
        } else {
            Object.keys(NutriCalc.ACTIVITY).forEach(function(k){
                var a = NutriCalc.ACTIVITY[k];
                $act.append('<option value="' + k + '">' + L(a.en, a.es) + ' (×' + a.factor + ')</option>');
            });
            if (!NutriCalc.ACTIVITY[STATE.activityKey]) STATE.activityKey = 'sedentary';
        }
        $act.val(STATE.activityKey);
    }

    function _render(){
        if (STATE.mode === 'ped') { _renderPed(); }
        else { _renderBase(); _renderCondition(); }
    }

    function _buildDropdowns(){
        // Actividad (según modo)
        _buildActivity();
        // Condición (adulto)
        var $c = $('#ncCondition').empty();
        NutriCalc.CONDITIONS.forEach(function(c){ $c.append('<option value="' + c.id + '">' + L(c.en, c.es) + '</option>'); });
        $c.val(STATE.conditionId);
        _buildSubgroups();
    }

    function _buildSubgroups(){
        var cond = _findCondition(STATE.conditionId);
        var $s = $('#ncSubgroup').empty();
        if (!cond || !cond.subgroups || cond.subgroups.length < 2) {
            $('#ncSubgroupWrap').hide();
            STATE.subgroupId = cond && cond.subgroups && cond.subgroups[0] ? cond.subgroups[0].id : null;
            return;
        }
        cond.subgroups.forEach(function(sg){ $s.append('<option value="' + sg.id + '">' + L(sg.en, sg.es) + '</option>'); });
        if (!STATE.subgroupId || !_findSubgroup(cond, STATE.subgroupId)) STATE.subgroupId = cond.subgroups[0].id;
        $s.val(STATE.subgroupId);
        $('#ncSubgroupWrap').show();
    }

    function _readInputs(){
        STATE.mode       = ($('input[name="ncMode"]:checked').val() === 'ped') ? 'ped' : 'adult';
        STATE.sex        = $('#ncSex').val();
        STATE.age        = parseFloat($('#ncAge').val()) || 0;
        STATE.weightKg   = parseFloat($('#ncWeight').val()) || 0;
        STATE.heightCm   = parseFloat($('#ncHeight').val()) || 0;
        STATE.activityKey= $('#ncActivity').val() || 'sedentary';
        STATE.conditionId= $('#ncCondition').val() || 'baseline';
        STATE.subgroupId = $('#ncSubgroup').val() || STATE.subgroupId;
    }

    function _wire(){
        $('input[name="ncMode"]').on('change', function(){
            STATE.mode = ($(this).val() === 'ped') ? 'ped' : 'adult';
            _applyMode(); _readInputs(); _render();
        });
        // Cambiar sexo re-arma los factores PA pediátricos
        $('#ncSex').on('change', function(){ STATE.sex = $(this).val(); if (STATE.mode === 'ped') _buildActivity(); _readInputs(); _render(); });
        $('#ncAge, #ncWeight, #ncHeight, #ncActivity').on('input change', function(){ _readInputs(); _render(); });
        $('#ncCondition').on('change', function(){ STATE.conditionId = $(this).val(); STATE.subgroupId = null; _buildSubgroups(); _readInputs(); _renderCondition(); });
        $('#ncSubgroup').on('change', function(){ STATE.subgroupId = $(this).val(); _renderCondition(); });
    }

    function _pedReportHtml(){
        var eer   = NutriCalc.eerPediatric(STATE.sex, STATE.weightKg, STATE.heightCm, STATE.age, STATE.activityKey);
        var pKg   = NutriCalc.proteinPerKgPediatric(STATE.age);
        var protG = (pKg != null && STATE.weightKg > 0) ? pKg * STATE.weightKg : null;
        var hs    = NutriCalc.hollidaySegar(STATE.weightKg);
        var fiber = NutriCalc.fiberPediatric(STATE.age);
        var quick = NutriCalc.kcalPerKgQuickPediatric(STATE.age);
        var bmi   = _bmi();
        var eForm = _pedFormulaLabel(eer.formula);
        var actLbl = '';
        if (NutriCalc.PED_ACTIVITY[STATE.activityKey]) {
            var a = NutriCalc.PED_ACTIVITY[STATE.activityKey];
            actLbl = L(a.en, a.es);
        }

        var html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;margin:14px 0;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">'
            + '<h5 style="color:#5a2d82;margin:0 0 8px 0;">' + L('Pediatric Nutrition Requirements', 'Requerimientos Nutricionales Pediátricos') + '</h5>'
            + '<div style="font-size:12px;color:#5a6172;margin-bottom:8px;">'
              + '<b>' + L('Age', 'Edad') + ':</b> ' + _fmt(STATE.age, 1) + ' ' + L('yr', 'años')
              + ' &nbsp;·&nbsp; <b>' + L('Weight', 'Peso') + ':</b> ' + _fmt(STATE.weightKg, 1) + ' kg'
              + (STATE.heightCm ? ' &nbsp;·&nbsp; <b>' + L('Height', 'Talla') + ':</b> ' + _fmt(STATE.heightCm, 1) + ' cm' : '')
              + (actLbl ? ' &nbsp;·&nbsp; <b>' + L('Activity', 'Actividad') + ':</b> ' + actLbl : '')
            + '</div>'
            + '<table style="width:100%;border-collapse:collapse;font-size:12px;">'
            + '<tr>'
              + '<td style="padding:4px 8px;"><b>EER:</b> ' + (eer.kcal > 0 ? _fmt(eer.kcal) + ' kcal/día' : '—') + (eForm ? ' (' + eForm + ')' : '') + '</td>'
              + '<td style="padding:4px 8px;"><b>' + L('Protein', 'Proteínas') + ':</b> ' + (pKg != null ? pKg.toFixed(2) + ' g/kg' + (protG != null ? ' → ' + _fmt(protG) + ' g/día' : '') : '—') + '</td>'
            + '</tr>'
            + '<tr>'
              + '<td style="padding:4px 8px;"><b>' + L('Fluid (Holliday-Segar)', 'Líquidos (Holliday-Segar)') + ':</b> ' + (hs > 0 ? _fmt(hs) + ' mL/día' : '—') + '</td>'
              + '<td style="padding:4px 8px;"><b>' + L('Fiber', 'Fibra') + ':</b> ' + (fiber != null ? fiber + ' g/día (' + L('Age + 5', 'Edad + 5') + ')' : '—') + '</td>'
            + '</tr>'
            + '<tr>'
              + '<td style="padding:4px 8px;"><b>' + L('Quick estimate', 'Estimación rápida') + ':</b> ' + (quick ? _range(quick.min, quick.max) + ' kcal/kg' + (STATE.weightKg > 0 ? ' → ' + _range(quick.min*STATE.weightKg, quick.max*STATE.weightKg) + ' kcal/día' : '') : '—') + '</td>'
              + '<td style="padding:4px 8px;"><b>BMI:</b> ' + _fmt(bmi, 1) + ' kg/m²</td>'
            + '</tr>'
            + '</table>'
            + '<div style="font-size:11px;color:#8a8f98;margin-top:6px;">' + L('IOM/DRI equations. Pediatric BMI interpreted with CDC/WHO percentiles.', 'Ecuaciones IOM/DRI. El IMC pediátrico se interpreta con percentiles CDC/OMS.') + '</div>'
            + '</div>';
        return html;
    }

    function getReportHtml(){
        _readInputs();
        if (STATE.mode === 'ped') return _pedReportHtml();
        var cond = _findCondition(STATE.conditionId);
        var subgroup = _findSubgroup(cond, STATE.subgroupId);
        var bmrV = NutriCalc.bmr(STATE.sex, STATE.weightKg, STATE.heightCm, STATE.age);
        var factor = (NutriCalc.ACTIVITY[STATE.activityKey] || NutriCalc.ACTIVITY.sedentary).factor;
        var teeV = NutriCalc.tee(bmrV, factor);
        var ideal = NutriCalc.idealWeightKg(STATE.sex, STATE.heightCm);
        var adj   = NutriCalc.adjustedBodyWeight(STATE.weightKg, ideal);
        var hs    = NutriCalc.hollidaySegar(STATE.weightKg);
        var bmi   = _bmi();

        var kcalHtml    = subgroup ? _kcalLine(subgroup.kcal, subgroup.kcal_options, subgroup) : '—';
        var proteinHtml = subgroup ? _proteinLine(subgroup.protein) : '—';
        var fluidHtml   = subgroup ? _fluidLine(subgroup.fluid) : '—';

        var html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#2b2b2b;margin:14px 0;padding:12px;border:1px solid #e5e7eb;border-radius:6px;">'
            + '<h5 style="color:#5a2d82;margin:0 0 8px 0;">' + L('Nutrition Requirements', 'Requerimientos Nutricionales') + '</h5>'
            + '<div style="font-size:12px;color:#5a6172;margin-bottom:8px;">'
              + '<b>' + L('Condition', 'Condición') + ':</b> ' + L(cond.en, cond.es)
              + (cond.subgroups.length > 1 ? ' — ' + L(subgroup.en, subgroup.es) : '')
            + '</div>'
            + '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;font-size:12px;">'
            + '<tr>'
              + '<td style="padding:4px 8px;"><b>BMR (Mifflin):</b> ' + _fmt(bmrV) + ' kcal/día</td>'
              + '<td style="padding:4px 8px;"><b>TEE:</b> ' + _fmt(teeV) + ' kcal/día (×' + factor + ')</td>'
              + '<td style="padding:4px 8px;"><b>BMI:</b> ' + _fmt(bmi, 1) + ' kg/m²</td>'
            + '</tr>'
            + '<tr>'
              + '<td style="padding:4px 8px;"><b>' + L('Ideal weight', 'Peso ideal') + ':</b> ' + _fmt(ideal, 1) + ' kg</td>'
              + '<td style="padding:4px 8px;"><b>' + L('Adjusted weight', 'Peso ajustado') + ':</b> ' + (bmi >= 30 ? _fmt(adj, 1) + ' kg' : '—') + '</td>'
              + '<td style="padding:4px 8px;"><b>' + L('Holliday-Segar', 'Holliday-Segar') + ':</b> ' + _fmt(hs) + ' mL/día</td>'
            + '</tr>'
            + '</table>'
            + '<div style="border-top:1px solid #eee;padding-top:6px;">'
              + '<div style="margin-bottom:4px;"><b>' + L('Calories', 'Calorías') + ':</b><br>' + kcalHtml + '</div>'
              + '<div style="margin-bottom:4px;"><b>' + L('Protein', 'Proteínas') + ':</b><br>' + proteinHtml + '</div>'
              + '<div><b>' + L('Fluid', 'Líquidos') + ':</b><br>' + fluidHtml + '</div>'
            + '</div>'
            + '</div>';
        return html;
    }

    function insertInReport(){
        var html = getReportHtml();
        if (typeof window._nutriInsertHandler === 'function') {
            window._nutriInsertHandler(html);
        } else {
            // Fallback: mostrar en una nueva pestaña
            var w = window.open('', '_blank');
            if (w) { w.document.write(html); w.document.close(); }
        }
    }

    function mount(sel, opts){
        CFG.lang = (opts && opts.lang === 'es') ? 'es' : 'en';
        _buildDropdowns();
        _wire();
        _applyMode();
        _readInputs();
        _render();
    }

    function prefill(data){
        if (!data) return;
        if (data.sex)      $('#ncSex').val(data.sex === 'F' || data.sex === 'f' ? 'F' : 'M');
        if (data.age != null && data.age !== '')      $('#ncAge').val(data.age);
        if (data.weightKg) $('#ncWeight').val(data.weightKg);
        if (data.heightCm) $('#ncHeight').val(data.heightCm);
        // Selección de modo automática por edad (si no la fija el llamador)
        if (data.mode === 'ped' || data.mode === 'adult') {
            $('#ncMode' + (data.mode === 'ped' ? 'Ped' : 'Adult')).prop('checked', true);
        } else if (data.age != null && data.age !== '' && parseFloat(data.age) > 0 && parseFloat(data.age) < 18) {
            $('#ncModePed').prop('checked', true);
        }
        STATE.sex = $('#ncSex').val();
        _applyMode();
        _readInputs();
        _render();
    }

    global.NutriCalcUI = { mount: mount, prefill: prefill, getReportHtml: getReportHtml, insertInReport: insertInReport };
})(typeof window !== 'undefined' ? window : this);
