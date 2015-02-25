<?php
/* vim:set encoding=utf8 fileencoding=utf8 */
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Blackboard IM, All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard IM.                       *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Blackboard IM Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Fr\u017Dd\u017Dric Mathiot                                                   *
 *                                                                            *
 * Date: 16th November 2007                                                   *
 *                                                                            *
 ******************************************************************************/

//Blackboard IM Pronto Module
$string['modulename'] = 'Blackboard IM';
$string['pluginname'] = 'Blackboard IM';
$string['modulenameplural'] = 'Blackboard IM';

//Configuration page
$string['prontosetup'] = 'Configuración de '.$string['modulename'];
$string['urlconfig'] = 'URL del servidor';
$string['accountconfig'] = 'Nombre de cuenta:';
$string['secretconfig'] = 'Pregunta secreta de la cuenta:';
$string['ntp'] = 'Uso de PTR:';
$string['ntphelp'] = '(Este recuadro debe permanecer activado, a menos que el servidor no sincronice con el Protocolo de tiempo de la red)';

$string['troubleshooting'] = 'Solución de problemas';
$string['integrationversion'] = 'Versión de integración:';
$string['loglevel'] = 'Establecer nivel de registro:';
$string['viewlogs'] = 'Ver registros';

$string['save'] = 'Guardar cambios';

//Field validation error messages
$string['urlandretry'] = 'Escriba el nombre del servidor suministrado por Blackboard, normalmente https://www.blackboardim.com/ y vuelva a intentarlo.';
$string['empty_url_error'] = 'El campo URL del servidor '.$string['modulename'].' está vacío. '. $string['urlandretry'];
$string['unvalid_url_error'] = 'La URL del servidor '.$string['modulename'].' es incorrecta. '. $string['urlandretry'];
$string['requiredvalue'] = '${field} está vacío.';
$string['invalidlettersnumbers'] = 'Esta campo no puede estar vacío y sólo puede contener letras, números, guiones bajos y guiones.';
$string['no_http_error'] = '';

$string['accountandretry'] = 'Escriba el nombre de cuenta suministrado por Blackboard y vuelva a intentarlo.';
$string['empty_account_error'] = 'El campo Nombre de cuenta está vacío.'.$string['accountandretry'];
$string['unvalid_account_error'] = 'El campo Nombre de cuenta es incorrecto. '.$string['accountandretry'];
$string['secretandretry'] = 'Escriba la pregunta secreta proporcionada por Blackboard y vuelva a intentarlo.';
$string['empty_secret_error'] = 'El campo Pregunta secreta está vacío. '.$string['secretandretry'];
$string['unvalid_secret_error'] = 'El campo Pregunta secreta es incorrecto.'.$string['secretandretry'];


//Logs page
$string['serverlogs'] = 'Registros del servidor';
$string['logsdir'] = 'Directorio de archivos de registro en disco: ';
$string['loglinks'] = 'Haga clic en los siguientes vínculos para descargar los archivos de registro.';
$string['logback'] = 'Volver a la configuración de '.$string['modulename'].'';
$string['no_logs'] = 'No hay archivos de registro aún.';

//Validation page

$string['edit'] = 'Editar';
$string['continue'] = 'Continuar';

$string['connectiontest'] = 'Prueba de conexión';
$string['editinstruction'] = 'Si la prueba ha fallado, haga clic en el botón <b>'.$string['edit'].'</b> para comprobar la configuración.';
$string['continueinstruction'] = 'Haga clic en el botón <b>'.$string['continue'].'</b> para volver a la página Actividades.';
$string['pronto_activity_title'] = 'Acceda a '.$string['modulename'].'!';
$string['empty_activity_title_error'] = 'Indique un nombre para esta actividad';

$string['admin_restriction_message']='Debe ser administrador para tener acceso';
