<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Affiliates\Models\Eps;

class EpsSeeder extends Seeder
{
    /**
     * Catálogo oficial PILA — 31 EPS + las presentes en Data Central.
     * Ref: Tabla PILA (Código, Administradora, Subsistema=EPS).
     */
    public function run(): void
    {
        foreach ($this->getEpsList() as $eps) {
            Eps::updateOrCreate(
                ['code' => $eps['code']],
                ['name' => $eps['name'], 'is_active' => $eps['is_active'] ?? true]
            );
        }
    }

    private function getEpsList(): array
    {
        return [
            ['code' => 'EPSIC3', 'name' => 'AIC - Asociación Indígena del Cauca'],
            ['code' => 'EPS001', 'name' => 'ALIANSALUD'],
            ['code' => 'ESSC62', 'name' => 'ASMETSALUD'],
            ['code' => 'EPSC34', 'name' => 'CAPITAL SALUD'],
            ['code' => 'EPS015', 'name' => 'COLPATRIA'],
            ['code' => 'EPS008', 'name' => 'COMPENSAR'],
            ['code' => 'CCFC24', 'name' => 'COMFAMILIAR HUILA'],
            ['code' => 'EPS009', 'name' => 'COMFENALCO ANTIOQUIA'],
            ['code' => 'EPS016', 'name' => 'COOMEVA'],
            ['code' => 'ESSC24', 'name' => 'COOSALUD'],
            ['code' => 'EPSS33', 'name' => 'DUSAKAWI'],
            ['code' => 'EPS039', 'name' => 'EMSSANAR'],
            ['code' => 'EPS017', 'name' => 'FAMISANAR'],
            ['code' => 'EPSI05', 'name' => 'MALLAMAS'],
            ['code' => 'EPS041', 'name' => 'MEDIMÁS'],
            ['code' => 'EPS042', 'name' => 'MUTUAL SER'],
            ['code' => 'EPS037', 'name' => 'NUEVA EPS'],
            ['code' => 'EPSI01', 'name' => 'PIJAOS SALUD'],
            ['code' => 'EPS002', 'name' => 'SALUD TOTAL'],
            ['code' => 'EPS005', 'name' => 'SANITAS'],
            ['code' => 'EPS040', 'name' => 'SAVIA SALUD'],
            ['code' => 'EPS018', 'name' => 'SOS - SERVICIO OCCIDENTAL DE SALUD'],
            ['code' => 'EPS010', 'name' => 'SURA'],
            ['code' => 'EPSS41', 'name' => 'LA NUEVA EPS (subsidiado)'],
            ['code' => 'EPSS34', 'name' => 'CAJACOPI'],
            ['code' => 'EPSS37', 'name' => 'COMFABOY'],
            ['code' => 'EPSS40', 'name' => 'COMFAMILIAR NARIÑO'],
            ['code' => 'EPSS45', 'name' => 'COMFAORIENTE'],
            ['code' => 'EPSS46', 'name' => 'COMFASUCRE'],
            ['code' => 'EPSS23', 'name' => 'CRUZ BLANCA'],
            ['code' => 'EPSS47', 'name' => 'ECOOPSOS'],
        ];
    }
}
