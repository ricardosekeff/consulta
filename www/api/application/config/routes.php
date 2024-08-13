<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
| example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
| https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
| $route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
| $route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
| $route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples: my-controller/index -> my_controller/index
|   my-controller/my-method -> my_controller/my_method
*/
$route['default_controller'] = 'cidade/cidade_get';
$route['404_override'] = '';
$route['translate_uri_dashes'] = TRUE;

/*
| -------------------------------------------------------------------------
| Sample REST API Routes
| -------------------------------------------------------------------------
*/
// $route['cidade'] = 'welcome';
$route['agendamento/count'] = 'agendamento/count';
$route['agendamento/(:num)/procedimento/(:num)'] = 'agendamento/procedimento/$1/$2';
$route['agendamento/(:num)/movimentos'] = 'movimento/$1';
$route['agendamento/(:num)'] = 'agendamento/$1';
$route['agendamento/(:num)/(:any)'] = 'agendamento/$1/$2';
$route['cargo/(:num)'] = 'cargo/$1';
// $route['cargo/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'cargo/id/$1/format/$3$4';
$route['cliente/(:num)'] = 'cliente/$1';
$route['cliente/(:num)/agendamento'] = 'agendamento/cliente/$1';
$route['cliente/(:num)/(:any)'] = 'cliente/$1/$2';
// $route['cliente/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'cliente/id/$1/format/$3$4';
$route['cidade/(:num)'] = 'cidade/$1';
$route['financeiro/(:num)'] = 'financeiro/$1';
$route['financeiro/(:num)/(:any)'] = 'financeiro/$1/$2';
// $route['cidade/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'cidade/id/$1/format/$3$4';
$route['pessoa-fisica/(:any)'] = 'pessoafisica/$1';
$route['pessoa-fisica/check/(:any)'] = 'pessoafisica/check/$1';
$route['pessoa-juridica/(:any)'] = 'pessoajuridica/$1';
$route['pessoa-juridica/check/(:any)'] = 'pessoajuridica/check/$1';
$route['procedimento/(:num)'] = 'procedimento/$1';
// $route['procedimento/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'procedimento/id/$1/format/$3$4';
$route['parceiro/(:num)'] = 'parceiro/$1';

$route['parceiro/procedimento/distinct'] = 'parceiroprocedimento/distinct';
// $route['parceiro/procedimento/'] = 'parceiroprocedimento//';
$route['parceiro/procedimento/(:num)'] = 'parceiroprocedimento/procedimento/$1';
$route['parceiro/(:num)/procedimento'] = 'parceiroprocedimento/$1';
$route['parceiro/(:num)/procedimento/(:num)'] = 'parceiroprocedimento/$1/$2';

$route['parceiro/(:num)/agendamento'] = 'agendamento/parceiro/$1';
$route['parceiro/(:num)/agendamento/(:num)'] = 'agendamento/parceiro/$1/$2';
$route['parceiro/(:num)/agendamento/(:num)/(:any)'] = 'agendamento/parceiro/$1/$2/$3';
$route['parceiro/(:num)/(:any)'] = 'parceiro/$1/$2';
// $route['parceiro/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'parceiro/id/$1/format/$3$4';
$route['parceiroprocedimento/(:num)'] = 'parceiroprocedimento/$1';

$route['representante/(:num)/agendamento'] = 'agendamento/representante/$1';
$route['representante/(:num)/pagamento'] = 'representante/pagamento/$1';
$route['representante/(:num)/pagamento/(:num)'] = 'representante/pagamento/$1/$2';

$route['usuario/(:num)'] = 'usuario/$1';
$route['usuario/(:num)/(:any)'] = 'usuario/$1/$2';
// $route['parceiroprocedimento/(:num)(\.)([a-zA-Z0-9_-]+)(.*)'] = 'parceiroprocedimento/id/$1/format/$3$4';
$route['login'] = 'usuario/login';
$route['logout'] = 'usuario/logout';

// Relatórios
$route['relatorio/agendamento'] = 'relatorio/agendamento';
$route['relatorio/agendamento/(:any)/(:any)'] = 'relatorio/agendamento/$1/$2';
$route['relatorio/financeiro'] = 'relatorio/financeiro';
$route['relatorio/financeiro/(:any)/(:any)'] = 'relatorio/financeiro/$1/$2';
$route['relatorio/parceiro'] = 'relatorio/parceiro';
$route['relatorio/parceiro/(:any)/(:any)'] = 'relatorio/parceiro/$1/$2';
$route['relatorio/procedimento'] = 'relatorio/procedimento';
$route['relatorio/procedimento/(:any)/(:any)'] = 'relatorio/procedimento/$1/$2';