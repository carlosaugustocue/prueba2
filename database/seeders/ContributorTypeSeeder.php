<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\SocialSecurity\Models\ContributorType;

class ContributorTypeSeeder extends Seeder
{
    /**
     * Catálogo PILA — Tipos de cotizante con reglas de liquidación.
     * Ref: mapeo_datasegura_a_base_de_datos.md, Resolución 2388/2016, Decreto 780/2016.
     * Cada fila incluye las reglas de aportes para que ContributorTypeRules lea de BD.
     */
    public function run(): void
    {
        foreach ($this->getRows() as $row) {
            ContributorType::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_dependent' => $row['is_dependent'],
                    'parafiscales_allowed' => $row['parafiscales_allowed'],
                    'health_applies' => $row['health_applies'],
                    'pension_applies' => $row['pension_applies'],
                    'arl_applies' => $row['arl_applies'],
                    'ccf_applies' => $row['ccf_applies'],
                    'is_proportional' => $row['is_proportional'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function getRows(): array
    {
        return [
            [
                'code' => '01', 'name' => 'Dependiente',
                'description' => 'Empleados de empresa constituida como persona Natural o Jurídica.',
                'is_dependent' => true, 'parafiscales_allowed' => true,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => true, 'is_proportional' => false,
            ],
            [
                'code' => '02', 'name' => 'Servicio Doméstico',
                'description' => 'Trabajadores del servicio doméstico.',
                'is_dependent' => true, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => true, 'is_proportional' => false,
            ],
            [
                'code' => '03', 'name' => 'Independiente',
                'description' => 'Trabajador independiente.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '04', 'name' => 'Madre sustituta',
                'description' => 'Se aplica a trabajadores de un hogar sustituto ICBF.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '05', 'name' => 'Independiente voluntario al Sistema de Riesgos Laborales',
                'description' => 'Independiente afiliado voluntariamente a ARL (equivalente a 57 en algunos operadores).',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '12', 'name' => 'Aprendices en etapa lectiva',
                'description' => 'Aprendiz SENA en etapa lectiva; solo salud.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '16', 'name' => 'Afiliación colectiva al sistema de seguridad integral',
                'description' => 'Afiliado de una Fundación bajo el registro del NIT.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '18', 'name' => 'Funcionarios públicos sin tope máximo de IBC',
                'description' => 'IBC sin tope de 25 SMLMV.',
                'is_dependent' => true, 'parafiscales_allowed' => true,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => true, 'is_proportional' => false,
            ],
            [
                'code' => '19', 'name' => 'Aprendices en etapa productiva',
                'description' => 'Aprendiz SENA en etapa productiva; salud y ARL.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '20', 'name' => 'Estudiantes',
                'description' => 'Estudiantes; IBC puede ser superior a 25 SMLMV.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '21', 'name' => 'Estudiantes de postgrado en salud',
                'description' => 'Estudiantes de residencia médica o postgrado clínico.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '22', 'name' => 'Profesor de establecimiento particular',
                'description' => 'Régimen especial Ley 789 de 2002.',
                'is_dependent' => true, 'parafiscales_allowed' => true,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => true, 'is_proportional' => false,
            ],
            [
                'code' => '23', 'name' => 'Estudiantes aporte solo riesgos laborales',
                'description' => 'Estudiantes que solo aportan a ARL.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => false,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '30', 'name' => 'Dependiente entidades o universidades públicas',
                'description' => 'Regímenes especial y de excepción.',
                'is_dependent' => true, 'parafiscales_allowed' => true,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => true, 'is_proportional' => false,
            ],
            [
                'code' => '31', 'name' => 'Cooperados o precooperativas de trabajo asociado',
                'description' => 'Asociados o Cooperados de las CTA.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '32', 'name' => 'Cotizante miembro carrera diplomática o consular',
                'description' => 'Miembros de organismos multilaterales.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '33', 'name' => 'Beneficiario del fondo de solidaridad pensional',
                'description' => 'Trámites con Fondo de Solidaridad Pensional; porcentaje pensión distinto al legal.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => true,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '40', 'name' => 'Beneficiario UPC adicional',
                'description' => 'Pago UPC Adicional al sistema de salud.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '42', 'name' => 'Cotizante Independiente pago solo salud',
                'description' => 'Cotizantes de bajos ingresos (Art. 2 Ley 1250 de 2008).',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '43', 'name' => 'Cotizante a pensiones con pago por tercero',
                'description' => 'Interviene un tercero para el pago de aportes; solo pensiones.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => true,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '51', 'name' => 'Trabajador de tiempo parcial afiliado al régimen subsidiado',
                'description' => 'Remuneración < 1 SMLMV; periodos < 30 días en el mes.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => true,
            ],
            [
                'code' => '56', 'name' => 'Prepensionado con aporte voluntario en salud',
                'description' => 'Prepensionados en trámite de pensión que aportan voluntariamente.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '57', 'name' => 'Independiente voluntario al Sistema de Riesgos Laborales',
                'description' => 'En archivos PILA a veces se usa 05; mantener 57 como válido si viene en datos.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => false, 'pension_applies' => false,
                'arl_applies' => false, 'ccf_applies' => false, 'is_proportional' => false,
            ],
            [
                'code' => '59', 'name' => 'Independiente con contrato de prestación de servicios superior a 1 mes',
                'description' => 'Persona natural con contrato formal de prestación de servicios.',
                'is_dependent' => false, 'parafiscales_allowed' => false,
                'health_applies' => true, 'pension_applies' => true,
                'arl_applies' => true, 'ccf_applies' => false, 'is_proportional' => false,
            ],
        ];
    }
}
