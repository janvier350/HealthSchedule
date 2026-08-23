/**
 * bwplanner.js — Port fiel del modelo dinámico de peso corporal de Hall (NIH Body Weight Planner)
 *
 * Basado en el código abierto del simulador oficial (ccc1685/BWSimulatorOS, Objective-C),
 * que implementa el modelo publicado en:
 *   Hall KD, et al. "Quantification of the effect of energy imbalance on bodyweight."
 *   The Lancet 2011;378:826-37.
 *
 * Unidades internas: kg, metros, kcal, días.  sex: 1 = mujer, 0 = hombre.
 */
(function (global) {
  'use strict';

  // ── Constantes del modelo (idénticas al simulador del NIH) ──────────
  var beta       = 0.24;
  var beta_tef   = 0.1;
  var beta_therm = beta - beta_tef;   // 0.14

  var eta_L = 230, eta_F = 18;
  var gamma_L = 22, gamma_F = 3.2;

  var rho_F = 9440, rho_L = 1807, rho_c = 4180;
  var hg = 2.7;

  var Cf = 10.4;
  var C  = Cf * rho_L / rho_F;
  var deltaEInitial = 0;

  var carb_power = 2;
  var tau_therm  = 14;

  var Na_conc     = 3220;
  var Na_zero_CIn = 4000;
  var Na_ecw      = 3000;

  var dt = 0.5;

  function BWPerson(opts) {
    opts = opts || {};
    // Datos iniciales del sujeto
    this.sex           = (opts.sex === 1 || opts.sex === '1') ? 1 : 0; // 1=mujer, 0=hombre
    this.age           = +opts.age;
    this.height        = +opts.height;         // metros
    this.weightInitial = +opts.weightInitial;  // kg
    this.palInitial    = +opts.palInitial;

    // Parámetros con valor por defecto del NIH
    this.glycogenInitial = (opts.glycogenInitial != null) ? +opts.glycogenInitial : 0.5;
    this.NaBaseline      = (opts.NaBaseline      != null) ? +opts.NaBaseline      : 4000;
    this.carbFracBaseline= (opts.carbFracBaseline!= null) ? +opts.carbFracBaseline: 0.5;

    // Ingesta basal = PAL inicial × RMR inicial
    this.intakeInitial = this.teeInitial();

    // Estado (se fija en stepper)
    this.pal    = this.palInitial;   // PAL actual (puede cambiar como meta de actividad)
    this.intake = this.intakeInitial;
    this.fat = 0; this.lean = 0; this.glycogen = 0;
    this.therm = 0; this.deltaExtraCellularWater = 0; this.weight = 0;
  }

  // ── Cantidades iniciales (constantes durante la simulación) ─────────
  BWPerson.prototype.rmrInitial = function () {
    if (this.sex === 1) { // mujer
      return 9.99 * this.weightInitial + 625 * this.height - 4.92 * this.age - 161;
    }
    return 9.99 * this.weightInitial + 625 * this.height - 4.92 * this.age + 5; // hombre
  };
  BWPerson.prototype.teeInitial  = function () { return this.palInitial * this.rmrInitial(); };
  BWPerson.prototype.bmiInitial  = function () { return this.weightInitial / (this.height * this.height); };

  BWPerson.prototype.fatInitial = function () {
    var bmi = this.bmiInitial();
    var f;
    if (this.sex === 1) { // mujer
      f = (-102.01 + 39.96 * Math.log(bmi) + 0.14 * this.age) / 100 * this.weightInitial;
    } else {              // hombre
      f = (-103.94 + 37.31 * Math.log(bmi) + 0.14 * this.age) / 100 * this.weightInitial;
    }
    if (f > 0.6 * this.weightInitial) f = 0.6 * this.weightInitial;
    if (f < 0) f = 0;
    return f;
  };

  BWPerson.prototype.deltaInit = function () {
    return ((1 - beta_tef) * this.teeInitial() - this.rmrInitial()) / this.weightInitial;
  };
  BWPerson.prototype.delta = function (paldelta, weightdelta) {
    return ((1 - beta_tef) * paldelta - 1) * this.rmrInitial() / weightdelta;
  };

  // ── Flujos de carbohidratos / glucógeno ─────────────────────────────
  BWPerson.prototype.carbIntakeBaseline = function () { return this.carbFracBaseline * this.intakeInitial; };
  BWPerson.prototype.kCarb              = function () { return this.carbIntakeBaseline() / Math.pow(this.glycogenInitial, carb_power); };
  BWPerson.prototype.carbIntake         = function () { return this.carbFracBaseline * this.intake; };
  BWPerson.prototype.carbFlux           = function () { return this.carbIntake() - this.kCarb() * Math.pow(this.glycogen, carb_power); };
  BWPerson.prototype.pRatio             = function () { return C / (C + this.fat); };
  BWPerson.prototype.Na                 = function () { return this.NaBaseline * this.intake / this.intakeInitial; };

  // ── Gasto energético total ──────────────────────────────────────────
  BWPerson.prototype.energyExpenditure = function () {
    var K = (1 - beta) * this.intakeInitial - deltaEInitial
          - gamma_L * (this.weightInitial - this.fatInitial())
          - gamma_F * this.fatInitial()
          - this.deltaInit() * this.weightInitial;

    var pr = this.pRatio();
    var expend = K + gamma_L * this.lean + gamma_F * this.fat
               + this.delta(this.pal, this.weightInitial) * this.weight
               + this.therm + beta_tef * this.intake;

    return (expend + (this.intake - this.carbFlux()) * ((1 - pr) * eta_F / rho_F + pr * eta_L / rho_L))
         / (1 + pr * eta_L / rho_L + (1 - pr) * eta_F / rho_F);
  };

  // ── Derivadas ───────────────────────────────────────────────────────
  BWPerson.prototype.dFat      = function () { return (1 - this.pRatio()) * (this.intake - this.energyExpenditure() - this.carbFlux()) / rho_F; };
  BWPerson.prototype.dLean     = function () { return this.pRatio() * (this.intake - this.energyExpenditure() - this.carbFlux()) / rho_L; };
  BWPerson.prototype.dGlycogen = function () { return (this.carbIntake() - this.kCarb() * Math.pow(this.glycogen, carb_power)) / rho_c; };
  BWPerson.prototype.dTherm    = function () { return (beta_therm * this.intake - this.therm) / tau_therm; };
  BWPerson.prototype.dECW      = function () {
    return (this.Na() - this.NaBaseline - Na_ecw * this.deltaExtraCellularWater
            - Na_zero_CIn * (1 - this.carbIntake() / this.carbIntakeBaseline())) / Na_conc;
  };

  // Paso de Euler — se respeta el mismo orden secuencial del simulador original
  BWPerson.prototype.euler = function () {
    this.fat      += dt * this.dFat();
    this.lean     += dt * this.dLean();
    this.glycogen += dt * this.dGlycogen();
    this.therm    += dt * this.dTherm();
    this.deltaExtraCellularWater += dt * this.dECW();
    this.weight = this.fat + this.lean + this.deltaExtraCellularWater
                + (1 + hg) * (this.glycogen - this.glycogenInitial);
  };

  BWPerson.prototype._reset = function () {
    this.therm = beta_therm * this.intakeInitial;
    this.fat   = this.fatInitial();
    this.lean  = this.weightInitial - this.fatInitial();
    this.glycogen = this.glycogenInitial;
    this.deltaExtraCellularWater = 0;
    this.weight = this.fat + this.lean + this.deltaExtraCellularWater
                + (1 + hg) * (this.glycogen - this.glycogenInitial);
  };

  // Simula t días con la ingesta y PAL actuales
  BWPerson.prototype.stepper = function (t) {
    var total = Math.floor(t / dt);
    this._reset();
    for (var i = 0; i < total; i++) this.euler();
    return this.weight;
  };

  // Simula t días y devuelve la trayectoria de peso (un punto por día)
  BWPerson.prototype.trajectory = function (t) {
    var total = Math.floor(t / dt);
    var perDay = Math.round(1 / dt);
    this._reset();
    var pts = [{ day: 0, weight: this.weight }];
    for (var i = 1; i <= total; i++) {
      this.euler();
      if (i % perDay === 0) pts.push({ day: i * dt, weight: this.weight });
    }
    return pts;
  };

  // Newton-Raphson: ingesta constante para alcanzar goalWeight en t días
  BWPerson.prototype.findIntake = function (goalWeight, t) {
    var currentIntake = this.weightInitial * 22;
    this.intake = currentIntake;
    this.stepper(t);
    var currentWeight = this.weight;
    var error = currentWeight - goalWeight;
    var it = 0;
    while (Math.abs(error) > 0.01 && it < 10) {
      this.intake = currentIntake + 1;
      this.stepper(t);
      var dWeight = this.weight - currentWeight;
      currentIntake -= error / dWeight;

      this.intake = currentIntake;
      this.stepper(t);
      currentWeight = this.weight;

      error = currentWeight - goalWeight;
      it++;
    }
    this.intake = currentIntake;
    return currentIntake;
  };

  // Newton-Raphson: ingesta de mantenimiento para estabilizar en maintenanceWeight
  BWPerson.prototype.findMaintenanceIntake = function (maintenanceWeight) {
    var t = 4000;
    var currentIntake = this.weightInitial * 22;
    this.intake = currentIntake;
    this.stepper(t);
    var currentWeight = this.weight;
    var error = currentWeight - maintenanceWeight;
    var it = 0;
    while (Math.abs(error) > 0.01 && it < 10) {
      this.intake = currentIntake + 1;
      this.stepper(t);
      var dWeight = this.weight - currentWeight;
      currentIntake -= error / dWeight;

      this.intake = currentIntake;
      this.stepper(t);
      currentWeight = this.weight;

      error = currentWeight - maintenanceWeight;
      it++;
    }
    this.intake = currentIntake;
    return currentIntake;
  };

  global.BWPerson = BWPerson;
})(window);
