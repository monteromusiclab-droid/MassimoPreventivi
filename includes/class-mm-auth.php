<?php
/**
 * Gestione Autenticazione
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Auth {

    /**
     * Verifica se l'utente è loggato
     */
    public static function is_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Verifica se l'utente è admin
     */
    public static function is_admin() {
        return current_user_can('manage_options');
    }

    /**
     * Ottieni URL di login
     */
    public static function get_login_url() {
        return wp_login_url(get_permalink());
    }

    /**
     * Ottieni URL di logout
     */
    public static function get_logout_url() {
        return wp_logout_url(get_permalink());
    }

    /**
     * Mostra form di login
     */
    public static function show_login_form() {
        ob_start();
        ?>
        <div class="mm-login-container">
            <div class="mm-login-box">
                <div class="mm-login-header">
                    <h2>🔐 Accesso Richiesto</h2>
                    <p>Effettua il login per accedere al sistema di gestione preventivi</p>
                </div>

                <div class="mm-login-body">
                    <?php
                    wp_login_form(array(
                        'echo' => true,
                        'redirect' => get_permalink(),
                        'form_id' => 'mm-loginform',
                        'label_username' => __('Nome utente'),
                        'label_password' => __('Password'),
                        'label_remember' => __('Ricordami'),
                        'label_log_in' => __('Accedi'),
                        'remember' => true
                    ));
                    ?>
                </div>

                <div class="mm-login-footer">
                    <p><a href="<?php echo wp_lostpassword_url(get_permalink()); ?>">Password dimenticata?</a></p>
                </div>
            </div>
        </div>

        <style>
            .mm-login-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 60vh;
                padding: 40px 20px;
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            }

            .mm-login-box {
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                max-width: 450px;
                width: 100%;
                overflow: hidden;
            }

            .mm-login-header {
                background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%);
                color: white;
                padding: 35px 30px;
                text-align: center;
            }

            .mm-login-header h2 {
                margin: 0 0 10px 0;
                font-size: 26px;
                font-weight: 700;
            }

            .mm-login-header p {
                margin: 0;
                font-size: 14px;
                opacity: 0.95;
            }

            .mm-login-body {
                padding: 35px 30px;
            }

            .mm-login-body form {
                margin: 0;
            }

            .mm-login-body label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }

            .mm-login-body input[type="text"],
            .mm-login-body input[type="password"] {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 15px;
                margin-bottom: 20px;
                transition: border-color 0.3s;
            }

            .mm-login-body input[type="text"]:focus,
            .mm-login-body input[type="password"]:focus {
                outline: none;
                border-color: #e91e63;
                box-shadow: 0 0 0 3px rgba(233, 30, 99, 0.1);
            }

            .mm-login-body .login-remember {
                margin-bottom: 20px;
            }

            .mm-login-body .login-remember label {
                display: inline-flex;
                align-items: center;
                font-weight: 400;
                cursor: pointer;
                gap: 8px;
            }

            .mm-login-body .login-remember input[type="checkbox"] {
                width: auto;
                margin: 0;
                cursor: pointer;
            }

            .mm-login-body input[type="submit"] {
                width: 100%;
                padding: 14px 20px;
                background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(233, 30, 99, 0.3);
            }

            .mm-login-body input[type="submit"]:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(233, 30, 99, 0.4);
            }

            .mm-login-footer {
                padding: 20px 30px;
                background: #f8f9fa;
                border-top: 1px solid #e0e0e0;
                text-align: center;
            }

            .mm-login-footer p {
                margin: 0;
                font-size: 14px;
            }

            .mm-login-footer a {
                color: #e91e63;
                text-decoration: none;
                font-weight: 600;
                transition: color 0.3s;
            }

            .mm-login-footer a:hover {
                color: #c2185b;
                text-decoration: underline;
            }

            /* WordPress login form adjustments */
            #mm-loginform p {
                margin-bottom: 0;
            }

            @media (max-width: 768px) {
                .mm-login-container {
                    padding: 20px 15px;
                }

                .mm-login-box {
                    max-width: 100%;
                }

                .mm-login-header,
                .mm-login-body,
                .mm-login-footer {
                    padding: 25px 20px;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }
}
