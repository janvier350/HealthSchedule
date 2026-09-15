<?php // Widget HTML de la calculadora — se incluye tanto en la página independiente como en el modal de atención. ?>
<div id="ncRoot" class="row g-3">
    <!-- Datos de entrada -->
    <div class="col-lg-4">
        <div class="nc-card">
            <div class="nc-section"><?php te('nc.inputs'); ?></div>
            <div class="row g-2">
                <div class="col-12">
                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="mode">
                        <input type="radio" class="btn-check" name="ncMode" id="ncModeAdult" value="adult" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="ncModeAdult"><i class="bi bi-person me-1"></i><?php te('nc.modeAdult'); ?></label>
                        <input type="radio" class="btn-check" name="ncMode" id="ncModePed" value="ped" autocomplete="off">
                        <label class="btn btn-outline-primary" for="ncModePed"><i class="bi bi-emoji-smile me-1"></i><?php te('nc.modePed'); ?></label>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1"><?php te('nc.sex'); ?></label>
                    <select id="ncSex" class="form-select form-select-sm">
                        <option value="M"><?php te('nc.male'); ?></option>
                        <option value="F"><?php te('nc.female'); ?></option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1"><?php te('nc.age'); ?></label>
                    <input type="number" id="ncAge" class="form-control form-control-sm" min="0" max="120" step="0.1">
                    <div class="form-text small d-none" id="ncAgeHint"><?php te('nc.ageHintPed'); ?></div>
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1"><?php te('nc.weight'); ?> (kg)</label>
                    <input type="number" id="ncWeight" class="form-control form-control-sm" min="20" max="500" step="0.1">
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1"><?php te('nc.height'); ?> (cm)</label>
                    <input type="number" id="ncHeight" class="form-control form-control-sm" min="80" max="230" step="0.1">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1"><?php te('nc.activity'); ?></label>
                    <select id="ncActivity" class="form-select form-select-sm"></select>
                </div>
                <div class="col-12" id="ncAdultInputs">
                    <label class="form-label small mb-1"><?php te('nc.condition'); ?></label>
                    <select id="ncCondition" class="form-select form-select-sm"></select>
                </div>
                <div class="col-12" id="ncSubgroupWrap" style="display:none;">
                    <label class="form-label small mb-1"><?php te('nc.subgroup'); ?></label>
                    <select id="ncSubgroup" class="form-select form-select-sm"></select>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="col-lg-8">
      <!-- ── Resultados ADULTO ─────────────────────────────────────── -->
      <div id="ncAdultResults">
        <div class="nc-card mb-3">
            <div class="nc-section"><?php te('nc.baseCalc'); ?></div>
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncBmr">—<small>kcal</small></div>
                    <div class="small text-muted">BMR</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncTee">—<small>kcal</small></div>
                    <div class="small text-muted">TEE</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncIdeal">—<small>kg</small></div>
                    <div class="small text-muted"><?php te('nc.idealW'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncAbw">—<small>kg</small></div>
                    <div class="small text-muted"><?php te('nc.adjW'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncBmi">—<small>kg/m²</small></div>
                    <div class="small text-muted">BMI</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncHs">—<small>mL/día</small></div>
                    <div class="small text-muted"><?php te('nc.hsFluid'); ?></div>
                </div>
            </div>
        </div>

        <div class="nc-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="nc-section mb-0" id="ncCondTitle"><?php te('nc.byCondition'); ?></div>
                <button type="button" class="btn btn-sm btn-primary" onclick="NutriCalcUI.insertInReport()" id="ncInsertBtn">
                    <i class="bi bi-plus-square me-1"></i><?php te('nc.insertBtn'); ?>
                </button>
            </div>
            <div id="ncCondResult">
                <div class="text-muted small"><?php te('nc.pickCondition'); ?></div>
            </div>
        </div>
      </div>

      <!-- ── Resultados PEDIÁTRICO ─────────────────────────────────── -->
      <div id="ncPedResults" style="display:none;">
        <div class="nc-card mb-3">
            <div class="nc-section"><?php te('nc.pedNeeds'); ?></div>
            <div class="row g-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedEer">—<small>kcal/día</small></div>
                    <div class="small text-muted">EER</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedProt">—<small>g/día</small></div>
                    <div class="small text-muted"><?php te('nc.protein'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedFluid">—<small>mL/día</small></div>
                    <div class="small text-muted"><?php te('nc.hsFluid'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedFiber">—<small>g/día</small></div>
                    <div class="small text-muted"><?php te('nc.fiber'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedKcalKg">—<small>kcal/kg</small></div>
                    <div class="small text-muted"><?php te('nc.quickKcal'); ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="nc-metric" id="ncPedBmi">—<small>kg/m²</small></div>
                    <div class="small text-muted">BMI</div>
                </div>
            </div>
        </div>

        <div class="nc-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="nc-section mb-0"><?php te('nc.pedDetail'); ?></div>
                <button type="button" class="btn btn-sm btn-primary" onclick="NutriCalcUI.insertInReport()" id="ncPedInsertBtn">
                    <i class="bi bi-plus-square me-1"></i><?php te('nc.insertBtn'); ?>
                </button>
            </div>
            <div id="ncPedDetail"></div>
            <div class="nc-cond-note mt-2"><?php te('nc.pedBmiNote'); ?></div>
        </div>
      </div>
    </div>
</div>
