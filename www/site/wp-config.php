<?php
/**
 * As configurações básicas do WordPress
 *
 * O script de criação wp-config.php usa esse arquivo durante a instalação.
 * Você não precisa usar o site, você pode copiar este arquivo
 * para "wp-config.php" e preencher os valores.
 *
 * Este arquivo contém as seguintes configurações:
 *
 * * Configurações do MySQL
 * * Chaves secretas
 * * Prefixo do banco de dados
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/pt-br:Editando_wp-config.php
 *
 * @package WordPress
 */

// ** Configurações do MySQL - Você pode pegar estas informações com o serviço de hospedagem ** //
/** O nome do banco de dados do WordPress */
define('DB_NAME', 'sitecfacil');

/** Usuário do banco de dados MySQL */
define('DB_USER', 'sitecfacil');

/** Senha do banco de dados MySQL */
define('DB_PASSWORD', 'W=eFMT!bw5Qb:-');

/** Nome do host do MySQL */
define('DB_HOST', 'sitecfacil.mysql.dbaas.com.br');

/** Charset do banco de dados a ser usado na criação das tabelas. */
define('DB_CHARSET', 'utf8mb4');

/** O tipo de Collate do banco de dados. Não altere isso se tiver dúvidas. */
define('DB_COLLATE', '');

/**#@+
 * Chaves únicas de autenticação e salts.
 *
 * Altere cada chave para um frase única!
 * Você pode gerá-las
 * usando o {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org
 * secret-key service}
 * Você pode alterá-las a qualquer momento para invalidar quaisquer
 * cookies existentes. Isto irá forçar todos os
 * usuários a fazerem login novamente.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'u!h<.SY7syK(~n^;0%qP}=p$HHFN}9Qn1Q[)Uj4qWk=]v@k|9Th[5ImI3#vkpm#^');
define('SECURE_AUTH_KEY',  '8[2pDFH]R|Bv|}:sW!e/~hDrj&2_TBkdm1bgfeFYR^[5-,;lCRFG%R2 wp|NT^N/');
define('LOGGED_IN_KEY',    'J.;E[C0Vb,X<Pc!VCzHt]Fg&o;i;H~U57`+2F.T<Ihd8HP8/![}3EaXP%iF+8/8x');
define('NONCE_KEY',        '?!+;drzHeFXbHw$rI&?rZc[Q3J`!XXq=vwhx=N<Sih:55ooS} |TEC=5Sv5n/ym7');
define('AUTH_SALT',        '5jwc9~RPAQ]fHN]raDi2dz,*.A%%T)^7rm)%!eeQ9)@km7uX7F3g4o*/LUgB<j:Y');
define('SECURE_AUTH_SALT', '|j@%nOF8ba~j^zjKvV?^,l(WRr:7U0K RBBjfrVYr,c|-br`BN={0fwv}eam_GR;');
define('LOGGED_IN_SALT',   '03gGw~2qiahz#Y|b3cGM`tunAg2w17x.,m3 )}vFeB|=~.GO@r2<5ls-o.bF>p4S');
define('NONCE_SALT',       '_`RD|7t[D^21,5~)El4p^OMGlpQZn9M([/x6Dcrh>a2w~_klSx$!7RM+l9~c@ZAS');

/**#@-*/

/**
 * Prefixo da tabela do banco de dados do WordPress.
 *
 * Você pode ter várias instalações em um único banco de dados se você der
 * um prefixo único para cada um. Somente números, letras e sublinhados!
 */
$table_prefix  = 'wpcf_';

/**
 * Para desenvolvedores: Modo de debug do WordPress.
 *
 * Altere isto para true para ativar a exibição de avisos
 * durante o desenvolvimento. É altamente recomendável que os
 * desenvolvedores de plugins e temas usem o WP_DEBUG
 * em seus ambientes de desenvolvimento.
 *
 * Para informações sobre outras constantes que podem ser utilizadas
 * para depuração, visite o Codex.
 *
 * @link https://codex.wordpress.org/pt-br:Depura%C3%A7%C3%A3o_no_WordPress
 */
define('WP_DEBUG', false);

/* Isto é tudo, pode parar de editar! :) */

/** Caminho absoluto para o diretório WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Configura as variáveis e arquivos do WordPress. */
require_once(ABSPATH . 'wp-settings.php');
