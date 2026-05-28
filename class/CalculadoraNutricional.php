<?php
class CalculadoraNutricional {
    
    // 1. Cálculo de IMC (BMI)
    public static function calcularIMC($peso_kg, $talla_cm) {
        if ($talla_cm <= 0) return 0;
        $talla_m = $talla_cm / 100;
        $imc = $peso_kg / ($talla_m * $talla_m);
        return round($imc, 2);
    }

    // Clasificación de la OMS para el IMC
    public static function clasificarIMC($imc) {
        if ($imc < 18.5) return "Bajo peso";
        if ($imc < 24.9) return "Peso normal";
        if ($imc < 29.9) return "Sobrepeso";
        if ($imc < 34.9) return "Obesidad Clase I";
        if ($imc < 39.9) return "Obesidad Clase II";
        return "Obesidad Clase III";
    }

    // 2. Peso Ideal (Ecuación de Hamwi) - Basado en tu captura image_128e49.jpg
    public static function pesoIdealHamwi($talla_cm, $sexo) {
        // Convertir cm a pulgadas (1 pulgada = 2.54 cm)
        $talla_pulgadas = $talla_cm / 2.54;
        $pulgadas_sobre_5ft = $talla_pulgadas - 60; // 5 pies = 60 pulgadas

        if ($sexo == 'M') { // Masculino: 106 lbs por los primeros 5ft + 6 lbs por pulgada adicional
            $peso_lbs = 106 + ($pulgadas_sobre_5ft * 6);
        } else { // Femenino: 100 lbs por los primeros 5ft + 5 lbs por pulgada adicional
            $peso_lbs = 100 + ($pulgadas_sobre_5ft * 5);
        }
        return round($peso_lbs, 1);
    }

    // 3. Requerimiento Energético (Mifflin-St Jeor) - Basado en image_128e49.jpg
    public static function mifflinStJeor($peso_kg, $talla_cm, $edad, $sexo, $factor_actividad = 1.2) {
        if ($sexo == 'M') {
            $tmb = (10 * $peso_kg) + (6.25 * $talla_cm) - (5 * $edad) + 5;
        } else {
            $tmb = (10 * $peso_kg) + (6.25 * $talla_cm) - (5 * $edad) - 161;
        }
        return round($tmb * $factor_actividad);
    }
}
?>