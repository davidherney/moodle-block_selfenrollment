<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Strings for component 'block_selfenrollment', language 'en', branch 'MOODLE_31_STABLE'
 *
 * @package   block_selfenrollment
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Self enrollment';

//Capabilities
$string['selfenrollment:addinstance'] = 'Add a new Self enrollment block';
$string['selfenrollment:myaddinstance'] = 'Add a new Self enrollment block to Dashboard';
$string['selfenrollment:manage'] = 'Manage offers';
$string['selfenrollment:enrol'] = 'Self enrollment into offers';

$string['eventoffer_created'] = 'Oferta creada';
$string['eventoffer_deleted'] = 'Oferta eliminada';
$string['eventoffer_updated'] = 'Oferta modificada';
$string['eventenrol_created'] = 'Usuario matriculado';


$string['manage_offers'] = 'Gestionar ofertas';
$string['offertypes'] = 'Tipo de oferta';
$string['newblocktitle'] = 'Self enrollment';
$string['offer_add'] = 'La oferta fue creada';
$string['offer_delete'] = 'Borrar oferta';
$string['offer_deleted'] = 'Se borró la oferta';
$string['offers_count'] = 'Ofertas ({$a->count}/{$a->total})';
$string['search_courses'] = 'Courses search and enrol';
$string['search_courses_text'] = 'Courses search and enrol text';
$string['top_courses'] = 'Top courses';
$string['top_courses_text'] = 'Top courses text';
$string['offer'] = 'Oferta';
$string['offers'] = 'Ofertas';
$string['offer_type'] = 'Tipo de oferta';
$string['offer_tab_general'] = 'Información general';
$string['offer_tab_cohort'] = 'Cohortes';
$string['missingfield'] = 'Este campo es obligatorio';
$string['modality'] = 'Modalidad';
$string['modality_self'] = 'Autoformación';
$string['modality_tutor'] = 'Acompañamiento de tutor';
$string['intensity'] = 'Intensidad horaria';
$string['notnumeric'] = 'Este campo debe ser un valor numérico';
$string['observations'] = 'Observaciones';
$string['currency'] = 'Moneda';
$string['active'] = 'Activo';
$string['cost'] = 'Costo';
$string['cohortoffer'] = 'Cohortes de la oferta -{$a->name}-';
$string['cohortoffer_title'] = 'Cohorte de oferta';
$string['cohortoffer_delete'] = 'Borrar cohorte de oferta';
$string['cohortoffer_deleted'] = 'Se borró la cohorte de la oferta';
$string['timestart'] = 'Fecha de inicio';
$string['timeend'] = 'Fecha de fin';
$string['cohortoffer_add'] = 'La solicitud fue creada';
$string['timeend_after'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
$string['cohort_na'] = 'La cohorte ya no existe';
$string['cohortid_required'] = 'La cohorte es necesaria. Debe crear una cohorte para poder continuar.';
$string['offer_enrol'] = 'Matrícula';
$string['enrolcheck'] = 'Estás a punto de matricularte en <strong>{$a->name}</strong>. <br />Ten presente que al matricularte aceptas nuestros <a href="{$a->terms}" target="_blank">términos y condiciones.</a>';
$string['terms'] = '<p style="text-align: justify;">Este acuerdo establece los términos y condiciones que rigen a los servicios ofrecidos a través del sitio.</p>
<ol>
    <li>
        Cada uno de los servicios ofrecidos tiene la descripción e información pertinente sobre las características de estos y fechas de realización, si aplica.
    </li>
    <li>
        Otras condiciones...
    </li>
</ol>';
$string['enroled'] = 'Tu matrícula ha sido exitosa, ingresa al curso a través de la opción Mis cursos ubicada en la parte superior derecha de la plataforma';
$string['enrol'] = 'Matricularme';
$string['notenrol_notcohort'] = 'No es posible procesar tu matrícula en este momento.';
$string['enrol_notcohort'] = 'No disponible';
$string['enrol_enroled'] = 'Matriculado';
$string['intensity_format'] = '{$a} horas';
$string['free'] = 'N/A';
$string['more'] = 'Ver más';
$string['operations'] = 'Opciones';
$string['state_detail'] = 'Estado de la oferta';
$string['more_title'] = 'Detalle de la oferta';
$string['enrol_notactive'] = 'Inactivo';
$string['terms_title'] = 'Términos y condiciones';
$string['offer_explanation'] = 'A continuación encontrarás toda la oferta de cursos que tenemos disponibles para ti y podrás matricularte en aquellos que sean de tu interés. Si lo prefieres puedes filtrar la oferta por palabras claves en el nombre o tipo.';

$string['enrolled_report'] = 'Informe de matriculados';
$string['manual_enrolment'] = 'Matriculación manual';
$string['enroltype'] = 'Tipo de matrícula';
$string['enrolled_notfound'] = 'No se encontraron datos.';
$string['userid'] = 'Id de usuario';
$string['enroltype_self'] = 'Automatriculación';
$string['enroltype_manual'] = 'Manual';
$string['exportfilename'] = 'selfenrollment_{$a}';
$string['enrolemail'] = 'Correo de bienvenida';
$string['enroledsubject'] = 'Información sobre su matrícula en el curso {$a}';
$string['daterange_filter'] = 'Período de consulta: ';
$string['daterange_all'] = 'Más de 6 meses';
$string['daterange_6'] = 'Últimos 6 meses';
$string['daterange_3'] = 'Últimos 3 meses';
$string['daterange_1'] = 'Último mes';
$string['topoffers_title'] = 'Listado de cursos más solicitados';
$string['position'] = 'Posición';
$string['totalenrol'] = '# de matrículas';
$string['offer_explanation_top'] = 'A continuación encontrarás la oferta de cursos que tenemos disponibles para ti, ordenados por cantidad de matrículas. Si lo prefieres puedes filtrar la oferta por las más solicitadas en un período determinado.';
$string['filtertop'] = 'Filtrar';


$string['costenabled'] = 'Cost enabled';
$string['intensitynot'] = 'Abierto';
