<?php
// Init
error_reporting(NULL);
ob_start();
session_start();
$TAB = 'WEB';
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);
$v_ftp_email = $panel[$user]['CONTACT'];
    if (!empty($_POST['ok'])) {
        // Check input
        if (empty($_POST['v_domain'])) $errors[] = __('domain');
        if (empty($_POST['v_ip'])) $errors[] = __('ip');
        if ((!empty($_POST['v_ssl'])) && (empty($_POST['v_ssl_crt']))) $errors[] = __('ssl certificate');
        if ((!empty($_POST['v_ssl'])) && (empty($_POST['v_ssl_key']))) $errors[] = __('ssl key');
        if ((!empty($_POST['v_aliases'])) && ($_POST['v_aliases'] != 'www.'.$_POST['v_domain'])) $v_adv = 'yes';
        if ((!empty($_POST['v_ssl'])) || (!empty($_POST['v_elog']))) $v_adv = 'yes';
        if ((!empty($_POST['v_ssl_crt'])) || (!empty($_POST['v_ssl_key']))) $v_adv = 'yes';
        if ((!empty($_POST['v_ssl_ca'])) || ($_POST['v_stats'] != 'none')) $v_adv = 'yes';
        if (empty($_POST['v_proxy'])) $v_adv = 'yes';
        if (!empty($_POST['v_ftp'])) $v_adv = 'yes';

        // Protect input
        $v_domain = preg_replace("/^www\./i", "", $_POST['v_domain']);
        $v_domain = escapeshellarg($v_domain);
        $v_domain = strtolower($v_domain);
        $v_ip = escapeshellarg($_POST['v_ip']);
        if (empty($_POST['v_dns'])) $v_dns = 'off';
        if (empty($_POST['v_mail'])) $v_mail = 'off';
        $v_aliases = $_POST['v_aliases'];
        $v_ssl = $_POST['v_ssl'];
        $v_ssl_crt = $_POST['v_ssl_crt'];
        $v_ssl_key = $_POST['v_ssl_key'];
        $v_ssl_ca = $_POST['v_ssl_ca'];
        $v_ssl_home = $data[$v_domain]['SSL_HOME'];

        //Not implemented yet
        //$v_stats = escapeshellarg($_POST['v_stats']);
        //$v_stats_user = $data[$v_domain]['STATS_USER'];
        //$v_stats_password = $data[$v_domain]['STATS_PASSWORD'];
        $v_target_ip = escapeshellarg($_POST['v_target_ip']);
        
        $v_target_port = intval($_POST['v_target_port']);

        // Check for errors
        if (!empty($errors[0])) {
            foreach ($errors as $i => $error) {
                if ( $i == 0 ) {
                    $error_msg = $error;
                } else {
                    $error_msg = $error_msg.", ".$error;
                }
            }
            $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
        }

        if (empty($_SESSION['error_msg'])) {
            // Add WEB
            echo "Running command: v-add-app-proxy v-add-app-proxy {$user} {$v_domain} {$v_ip} {$v_target_ip} {$v_target_port} 'no'";
            exec (VESTA_CMD."v-add-app-proxy {$user} {$v_domain} {$v_ip} {$v_target_ip} {$v_target_port} 'no'", $output, $return_var);
            check_return_code($return_var,$output);
            echo "<br>Return value: {$return_var}";
            unset($output);

            // Add DNS
            if (($_POST['v_dns'] == 'on') && (empty($_SESSION['error_msg']))) {
                exec (VESTA_CMD."v-add-dns-domain ".$user." ".$v_domain." ".$v_ip, $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);
            }

            // Add Mail
            if (($_POST['v_mail'] == 'on') && (empty($_SESSION['error_msg']))) {
                exec (VESTA_CMD."v-add-mail-domain ".$user." ".$v_domain, $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);
            }

            // Add Aliases
            if ((!empty($_POST['v_aliases'])) && (empty($_SESSION['error_msg']))) {
                $valiases = preg_replace("/\n/", " ", $_POST['v_aliases']);
                $valiases = preg_replace("/,/", " ", $valiases);
                $valiases = preg_replace('/\s+/', ' ',$valiases);
                $valiases = trim($valiases);
                $aliases = explode(" ", $valiases);
                foreach ($aliases as $alias) {
                    if ($alias == 'www.'.$_POST['v_domain']) {
                        $www_alias = 'yes';
                    } else {
                        $alias = escapeshellarg($alias);
                        if (empty($_SESSION['error_msg'])) {
                            exec (VESTA_CMD."v-add-app-proxy-alias ".$user." ".$v_domain." ".$alias." 'no'", $output, $return_var);
                            check_return_code($return_var,$output);
                        }
                        unset($output);
                        if (($_POST['v_dns'] == 'on') && (empty($_SESSION['error_msg']))) {
                            exec (VESTA_CMD."v-add-dns-on-web-alias ".$user." ".$v_domain." ".$alias." 'no'", $output, $return_var);
                            check_return_code($return_var,$output);
                            unset($output);
                        }
                    }
                }
            }
            //NOT Implemented v-delete-app-proxy-alias
            /*if ((empty($www_alias)) && (empty($_SESSION['error_msg']))) {
                $alias =  preg_replace("/^www./i", "", $_POST['v_domain']);
                $alias = 'www.'.$alias;
                $alias = escapeshellarg($alias);
                exec (VESTA_CMD."v-delete-web-domain-alias ".$user." ".$v_domain." ".$alias." 'no'", $output, $return_var);
                check_return_code($return_var,$output);
            }*/

            // Add SSL
            if (!empty($_POST['v_ssl'])) {
                exec ('mktemp -d', $output, $return_var);
                $tmpdir = $output[0];

                // Certificate
                if (!empty($_POST['v_ssl_crt'])) {
                    $fp = fopen($tmpdir."/".$_POST['v_domain'].".crt", 'w');
                    fwrite($fp, str_replace("\r\n", "\n", $_POST['v_ssl_crt']));
                    fwrite($fp, "\n");
                    fclose($fp);
                }

                // Key
                if (!empty($_POST['v_ssl_key'])) {
                    $fp = fopen($tmpdir."/".$_POST['v_domain'].".key", 'w');
                    fwrite($fp, str_replace("\r\n", "\n", $_POST['v_ssl_key']));
                    fwrite($fp, "\n");
                    fclose($fp);
                }

                // CA
                if (!empty($_POST['v_ssl_ca'])) {
                    $fp = fopen($tmpdir."/".$_POST['v_domain'].".ca", 'w');
                    fwrite($fp, str_replace("\r\n", "\n", $_POST['v_ssl_ca']));
                    fwrite($fp, "\n");
                    fclose($fp);
                }

                $v_ssl_home = escapeshellarg($_POST['v_ssl_home']);
                exec (VESTA_CMD."v-add-web-domain-ssl ".$user." ".$v_domain." ".$tmpdir." ".$v_ssl_home." 'no'", $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);
            }

            // Add WebStats
            /*
            if ((!empty($_POST['v_stats'])) && ($_POST['v_stats'] != 'none' ) && (empty($_SESSION['error_msg']))) {
                $v_stats = escapeshellarg($_POST['v_stats']);
                exec (VESTA_CMD."v-add-web-domain-stats ".$user." ".$v_domain." ".$v_stats, $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);

                if ((!empty($_POST['v_stats_user'])) && (empty($_SESSION['error_msg']))) {
                    $v_stats_user = escapeshellarg($_POST['v_stats_user']);
                    $v_stats_password = escapeshellarg($_POST['v_stats_password']);
                    exec (VESTA_CMD."v-add-web-domain-stats-user ".$user." ".$v_domain." ".$v_stats_user." ".$v_stats_password, $output, $return_var);
                    check_return_code($return_var,$output);
                    unset($v_stats_user);
                    unset($v_stats_password);
                    unset($output);
                }
            }*/

            if (($_POST['v_dns'] == 'on') && (empty($_SESSION['error_msg']))) {
                exec (VESTA_CMD."v-restart-dns", $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);
            }

            if (empty($_SESSION['error_msg'])) {
                exec (VESTA_CMD."v-restart-proxy", $output, $return_var);
                check_return_code($return_var,$output);
                unset($output);
            }

            if (empty($_SESSION['error_msg'])) {
                unset($output);
                $_SESSION['ok_msg'] = __('WEB_APP_PROXY_CREATED_OK',$_POST[v_domain],$_POST[v_domain]);
                unset($v_domain);
                unset($v_aliases);
                unset($v_target_ip);
                unset($v_target_port);
                unset($v_ssl);
                unset($v_ssl_crt);
                unset($v_ssl_key);
                unset($v_ssl_ca);
            }
        }
    }

    exec (VESTA_CMD."v-list-user-ips ".$user." json", $output, $return_var);
    $ips = json_decode(implode('', $output), true);
    unset($output);

    exec (VESTA_CMD."v-list-web-stats json", $output, $return_var);
    $stats = json_decode(implode('', $output), true);
    unset($output);

    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_app.html');
    unset($_SESSION['error_msg']);
    unset($_SESSION['ok_msg']);
//}

var_dump( $_POST);
foreach ($_POST as $key => $value) {
    echo "<br>key:".$key."  value:".$value;
}

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
