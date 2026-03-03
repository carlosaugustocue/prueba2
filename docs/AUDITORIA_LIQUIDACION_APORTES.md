# Auditoría: liquidación de aportes a seguridad social

**Objetivo:** Verificar que el cálculo de aportes en el módulo de Seguridad Social cumple con la normativa colombiana vigente.

**Fecha de revisión:** Enero 2026.  
**Referencia:** `docs/NORMATIVA_Y_COMPLEJIDAD_SEGURIDAD_SOCIAL.md`, ley y decretos citados.

---

## 1. Porcentajes y parámetros (contribution_parameters)

| Concepto | Normativa | Valor en seeder | ¿Correcto? |
|----------|-----------|-----------------|------------|
| **Salud total** | 12,5% IBC | 12.5 | Sí |
| **Salud empleador / empleado** | 8,5% / 4% | 8.5 / 4.0 | Sí |
| **Salud independiente** | 12,5% | 12.5 | Sí |
| **Pensión total** | 16% IBC | 16.0 | Sí |
| **Pensión empleador / empleado** | 12% / 4% | 12.0 / 4.0 | Sí |
| **Pensión independiente** | 16% | 16.0 | Sí |
| **ARL clase I–V** | Decreto 768/2022 | 0,522 / 1,044 / 2,436 / 4,350 / 6,960 | Sí |
| **CCF** | 4% | 4.0 | Sí |
| **SENA** | 2% | 2.0 | Sí |
| **ICBF** | 3% | 3.0 | Sí |
| **FSP** (4–16, 16–17, …, ≥20 SMLMV) | Ley 2381/2024 | 1, 1.2, 1.4, 1.6, 1.8, 2% | Sí |
| **SMLMV 2026** | Decreto 1469/2025 | 1.750.905 | Sí |
| **IBC mínimo/máximo** | 1 y 25 SMLMV | 1 y 25 | Sí |

**Conclusión:** Los valores del `ContributionParameterSeeder` coinciden con la normativa referenciada. No hay porcentajes ni topes hardcodeados en el código de cálculo; todo se lee de `contribution_parameters` con vigencia por fecha.

---

## 2. Lógica del ContributionCalculator

- **Dependientes (01, 02):** Se aplica split empleador/empleado en salud y pensión usando `HEALTH.EMPLOYER/EMPLOYEE` y `PENSION.EMPLOYER/EMPLOYEE`. Coincide con la ley.
- **Independientes / 59:** Se usa `HEALTH.INDEPENDENT` y `PENSION.INDEPENDENT` (100% a cargo del cotizante/empresa). Correcto.
- **Parafiscales:** CCF se aplica solo a dependientes (01, 02). SENA e ICBF solo cuando `has_parafiscales` y tipo 01 (02 exento). Coincide con exención de servicio doméstico y con empresas que solo pagan CCF (decisión vía `has_parafiscales`).
- **ARL:** Se aplica según clase de riesgo (1–5) con tarifas parametrizadas; 100% empleador. Correcto.
- **FSP:** Se aplica solo cuando hay pensión. Tramos según IBC/SMLMV (< 4 sin FSP; 4–16 → 1%; …; ≥ 20 → 2%). Base del FSP = IBC efectivo (en tipo 51, IBC proporcional). Correcto según ley.
- **Tipo 51 (proporcional):** Se usa `effectiveIbc = IBC × (días/30)`. Circular 093 habla de proporción por semanas (2/4 = 2 semanas); usar días/30 es una interpretación habitual y proporcionalmente equivalente. Ver observación en §5.

---

## 3. Reglas por tipo de cotizante (ContributorTypeRules)

| Código | Salud | Pensión | ARL | CCF | Parafiscales (SENA/ICBF) | Nota |
|--------|-------|---------|-----|-----|---------------------------|------|
| 01 | Sí (split) | Sí (split) | Sí | Sí | Sí si has_parafiscales | Correcto |
| 02 | Sí (split) | Sí (split) | Sí | Sí | No (exento) | Correcto |
| 03 | Sí 100% | Sí 100% | Sí si clase | No | No | Correcto |
| 04 | No | No | No | No | No | Madre sustituta; correcto |
| 12 | Solo salud | No | No | No | No | Aprendiz lectiva; correcto |
| 19 | Sí | No | Sí | No | No | Aprendiz productiva; correcto |
| 23 | Sí | Sí | Sí | No | No | “Según caso”; implementación genérica; ver §5 |
| 40 | Solo salud | No | No | No | No | UPC adicional; correcto |
| 51 | Proporcional | Proporcional | Sí si clase | No | No | Correcto; ver §5 (IBC y semanas/días) |
| 57 | No | No (voluntario) | No | No | No | No se liquidan obligatorios; correcto |
| 59 | Sí 100% | Sí 100% | Sí 100% empleador | No | No | Contratista CPS; correcto |

---

## 4. Validaciones y riesgos mitigados

- **IBC:** Se valida en `validateProfileForPayroll` contra mínimo y máximo vigentes (1 y 25 SMLMV) desde `ContributionParametersResolver`. Evita IBC fuera de rango.
- **Tipo de cotizante:** Debe estar asignado y ser un código soportado para liquidar.
- **Parámetros vigentes:** Si no hay parámetros para la fecha, los porcentajes leídos son 0 y los montos resultan 0; no se generan montos “inventados”. Recomendación: asegurar que existan parámetros para todos los períodos que se liquiden (p. ej. ejecutar seeder o cargar datos para 2025 si aplica).
- **FSP:** Se calcula sobre el IBC efectivo (incluye proporcional tipo 51). No se aplica cuando no hay pensión (tipos 12, 19, 40, 57, 04). Correcto.

---

## 5. Observaciones y recomendaciones

1. **Tipo 51 – Circular 093:** La circular habla de ingresos **menores a 1 SMLMV** y proporción por **semanas** (ej. 2/4).  
   - **Semanas vs días:** El sistema usa `días trabajados / 30`. Es una práctica aceptada y proporcional; si la UGPP o el operador PILA exigen estrictamente “semanas/4”, se podría añadir un campo opcional “semanas trabajadas” y usar ese factor.  
   - **IBC < 1 SMLMV:** Se recomienda una advertencia (o validación blanda) cuando el tipo sea 51 y el IBC ≥ 1 SMLMV, para alinear con la Circular 093.

2. **Tipo 23 (estudiante):** La normativa indica “según caso”. Hoy se trata como salud + pensión + ARL. Si en la práctica solo aplica ARL (u otra combinación), conviene definir la regla con el revisor legal o la UGPP y ajustar `ContributorTypeRules` en consecuencia.

3. **Distribución Colpensiones/ACCAI (Ley 2381):** El sistema registra el **monto total de pensión**; la distribución entre Colpensiones y ACCAI según 2,3 SMLMV la hace el operador al radicar en PILA. Alineado con lo documentado para el MVP.

4. **Parafiscales (SENA/ICBF):** La exención para empresas con salarios hasta 10 SMLMV que pagan CREE/renta no se calcula automáticamente; el operador debe marcar `has_parafiscales` correctamente. Conviene dejarlo documentado en manual de operación.

---

## 6. Conclusión

- **Porcentajes, topes y parámetros:** Correctos y alineados con la normativa referenciada (Decreto 1469/2025, Ley 2381/2024, Decreto 768/2022, etc.).  
- **Lógica de cálculo:** Split dependiente/independiente, parafiscales, CCF, ARL y FSP están implementados según la ley.  
- **Tipos de cotizante:** Las reglas por código (01, 02, 03, 04, 12, 19, 23, 40, 51, 57, 59) coinciden con la tabla normativa; el único punto a afinar con criterio legal/operador es el tipo 23 y, si se desea, la validación/advertencia para tipo 51 (IBC < 1 SMLMV y uso de semanas).

---

## 7. Caso de verificación: IBC = 1 SMLMV 2026 ($1.750.905)

Se verificó con tests unitarios (`tests/Unit/SocialSecurity/ContributionCalculatorTest.php`) que el calculador reproduce las tablas de referencia:

| Caso | Total empleador | Total empleado/cotizante | Total planilla |
|------|-----------------|---------------------------|----------------|
| **Dependiente (01)** con parafiscales y ARL I | $525.657 | $140.072 | $665.729 |
| **Independiente (03)** con ARL I | — | $508.148 | $508.148 |

Desglose dependiente: Salud 8,5%/4%, Pensión 12%/4%, ARL 0,522%, CCF 4%, SENA 2%, ICBF 3%. FSP no aplica (IBC < 4 SMLMV). Desglose independiente: Salud 12,5%, Pensión 16%, ARL 0,522%. Los tests usan `assertEqualsWithDelta` para tolerar redondeo a centavos.

---

**Recomendación final:** La liquidación está implementada conforme a la ley para los casos cubiertos. Para mayor seguridad jurídica: (1) asegurar que los parámetros cargados en BD correspondan siempre a la normativa vigente por período, y (2) que un revisor legal o contador valide el uso en producción, en especial para tipos 23 y 51 y para la política de `has_parafiscales`.
