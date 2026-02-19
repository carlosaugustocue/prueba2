<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\ContributorType;

class ContributorTypeSeeder extends Seeder
{
    /**
     * Catálogo PILA — Tipos de cotizante. Ref: mapeo_datasegura_a_base_de_datos.md
     */
    public function run(): void
    {
        $rows = [
            ['01', 'Dependiente', 'Empleados de empresa constituida como persona Natural o Jurídica.'],
            ['02', 'Servicio Doméstico', null],
            ['03', 'Independiente', null],
            ['04', 'Madre sustituta', 'Se aplica a trabajadores de un hogar.'],
            ['05', 'Independiente voluntario al Sistema de Riesgos Laborales', 'Independiente afiliado voluntariamente a Riesgos Laborales (ARL).'],
            ['12', 'Aprendices en etapa lectiva', 'Empleado que realiza sus propios aportes.'],
            ['16', 'Afiliación colectiva al sistema de seguridad integral', 'Afiliado de una Fundación bajo el registro del NIT.'],
            ['18', 'Funcionarios públicos sin tope máximo de IBC', 'Aprendiz en formación integral.'],
            ['19', 'Aprendices en etapa productiva', 'Independiente agremiado o asociado.'],
            ['20', 'Estudiantes', 'IBC puede ser superior a 25 SMLMV; deben estar asociados a distintos tipos de aportante.'],
            ['21', 'Estudiantes de postgrado en salud', 'Estudiantes del SENA en periodo de práctica.'],
            ['22', 'Profesor de establecimiento particular', 'Régimen especial Ley 789 de 2002.'],
            ['23', 'Estudiantes aporte solo riesgos laborales', null],
            ['30', 'Dependiente entidades o universidades públicas (regímenes especial y excepción)', 'Docentes en periodo de vacaciones.'],
            ['31', 'Cooperados o precooperativas de trabajo asociado', 'Asociados o Cooperados de las CTA.'],
            ['32', 'Cotizante miembro carrera diplomática o consular / organismo multilateral', null],
            ['33', 'Beneficiario del fondo de solidaridad pensional', 'Trámites con Fondo de Solidaridad Pensional (% pensión distinto al legal).'],
            ['40', 'Beneficiario UPC adicional', 'Pago UPC Adicional al sistema de salud.'],
            ['42', 'Cotizante Independiente pago solo salud', 'Cotizantes de bajos ingresos (Art. 2 Ley 1250 de 2008).'],
            ['43', 'Cotizante a pensiones con pago por tercero', 'Interviene un tercero para el pago de aportes; solo pensiones.'],
            ['51', 'Trabajador de tiempo parcial afiliado al régimen subsidiado', 'Remuneración < 1 SMLMV; periodos < 30 días en el mes.'],
            ['56', 'Prepensionado con aporte voluntario en salud', 'Prepensionados en trámite de pensión que aportan voluntariamente.'],
            ['57', 'Independiente voluntario al Sistema de Riesgos Laborales', 'En archivos PILA a veces se usa 05; mantener 57 como válido si viene en datos.'],
            ['59', 'Independiente con contrato de prestación de servicios superior a 1 mes', 'Persona natural con contrato formal de prestación de servicios.'],
        ];

        foreach ($rows as [$code, $name, $description]) {
            ContributorType::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'is_active' => true]
            );
        }
    }
}
