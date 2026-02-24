# Normatividad y complejidad — Seguridad Social en Colombia

**Propósito:** Tener muy en cuenta, en todo el desarrollo del módulo de Seguridad Social de Serviconli, la complejidad normativa y operativa colombiana. Este documento es **referencia obligatoria** para diseño, cálculos, parametrización y trazabilidad.

**Documentos relacionados:** [PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md](PLAN_IMPLEMENTACION_SEGURIDAD_SOCIAL.md), [TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md)

---

## 1. Complejidad normativa

### 1.1 Múltiples entidades y operadores

| Entidad | Alcance | Implicación en el sistema |
|--------|---------|---------------------------|
| **EPS (Salud)** | Más de 30 entidades | Catálogo `eps` con códigos PILA; una por afiliado en perfil SS. |
| **AFP / Fondos de pensiones** | Públicas y privadas (Colpensiones, Porvenir, Protección, etc.) | Catálogo `afps`; reglas y porcentajes según tipo. |
| **ARL (Riesgos laborales)** | Niveles de riesgo I a V (porcentajes distintos) | Catálogo `arps`; campo `arp_risk_class`; porcentaje según clase. |
| **Cajas de Compensación Familiar** | Decenas de entidades | Catálogo `ccfs`; aportes según normativa. |
| **ICBF, SENA (parafiscales)** | Aportes parafiscales | Campo `has_parafiscales`; porcentajes parametrizables (SENA 2%, ICBF 3%, Cajas 4%). |

Cada entidad tiene sus propias reglas, códigos y porcentajes. **No hardcodear** códigos ni porcentajes en el código; usar **tablas de parámetros o catálogos** con vigencia por fecha.

### 1.2 Legislación cambiante

- Reformas tributarias que modifican **bases y porcentajes**.
- Decretos que ajustan **salario mínimo**, **auxilio de transporte**.
- Cambios en **UVT (Unidad de Valor Tributario)**.
- Modificaciones en **normativas laborales**.

**Implicación:** Los porcentajes y topes (por ejemplo salud 12,5%, pensión 16%, rangos ARL, IBC mínimo/máximo) deben estar **parametrizados** y con **vigencia por fecha** (históricos) para no tener que tocar código en cada reforma.

---

## 2. Cálculos complejos

### 2.1 Variables a considerar

- Salario base vs salario integral.
- Días trabajados vs días del mes.
- Incapacidades, licencias, vacaciones.
- Horas extras, recargos nocturnos, dominicales.
- Bonificaciones, comisiones.
- Deducciones (retención en la fuente, embargos, préstamos).

En el **MVP** el foco es IBC (Ingreso Base de Cotización) y montos derivados; extensiones futuras pueden incorporar más variables. Los **cálculos que sí hagamos** (salud, pensión, ARL, CCF) deben usar **parámetros de BD**, no literales en código.

### 2.2 Bases de cotización (referencia normativa)

| Concepto | Porcentaje / base | Nota |
|----------|-------------------|------|
| **Salud** | 12,5% (empleador 8,5%, empleado 4%) | Base: IBC. |
| **Pensión** | 16% (empleador 12%, empleado 4%) | Base: IBC. |
| **ARL** | 0,522% a 6,96% según nivel de riesgo (100% empleador) | Clase I a V. |
| **Parafiscales** | SENA 2%, ICBF 3%, Cajas 4% | Solo para ciertos empleadores; parametrizable. |

Estos valores pueden cambiar por ley. El sistema debe leerlos de **tablas de parámetros** (por ejemplo `contribution_parameters` con vigencia desde/hasta y tipo: salud_employer, salud_employee, pension_employer, pension_employee, arl_by_risk_class, etc.).

---

## 3. Sistema PILA

La **Planilla Integrada de Liquidación de Aportes** añade complejidad:

- Archivos con **formato específico** (.txt u otro).
- **Códigos y estructuras** estrictas.
- **Validaciones múltiples** antes de enviar.
- Integración con **operadores de información** (Enlace Operativo, Simple, etc.).

**Implicación para el módulo:**

- No generar archivos PILA en el MVP sin antes documentar formato y validaciones oficiales.
- Cuando se implemente generación PILA: **validaciones robustas** antes de generar; **logging** de cada generación (qué se incluyó, período, afiliados); **no hardcodear** códigos de entidad ni de concepto.

---

## 4. Tipos de contratación

Diferentes regímenes con reglas distintas:

- Empleados dependientes.
- Contratistas (según ingresos).
- Empleados de medio tiempo.
- Salario integral (factores diferentes).
- Aprendices SENA.
- Pensionados que trabajan.

**Implicación:** El **tipo de cotizante** (catálogo `contributor_types`, códigos 01–59) y el **tipo de cliente** (independiente, dependiente, SERVICONLI, etc.) deben estar bien definidos en el perfil SS; los cálculos y la generación PILA (futura) deben considerar estos tipos. Mantener **documentación** de qué tipo aplica a cada cálculo.

---

## 5. Plazos y sanciones

- Pagos antes de **fechas específicas** cada mes (día hábil 2 al 16 según NIT/documento).
- **Multas** por mora o errores.
- **Intereses moratorios**.
- **Responsabilidad solidaria** del empleador.

**Implicación:** El **DueDateCalculator** ya contempla el día hábil de vencimiento (2–16). En planillas, el estado **OVERDUE** y cualquier cálculo futuro de intereses o multas deben basarse en **parámetros** (porcentaje, tope) y **fechas** registradas, no en constantes fijas en código.

---

## 6. Desafíos técnicos (resumen)

| Área | Qué implica |
|------|-------------|
| **Backend (Laravel)** | Tablas para múltiples entidades (ya: eps, afps, arps, ccfs, payers, profiles); cálculos con reglas de negocio complejas usando **servicios** (ej. PayrollService, DueDateCalculator); manejo de períodos y vigencia; en el futuro generación de archivos PILA; **auditoría y trazabilidad** (novedades, communication_logs, payroll_trackings). |
| **Frontend (Vue)** | Interfaces para configurar parámetros (admin); validaciones en tiempo real; reportes por conceptos; manejo de excepciones y casos especiales (mensajes claros). |
| **Base de datos** | **Históricos de cambios normativos** (parámetros con vigencia desde/hasta); configuraciones por empresa/pagador si aplica; **trazabilidad completa** de cálculos (quién, cuándo, qué valores se usaron). |

---

## 7. Recomendaciones obligatorias para el módulo

Estas recomendaciones deben aplicarse en el diseño y en las tareas concretas ([TAREAS_SEGURIDAD_SOCIAL.md](TAREAS_SEGURIDAD_SOCIAL.md)).

### 7.1 Parametrización

- **Mantener porcentajes y valores en base de datos, no hardcodeados.**  
  Ejemplo: tabla `contribution_parameters` (o equivalente) con: tipo (salud_employer, health_employee, pension_employer, pension_employee, arl_class_1 … arl_class_5, sena, icbf, ccf), valor (porcentaje o decimal), vigencia_desde, vigencia_hasta. Los servicios de cálculo (PayrollService) leen de ahí.
- Catálogos (EPS, AFP, ARP, CCF) con códigos oficiales; no texto libre.

### 7.2 Históricos

- **Guardar versiones de configuraciones por fechas.**  
  Si se cambia un porcentaje o un tope, no borrar el anterior; usar vigencia (vigencia_desde, vigencia_hasta) para que, al liquidar un período pasado, se usen los valores que regían en esa fecha.
- En planillas: conservar los montos calculados (health_amount, pension_amount, etc.) en el momento de la liquidación; no recalcular “hoy” con reglas nuevas para períodos antiguos.

### 7.3 Validaciones robustas

- **Validar antes de generar PILA** (cuando se implemente): rangos de IBC, fechas, códigos de entidad, tipo de cotizante.
- En el MVP: validar IBC en rango (ej. 290.000 – 14.235.800), payment_day 2–16, FKs a catálogos existentes; mensajes de error claros en backend y en frontend.

### 7.4 Logging y trazabilidad

- **Documentar cada cálculo para auditorías.**  
  Opción: tabla `payroll_calculation_logs` o campo `calculation_metadata` (JSON) en la planilla con: parámetros usados (porcentajes, IBC, tipo cotizante), fecha de cálculo, usuario/sistema. Así se puede reproducir “por qué salió este monto”.
- Novedades: ya se registra quién y cuándo cambió perfil SS. Mantener ese criterio en cambios de estado de planilla (payroll_trackings).

### 7.5 Flexibilidad

- **Diseñar pensando en cambios frecuentes.**  
  Nuevas entidades (EPS, AFP, ARL, CCF): agregar registros a catálogos, no tocar código. Nuevos porcentajes o topes: agregar o actualizar parámetros con vigencia. Nuevos tipos de cotizante: ampliar catálogo o enums con documentación.

---

## 8. Checklist de diseño antes de implementar cálculos o PILA

- [ ] ¿Los porcentajes o topes están en BD (parametrizados) con vigencia?
- [ ] ¿Hay histórico de parámetros por fecha para liquidaciones pasadas?
- [ ] ¿Se validan IBC, tipo de cotizante y fechas antes de guardar o generar?
- [ ] ¿Queda registro (log o tabla) de qué valores se usaron en cada cálculo?
- [ ] ¿Los catálogos (EPS, AFP, ARL, CCF) tienen código oficial y están actualizados?

Este documento debe actualizarse cuando cambie la normativa o cuando se incorporen nuevas entidades o conceptos al módulo.
