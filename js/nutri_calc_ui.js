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
    var STATE = { sex: 'M', age: 0, weightKg: 0, heightCm: 0, activityKey: 'sedentary', conditionId: 'baseline', subgroupId: null };

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

    function _buildDropdowns(){
        // Actividad
        var $act = $('#ncActivity').empty();
        Object.keys(NutriCalc.ACTIVITY).forEach(function(k){
            var a = NutriCalc.ACTIVITY[k];
            $act.append('<option value="' + k + '">' + L(a.en, a.es) + ' (×' + a.factor + ')</option>');
        });
        $act.val(STATE.activityKey);
        // Condición
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
        STATE.sex        = $('#ncSex').val();
        STATE.age        = parseFloat($('#ncAge').val()) || 0;
        STATE.weightKg   = parseFloat($('#ncWeight').val()) || 0;
        STATE.heightCm   = parseFloat($('#ncHeight').val()) || 0;
        STATE.activityKey= $('#ncActivity').val() || 'sedentary';
        STATE.conditionId= $('#ncCondition').val() || 'baseline';
        STATE.subgroupId = $('#ncSubgroup').val() || STATE.subgroupId;
    }

    function _wire(){
        $('#ncSex, #ncAge, #ncWeight, #ncHeight, #ncActivity').on('input change', function(){ _readInputs(); _renderBase(); _renderCondition(); });
        $('#ncCondition').on('change', function(){ STATE.conditionId = $(this).val(); STATE.subgroupId = null; _buildSubgroups(); _readInputs(); _renderCondition(); });
        $('#ncSubgroup').on('change', function(){ STATE.subgroupId = $(this).val(); _renderCondition(); });
    }

    function getReportHtml(){
        _readInputs();
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
        _readInputs();
        _renderBase();
        _renderCondition();
    }

    function prefill(data){
        if (!data) return;
        if (data.sex)      $('#ncSex').val(data.sex === 'F' || data.sex === 'f' ? 'F' : 'M');
        if (data.age)      $('#ncAge').val(data.age);
        if (data.weightKg) $('#ncWeight').val(data.weightKg);
        if (data.heightCm) $('#ncHeight').val(data.heightCm);
        _readInputs();
        _renderBase();
        _renderCondition();
    }

    global.NutriCalcUI = { mount: mount, prefill: prefill, getReportHtml: getReportHtml, insertInReport: insertInReport };
})(typeof window !== 'undefined' ? window : this);
