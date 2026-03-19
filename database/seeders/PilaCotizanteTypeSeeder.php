<?php

namespace Database\Seeders;

use App\Modules\PilaManagement\Models\PilaCotizanteType;
use Illuminate\Database\Seeder;

class PilaCotizanteTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $row) {
            PilaCotizanteType::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function rows(): array
    {
        return [
            ['code' => '01', 'name' => 'Dependiente'],
            ['code' => '02', 'name' => 'Servicio Doméstico'],
            ['code' => '03', 'name' => 'Independiente'],
            ['code' => '04', 'name' => 'Madre sustituta'],
            ['code' => '12', 'name' => 'Aprendices en etapa lectiva'],
            ['code' => '16', 'name' => 'Afiliación colectiva al sistema de seguridad integral'],
            ['code' => '18', 'name' => 'Funcionarios públicos sin tope máximo de IBC'],
            ['code' => '19', 'name' => 'Aprendices en etapa productiva'],
            ['code' => '20', 'name' => 'Estudiantes'],
            ['code' => '21', 'name' => 'Estudiantes de postgrado en salud'],
            ['code' => '22', 'name' => 'Profesor de establecimiento particular'],
            ['code' => '23', 'name' => 'Estudiantes aporte solo riesgos laborales'],
            ['code' => '30', 'name' => 'Dependiente entidades o universidades públicas'],
            ['code' => '31', 'name' => 'Cooperados o precooperativas de trabajo asociado'],
            ['code' => '40', 'name' => 'Beneficiario UPC adicional'],
            ['code' => '51', 'name' => 'Trabajador de tiempo parcial afiliado al régimen subsidiado'],
            ['code' => '56', 'name' => 'Prepensionado con aporte voluntario en salud'],
            ['code' => '57', 'name' => 'Independiente voluntario al Sistema de Riesgos Laborales'],
            ['code' => '59', 'name' => 'Independiente con contrato de prestación de servicios superior a 1 mes'],
        ];
    }
}
