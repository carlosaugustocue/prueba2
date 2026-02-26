# Normatividad y complejidad — Seguridad Social en Colombia

**Propósito:** Tener muy en cuenta, en todo el desarrollo del módulo de Seguridad Social de Serviconli, la complejidad normativa y operativa colombiana. Este documento es **referencia obligatoria** para diseño, cálculos, parametrización y trazabilidad.

**Documentos relacionados:**
- [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md)
- [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md)
- [ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md](ARQUITECTURA_Y_PLAN_FEATURE_SEGURIDAD_SOCIAL.md)

**Fecha de referencia normativa:** Enero 2026.

---

## 1. Complejidad normativa

### 1.1 Múltiples entidades y operadores

| Entidad | Alcance | Implicación en el sistema |
|--------|---------|---------------------------|
| **EPS (Salud)** | Más de 30 entidades | Catálogo `eps` con códigos PILA; una por afiliado en perfil SS. |
| **AFP / ACCAI (Pensiones)** | Públicas y privadas (Colpensiones, Porvenir, Protección, Colfondos, Skandia) | Catálogo `afps`; post-reforma las AFP privadas se transforman en ACCAI (Administradoras del Componente Complementario de Ahorro Individual). |
| **ARL (Riesgos laborales)** | Niveles de riesgo I a V (porcentajes distintos) | Catálogo `arps`; campo `arp_risk_class`; porcentaje según clase. |
| **Cajas de Compensación Familiar** | Decenas de entidades | Catálogo `ccfs`; aportes según normativa. |
| **Parafiscales (SENA, ICBF, CCF)** | Aportes parafiscales | Campo `has_parafiscales`; porcentajes parametrizables. |
| **Operadores PILA** | 6 operadores autorizados: SOI (ACH), Mi Planilla, Aportes en Línea, Enlace Operativo, Asopagos, Pago Simple | Catálogo `payment_operators`; credenciales cifradas por afiliado. |

Cada entidad tiene sus propias reglas, códigos y porcentajes. **No hardcodear** códigos ni porcentajes en el código; usar **tablas de parámetros (`contribution_parameters`)** con vigencia por fecha.

### 1.2 Legislación cambiante

- **Reforma pensional (Ley 2381 de 2024):** Reestructuración del sistema pensional en 4 pilares; distribución de aportes según IBC vs 2,3 SMLMV; transformación de AFP en ACCAI.
- **Resolución 467 de 2025 (MinSalud):** Ajusta la PILA para cumplir con la reforma pensional a partir del 1 de agosto de 2025.
- **Circular 093 de 2025 (MinTrabajo):** Autoriza aportes flexibles por semanas (tipo cotizante 51) para independientes con ingresos < 1 SMLMV.
- **Decreto 1469/2025:** Fija SMLMV 2026 en $1.750.905 y auxilio de transporte en $249.095.
- **Resolución DIAN 000238/2025:** UVT 2026 = $52.374.
- Reformas tributarias que modifican **bases y porcentajes**.
- Cambios en **UVT (Unidad de Valor Tributario)** que afectan umbrales.
- Modificaciones en **normativas laborales**.

**Implicación:** Los porcentajes y topes deben estar **parametrizados** en la tabla `contribution_parameters` con **vigencia por fecha** (históricos) para no tener que tocar código en cada reforma.

---

## 2. Valores vigentes 2026

### 2.1 Base del sistema

| Concepto | Valor 2026 | Norma |
|----------|-----------|-------|
| **SMLMV** | $1.750.905 | Decreto 1469/2025 |
| **Auxilio de transporte** | $249.095 | Decreto 1469/2025 |
| **UVT** | $52.374 | Resolución DIAN 000238/2025 |
| **IBC mínimo** | $1.750.905 (1 SMLMV) | — |
| **IBC máximo** | $43.772.625 (25 SMLMV) | — |
| **Umbral pilar contributivo** | $4.027.082 (2,3 SMLMV) | Ley 2381/2024 |

### 2.2 Porcentajes de aportes

#### Salud (12,5% del IBC)

| Componente | Porcentaje | Pagado por |
|-----------|-----------|------------|
| Total | 12,5% | — |
| Empleador | 8,5% | Empresa |
| Empleado | 4,0% | Trabajador |
| Independiente | 12,5% | Cotizante |

#### Pensión (16% del IBC)

| Componente | Porcentaje | Pagado por |
|-----------|-----------|------------|
| Total | 16,0% | — |
| Empleador | 12,0% | Empresa |
| Empleado | 4,0% | Trabajador |
| Independiente | 16,0% | Cotizante |

**Distribución post-reforma pensional:**
- IBC ≤ 2,3 SMLMV ($4.027.082) → 100% a Colpensiones (Prima Media)
- IBC > 2,3 SMLMV → 16% sobre 2,3 SMLMV a Colpensiones + 13,2% del excedente a ACCAI elegida + porcentaje restante al Fondo de Ahorro del Pilar Contributivo

#### ARL — Riesgos Laborales (100% empleador)

| Clase | Nivel de riesgo | Tarifa | Actividades típicas |
|-------|----------------|--------|---------------------|
| I | Mínimo | 0,522% | Oficinas, comercio, educación |
| II | Bajo | 1,044% | Manufactura liviana, restaurantes |
| III | Medio | 2,436% | Manufactura pesada, transporte |
| IV | Alto | 4,350% | Construcción, minería a cielo abierto |
| V | Máximo | 6,960% | Trabajo en alturas, minería subterránea |

Clasificación según Decreto 768 de 2022.

#### Parafiscales (solo empleadores que apliquen)

| Concepto | Porcentaje | Pagado por |
|----------|-----------|------------|
| SENA | 2,0% | Empleador |
| ICBF | 3,0% | Empleador |
| CCF | 4,0% | Empleador |
| **Total parafiscales** | **9,0%** | — |

**Nota:** Empresas con empleados que ganen hasta 10 SMLMV y que paguen CREE/impuesto de renta están exentas de SENA e ICBF (solo pagan CCF 4%).

#### Fondo de Solidaridad Pensional (FSP) — Aporte adicional

| Rango de IBC (en SMLMV) | Porcentaje adicional |
|--------------------------|---------------------|
| < 4 SMLMV | 0% (no aplica) |
| ≥ 4 y < 16 SMLMV | 1,0% |
| ≥ 16 y < 17 SMLMV | 1,2% |
| ≥ 17 y < 18 SMLMV | 1,4% |
| ≥ 18 y < 19 SMLMV | 1,6% |
| ≥ 19 y < 20 SMLMV | 1,8% |
| ≥ 20 SMLMV | 2,0% |

El FSP se descuenta al trabajador/cotizante y se destina a la subcuenta de solidaridad.

---

## 3. Tipos de cotizante PILA

Los códigos de tipo de cotizante determinan las reglas de liquidación y quién asume los aportes.

### 3.1 Tabla de tipos principales

| Código | Descripción | Paga salud | Paga pensión | Paga ARL | Parafiscales |
|--------|-------------|-----------|-------------|---------|-------------|
| 01 | Dependiente (empleado) | Split empleador/empleado | Split empleador/empleado | Empleador 100% | Si aplica |
| 02 | Servicio doméstico | Split | Split | Empleador 100% | Exento |
| 03 | Independiente | Cotizante 100% | Cotizante 100% | Voluntario | No aplica |
| 04 | Madre sustituta | — | — | — | — |
| 12 | Aprendiz SENA lectiva | Solo salud | No | No | No |
| 19 | Aprendiz SENA productiva | Salud + ARL | No | Sí | No |
| 23 | Estudiante | Según caso | Según caso | Según caso | No |
| 40 | Beneficiario UPC adicional | Solo salud | No | No | No |
| 51 | Independiente flexible (semanas) | Proporcional | Proporcional | Voluntario | No |
| 57 | Independiente voluntario | No | Voluntario | No | No |
| 59 | Contratista CPS | Empresa descuenta | Empresa descuenta | Empresa 100% | No directo |

### 3.2 Reglas especiales por tipo cotizante

**Tipo 59 (Contratista con contrato de prestación de servicios):**
- A partir de julio de 2025, la empresa contratante asume:
  - Calcular el IBC del contratista (40% del valor bruto del contrato mensual, mínimo 1 SMLMV).
  - Descontar los aportes de salud, pensión y ARL de los honorarios.
  - Pagar los aportes directamente al sistema de seguridad social vía PILA.
- **Impacto en Serviconli:** Es el caso más común. Serviconli administra este flujo donde la empresa (pagador) delega la gestión de aportes de sus contratistas.

**Tipo 51 (Independiente con aportes flexibles):**
- Autorizado por Circular 093 de septiembre 2025 del Ministerio de Trabajo.
- Permite cotizar por semanas proporcionalmente al ingreso.
- Para independientes con ingresos < 1 SMLMV mensual.
- Cotización proporcional: si trabaja 2 semanas, paga 2/4 del aporte mensual mínimo.

---

## 4. Sistema PILA

La **Planilla Integrada de Liquidación de Aportes** añade complejidad:

### 4.1 Tipos de planilla

| Código | Tipo | Uso |
|--------|------|-----|
| E | Empleados | Empresas con nómina formal |
| I | Independientes | Independientes por cuenta propia |
| S | Servicio doméstico | Empleadores de servicio doméstico |
| Y | Independiente empresarial | Empresas que pagan por sus contratistas (tipo 59) |
| N | Correcciones | Corrección de planilla ya radicada |
| A | Mora | Pago extemporáneo con intereses |
| K | Estudiantes | Aportes por estudiantes |
| M | Mora empleados | Planilla de mora para empleados |

### 4.2 Operadores autorizados

| Operador | Código | Observaciones |
|----------|--------|---------------|
| Aportes en Línea | AEL | Más usado por empresas medianas |
| SOI (ACH Colombia) | SOI | Planilla electrónica y asistida |
| Asopagos | ASO | — |
| Enlace Operativo | ENL | Especializado en empresas grandes |
| Mi Planilla | MIP | — |
| Pago Simple | SIM | Enfocado en microempresas |

### 4.3 Calendario de vencimiento PILA

Según Decreto 1990 de 2016. El vencimiento se determina por los dos últimos dígitos del NIT (empresas) o cédula (personas naturales):

| Últimos 2 dígitos | Día hábil del mes siguiente |
|--------------------|---------------------------|
| 00–07 | 2 |
| 08–14 | 3 |
| 15–21 | 4 |
| 22–28 | 5 |
| 29–35 | 6 |
| 36–42 | 7 |
| 43–49 | 8 |
| 50–56 | 9 |
| 57–63 | 10 |
| 64–69 | 11 |
| 70–75 | 12 |
| 76–81 | 13 |
| 82–87 | 14 |
| 88–93 | 15 |
| 94–99 | 16 |

**Día hábil:** No sábado, no domingo, no festivo (calendario oficial Colombia).

Ya implementado en `DueDateCalculator` del módulo.

### 4.4 Implicaciones para el módulo

- No generar archivos PILA en formato plano en el MVP; primero documentar formato y validaciones oficiales.
- Cuando se implemente generación PILA futura: **validaciones robustas** antes de generar; **logging** de cada generación; **no hardcodear** códigos de entidad.
- El MVP se enfoca en la **administración** (calcular montos, rastrear estados, controlar vencimientos), no en la **generación del archivo plano PILA** (eso lo hace el operador).

---

## 5. Reforma pensional (Ley 2381 de 2024)

### 5.1 Estructura del nuevo sistema de pilares

| Pilar | Nombre | Población objetivo | Administrador |
|-------|--------|-------------------|---------------|
| Solidario | Renta básica solidaria | Adultos mayores en vulnerabilidad sin pensión | Estado |
| Semicontributivo | Renta vitalicia | Quienes cotizaron pero no completan requisitos | Colpensiones |
| Contributivo | Pensión por vejez | Trabajadores activos que cotizan | Colpensiones + ACCAI |
| Voluntario | Ahorro complementario | Cualquier persona | AFP voluntarias |

### 5.2 Distribución de aportes a pensión

**Para el pilar contributivo, los aportes (16%) se distribuyen según el IBC:**

| Nivel de IBC | Destino Colpensiones (Prima Media) | Destino ACCAI | Fondo de Ahorro |
|-------------|----------------------------------|---------------|----------------|
| ≤ 2,3 SMLMV | 100% del 16% | 0% | 0% |
| > 2,3 SMLMV | 16% sobre los primeros 2,3 SMLMV | 13,2% del excedente | Diferencia |

**Ejemplo con IBC de $5.000.000:**
- Primeros $4.027.082 (2,3 SMLMV) → 16% = $644.333 → Colpensiones
- Excedente $972.918 → 13,2% = $128.425 → ACCAI elegida

### 5.3 ACCAI (reemplazo de AFP privadas)

Las AFP se transforman en Administradoras del Componente Complementario de Ahorro Individual:
- Porvenir
- Protección
- Colfondos
- Skandia

### 5.4 Impacto en el sistema Serviconli

1. **Tabla `afps`:** Mantener, pero considerar agregar campo que identifique si es Colpensiones o ACCAI.
2. **Cálculos:** `ContributionCalculator` debe manejar la distribución Colpensiones/ACCAI cuando el IBC > 2,3 SMLMV. En el MVP, registrar el monto total de pensión; la distribución interna la hace el operador PILA.
3. **Informativo:** Mostrar en el desglose de planilla la nota sobre distribución cuando aplique.

---

## 6. Tipos de contratación y su impacto

| Tipo | IBC | Reglas especiales |
|------|-----|-------------------|
| Empleado dependiente | Salario mensual | Split empleador/empleado estándar |
| Contratista CPS (tipo 59) | 40% del valor del contrato (mín. 1 SMLMV) | Post jul/2025: empresa calcula IBC y descuenta aportes |
| Independiente cuenta propia (tipo 03) | 40% de ingresos netos mensuales (mín. 1 SMLMV) | Cotizante asume 100% |
| Salario integral | 70% del salario integral (13 SMLMV base + factor prestacional) | Factor diferente para aportes |
| Aprendiz SENA | Según etapa | Solo salud (lectiva) o salud+ARL (productiva) |
| Pensionado que trabaja | Según ingresos | Solo salud (no cotiza pensión) |
| Independiente flexible (tipo 51) | Proporcional por semanas | Circular 093/2025; < 1 SMLMV |

**Para el MVP de Serviconli:** Enfocarse en tipos 01 (dependiente), 03 (independiente) y 59 (contratista CPS), que representan el 95%+ de los casos de los clientes.

---

## 7. Plazos y sanciones

- Pagos antes de **fechas específicas** según calendario PILA (día hábil 2 al 16).
- **Intereses moratorios** por pago extemporáneo: tasa máxima legal vigente por cada día de mora.
- **Multas de la UGPP** por incumplimiento: hasta 5% del valor de los aportes dejados de pagar, por cada mes de mora.
- **Responsabilidad solidaria** del empleador: si no paga aportes, responde con su patrimonio.
- **Desafiliación por mora:** Después de ciertos meses sin pago, el trabajador puede perder cobertura.

**Implicación en el sistema:**
- `DueDateCalculator` ya contempla el día hábil de vencimiento.
- El estado `OVERDUE` se asigna automáticamente cuando `hoy > due_date` y la planilla no está PAID.
- Futuro: cálculo de intereses moratorios basado en tasa y días de mora (parametrizable).

---

## 8. Desafíos técnicos (resumen)

| Área | Qué implica |
|------|-------------|
| **Backend (Laravel)** | Tabla `contribution_parameters` con vigencia; `ContributionCalculator` con lógica pura de cálculo según tipo cotizante; `PayrollService` para orquestar; `DueDateCalculator` para fechas; auditoría en `payroll_trackings`; scheduler para `markOverdue`. |
| **Frontend (Vue)** | Simulador/preview de aportes antes de liquidar; desglose visual de montos; dashboard con vencimientos y mora; filtros por estado, período, pagador; badge de estados con colores. |
| **Base de datos** | Históricos de cambios normativos (`contribution_parameters` con vigencia); `calculation_metadata` (JSON) en planilla para reproducir cálculos; índices eficientes en planillas por (affiliate_id, year, month), estado, due_date. |
| **Normativa** | Reforma pensional que cambia distribución de aportes; tipos de cotizante con reglas diferentes; FSP escalonado por SMLMV; cambio anual de SMLMV, UVT, auxilio. |

---

## 9. Recomendaciones obligatorias para el módulo

### 9.1 Parametrización

- **Mantener porcentajes y valores en base de datos, no hardcodeados.**
  Tabla `contribution_parameters` con: `type` (HEALTH, PENSION, ARL, CCF, SENA, ICBF, FSP, SYSTEM), `subtype` (TOTAL, EMPLOYER, EMPLOYEE, INDEPENDENT, RISK_1..5, SMLMV, etc.), `value`, `value_type` (PERCENTAGE, AMOUNT, MULTIPLIER), `valid_from`, `valid_to`.
- Los servicios de cálculo (`ContributionCalculator`) leen de `contribution_parameters`, nunca de constantes.
- Catálogos (EPS, AFP, ARL, CCF) con códigos oficiales PILA; no texto libre.
- **Cada enero:** Actualizar seeder/BD con nuevo SMLMV, auxilio de transporte, UVT. Cerrar vigencia anterior (`valid_to = '20XX-12-31'`), crear nuevo registro (`valid_from = '20XX-01-01'`).

### 9.2 Históricos

- **Guardar versiones de configuraciones por fechas.**
  Si cambia un porcentaje, no borrar el anterior; cerrar vigencia (`valid_to`) y crear nuevo registro con nueva `valid_from`. Al liquidar un período pasado se usan los valores que regían en esa fecha.
- En planillas: conservar los montos calculados y el `calculation_metadata` en el momento de la liquidación. No recalcular "hoy" con reglas nuevas para períodos antiguos.
- El `calculation_metadata` debe contener: IBC usado, porcentajes aplicados, SMLMV del período, tipo cotizante, clase ARL, y fecha/usuario del cálculo.

### 9.3 Validaciones robustas

- **Antes de liquidar planilla:**
  - IBC en rango: $1.750.905 – $43.772.625 (actualizar según SMLMV vigente, leer de `contribution_parameters` type=SYSTEM subtype=SMLMV).
  - `payment_day` entre 2 y 16.
  - Tipo cotizante válido y con código existente en catálogo.
  - Clase ARL asignada si el tipo cotizante requiere ARL.
  - EPS y AFP/ACCAI activas si el tipo cotizante las requiere.
  - No duplicidad de planilla para mismo afiliado/año/mes.
- **Mensajes de error claros** en backend (FormRequest) y frontend.

### 9.4 Logging y trazabilidad

- **Cada cálculo queda documentado para auditorías.**
  Campo `calculation_metadata` (JSON) en la planilla con: parámetros usados, IBC, tipo cotizante, clase ARL, fecha de cálculo, usuario.
- **Transiciones de estado** en `payroll_trackings`: quién, cuándo, de qué estado a cuál, metadata adicional.
- **Novedades** al cambiar perfil SS: tipo de novedad, fecha efectiva, valor anterior, valor nuevo, usuario.
- **Preparación para fiscalización UGPP:** Con `calculation_metadata` + `payroll_trackings` se puede responder cualquier pregunta sobre cómo, cuándo y con qué valores se liquidó.

### 9.5 Flexibilidad

- **Diseñar pensando en cambios frecuentes.**
  - Nuevas entidades (EPS, AFP, ARL, CCF): agregar registros a catálogos, no tocar código.
  - Nuevos porcentajes o topes: agregar parámetros en `contribution_parameters` con nueva vigencia.
  - Nuevos tipos de cotizante: ampliar catálogo `contributor_types`.
  - Reforma pensional futura: la distribución Colpensiones/ACCAI se maneja con parámetros, no con lógica hardcodeada.
  - Nuevo SMLMV cada enero: agregar registro en `contribution_parameters` type=SYSTEM subtype=SMLMV.

---

## 10. Checklist de diseño antes de implementar cálculos o PILA

- [ ] ¿Los porcentajes o topes están en BD (`contribution_parameters`) con vigencia?
- [ ] ¿Hay histórico de parámetros por fecha para liquidaciones pasadas?
- [ ] ¿Se validan IBC, tipo de cotizante, clase ARL y entidades antes de liquidar?
- [ ] ¿Queda registro (`calculation_metadata` + `payroll_trackings`) de qué valores se usaron?
- [ ] ¿Los catálogos (EPS, AFP, ARL, CCF) tienen código oficial y están actualizados?
- [ ] ¿Se considera el FSP escalonado por SMLMV?
- [ ] ¿Se distingue el flujo según tipo de cotizante (01 vs 03 vs 59)?
- [ ] ¿Las planillas pagadas son inmutables?
- [ ] ¿Se puede responder a una fiscalización UGPP con los datos del sistema?

Este documento debe actualizarse cuando cambie la normativa o cuando se incorporen nuevas entidades o conceptos al módulo.
