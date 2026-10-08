<?php
/**
 * class/geoip.php — País/ciudad aproximados a partir de la IP, con caché.
 *
 * Se usa SÓLO al mostrar la bitácora (no al registrar eventos), para no
 * afectar la operación. Cada IP se resuelve una vez y se guarda en la tabla
 * `ip_geo`; después se lee de la caché. Tolerante a fallos: si no hay salida
 * a internet o el servicio no responde, devuelve lo que haya (o vacío).
 *
 * Servicio: ip-api.com (gratuito, sin API key, endpoint batch). Sólo se envían
 * direcciones IP (de los usuarios de la app), nunca datos de pacientes.
 */

if (!function_exists('geo_ip_privada')) {
    function geo_ip_privada($ip) {
        if ($ip === '' || $ip === null) return true;
        if ($ip === '127.0.0.1' || $ip === '::1' || strtolower($ip) === 'localhost') return true;
        // Rangos privados / reservados.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
            && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return true;
        }
        return false;
    }
}

if (!function_exists('geo_ensure_tabla')) {
    function geo_ensure_tabla($conexion) {
        @$conexion->query("CREATE TABLE IF NOT EXISTS ip_geo (
            ip VARCHAR(45) NOT NULL PRIMARY KEY,
            pais VARCHAR(80) NULL,
            ciudad VARCHAR(120) NULL,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('geo_para_ips')) {
    /**
     * Devuelve un mapa ip => ['pais'=>..,'ciudad'=>..] para las IPs dadas.
     * Resuelve por caché; las que falten (públicas) se consultan en lote.
     */
    function geo_para_ips($conexion, array $ips) {
        $out = [];
        $ips = array_values(array_unique(array_filter(array_map('trim', $ips))));
        if (!$ips || !$conexion) return $out;
        geo_ensure_tabla($conexion);

        // 1) Caché existente
        $esc = array_map(function($x) use ($conexion){ return "'".$conexion->real_escape_string($x)."'"; }, $ips);
        if ($esc) {
            if ($r = $conexion->query("SELECT ip, pais, ciudad FROM ip_geo WHERE ip IN (".implode(',', $esc).")")) {
                while ($x = $r->fetch_assoc()) $out[$x['ip']] = ['pais'=>$x['pais'], 'ciudad'=>$x['ciudad']];
            }
        }

        // 2) Faltantes
        $faltan = [];
        foreach ($ips as $ip) {
            if (isset($out[$ip])) continue;
            if (geo_ip_privada($ip)) { $out[$ip] = ['pais'=>'Local', 'ciudad'=>'']; continue; }
            $faltan[] = $ip;
        }
        if (!$faltan) return $out;

        // 3) Consulta en lote a ip-api.com (máx. 100 por llamada)
        $resuelto = geo_consultar_batch($faltan);

        // 4) Guardar en caché e integrar
        if ($resuelto) {
            $ins = $conexion->prepare("INSERT INTO ip_geo (ip, pais, ciudad) VALUES (?,?,?)
                                       ON DUPLICATE KEY UPDATE pais=VALUES(pais), ciudad=VALUES(ciudad), fecha=NOW()");
            foreach ($resuelto as $ip => $g) {
                $p = $g['pais']; $c = $g['ciudad'];
                if ($ins) { $ins->bind_param('sss', $ip, $p, $c); @$ins->execute(); }
                $out[$ip] = $g;
            }
            if ($ins) $ins->close();
        }
        return $out;
    }
}

if (!function_exists('geo_consultar_batch')) {
    /** Consulta ip-api.com/batch. Devuelve ip => ['pais','ciudad']. Vacío si falla. */
    function geo_consultar_batch(array $ips) {
        $ips = array_slice($ips, 0, 100);
        $payload = json_encode(array_map(function($ip){ return ['query'=>$ip, 'fields'=>'status,country,city,query']; }, $ips));
        $url = 'http://ip-api.com/batch?fields=status,country,city,query';
        $resp = '';
        try {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 4,
                    CURLOPT_CONNECTTIMEOUT => 3,
                ]);
                $resp = curl_exec($ch);
                curl_close($ch);
            } elseif (ini_get('allow_url_fopen')) {
                $ctx = stream_context_create(['http'=>[
                    'method'=>'POST', 'header'=>"Content-Type: application/json\r\n",
                    'content'=>$payload, 'timeout'=>4,
                ]]);
                $resp = @file_get_contents($url, false, $ctx);
            }
        } catch (Exception $e) { return []; }

        if (!$resp) return [];
        $data = json_decode($resp, true);
        if (!is_array($data)) return [];
        $map = [];
        foreach ($data as $row) {
            if (!isset($row['query'])) continue;
            if (($row['status'] ?? '') === 'success') {
                $map[$row['query']] = ['pais'=>(string)($row['country'] ?? ''), 'ciudad'=>(string)($row['city'] ?? '')];
            } else {
                $map[$row['query']] = ['pais'=>'', 'ciudad'=>''];
            }
        }
        return $map;
    }
}
