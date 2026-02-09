<?php
require_once __DIR__ . '/../configuracion.inc.php';

class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    private $env;

    public function __construct() {
        // Leer configuración desde configuracion.inc.php
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_user = SMTP_USER;
        $this->smtp_pass = SMTP_PASS;
        $this->from_email = SMTP_FROM;
        $this->from_name = SMTP_FROM_NAME;
    }

    /**
     * Enviar correo de confirmación de reserva
     * @param string $destinatario Email del usuario
     * @param string $nombre Nombre del usuario
     * @param array $reserva Datos de la reserva
     * @param string $instalacion Nombre de la instalación
     * @param array $pago Datos del pago (opcional)
     */
    public function enviarConfirmacionReserva($destinatario, $nombre, $reserva, $instalacion, $pago = null) {
        $fecha_formato = $this->formatearFecha($reserva['fecha']);
        $fecha_reserva = new DateTime($reserva['fecha']);
        $numero_referencia = $pago['referencia'] ?? 'Sin registrar';
        $importe = isset($pago['importe']) ? number_format($pago['importe'], 2, ',', '.') : 'N/A';

        $asunto = "Confirmación de Reserva - TodoReservas";
        
        $htmlBody = $this->generarHtmlReserva(
            $nombre, 
            $instalacion, 
            $fecha_formato,
            $reserva['hora_inicio'],
            $numero_referencia,
            $importe
        );

        return $this->enviarCorreo($destinatario, $asunto, $htmlBody, $nombre);
    }

    /**
     * Generar HTML del correo de confirmación
     */
    private function generarHtmlReserva($nombre, $instalacion, $fecha, $hora, $referencia, $importe) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    border-bottom: 3px solid #007bff;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #007bff;
                    margin: 0;
                    font-size: 28px;
                }
                .header p {
                    color: #666;
                    font-size: 14px;
                    margin: 5px 0 0 0;
                }
                .content {
                    margin: 20px 0;
                }
                .content p {
                    margin: 10px 0;
                }
                .info-section {
                    background-color: #f8f9fa;
                    border-left: 4px solid #28a745;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .info-section h3 {
                    color: #28a745;
                    margin-top: 0;
                }
                .info-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #e0e0e0;
                }
                .info-item:last-child {
                    border-bottom: none;
                }
                .info-label {
                    font-weight: 600;
                    color: #555;
                }
                .info-value {
                    color: #333;
                    text-align: right;
                }
                .payment-info {
                    background-color: #e7f3ff;
                    border-left: 4px solid #0066cc;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .payment-info h3 {
                    color: #0066cc;
                    margin-top: 0;
                }
                .footer {
                    text-align: center;
                    border-top: 1px solid #eee;
                    padding-top: 20px;
                    margin-top: 30px;
                    color: #888;
                    font-size: 12px;
                }
                .btn {
                    display: inline-block;
                    background-color: #007bff;
                    color: white;
                    padding: 10px 20px;
                    text-decoration: none;
                    border-radius: 4px;
                    margin-top: 20px;
                }
                .highlight {
                    color: #28a745;
                    font-weight: bold;
                    font-size: 18px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎾TodoReservas⚽</h1>
                    <p>Confirmación de tu reserva</p>
                </div>

                <div class='content'>
                    <p>¡Hola <strong>$nombre</strong>!</p>
                    <p>Tu reserva ha sido <span class='highlight'>confirmada</span> correctamente. Aquí están los detalles:</p>

                    <div class='info-section'>
                        <h3>📋 Detalles de la Reserva</h3>
                        <div class='info-item'>
                            <span class='info-label'>Instalación:</span>
                            <span class='info-value'><strong>$instalacion</strong></span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Fecha:</span>
                            <span class='info-value'><strong>$fecha</strong></span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Hora:</span>
                            <span class='info-value'><strong>$hora</strong></span>
                        </div>
                    </div>

                    <div class='payment-info'>
                        <h3>💳 Información de Pago</h3>
                        <div class='info-item'>
                            <span class='info-label'>Referencia de Transacción:</span>
                            <span class='info-value'><strong>$referencia</strong></span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Importe:</span>
                            <span class='info-value'><strong>€$importe</strong></span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Estado:</span>
                            <span class='info-value'><strong style='color: #28a745;'>✓ Completado</strong></span>
                        </div>
                    </div>

                    <p>Si necesitas modificar o cancelar tu reserva, puedes hacerlo desde tu panel de usuario en TodoReservas.</p>
                </div>

                <div class='footer'>
                    <p>© 2026 TodoReservas. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no respondas directamente.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Enviar correo electrónico mediante Gmail SMTP
     */
    private function enviarCorreo($destinatario, $asunto, $htmlBody, $nombre = '') {
        return $this->enviarPorSMTPGmail($destinatario, $asunto, $htmlBody);
    }

    /**
     * Conectar a Gmail SMTP con STARTTLS
     */
    private function enviarPorSMTPGmail($destinatario, $asunto, $htmlBody) {
        try {
            // Conectar a Gmail
            $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 10);
            
            if (!$socket) {
                return [
                    'status' => 'error',
                    'message' => "Error conectando a Gmail: $errstr ($errno)",
                    'destinatario' => $destinatario
                ];
            }

            // Leer respuesta inicial
            $this->leerLinea($socket);

            // EHLO
            $this->enviarComando($socket, "EHLO localhost\r\n");
            $this->leerRespuesta($socket);

            // STARTTLS para conexión segura
            $this->enviarComando($socket, "STARTTLS\r\n");
            $respuesta = $this->leerLinea($socket);
            
            if (!$this->esRespuestaOk($respuesta)) {
                fclose($socket);
                return [
                    'status' => 'error',
                    'message' => "Gmail rechazó STARTTLS: $respuesta",
                    'destinatario' => $destinatario
                ];
            }

            // Iniciar encriptación TLS
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // EHLO de nuevo después de TLS
            $this->enviarComando($socket, "EHLO localhost\r\n");
            $this->leerRespuesta($socket);

            // AUTH LOGIN
            $this->enviarComando($socket, "AUTH LOGIN\r\n");
            $this->leerLinea($socket);

            // Usuario en base64
            $usuario_b64 = base64_encode($this->smtp_user);
            $this->enviarComando($socket, $usuario_b64 . "\r\n");
            $this->leerLinea($socket);

            // Contraseña en base64
            $pass_b64 = base64_encode($this->smtp_pass);
            $this->enviarComando($socket, $pass_b64 . "\r\n");
            $respuesta = $this->leerLinea($socket);
            
            if (!$this->esRespuestaOk($respuesta)) {
                fclose($socket);
                return [
                    'status' => 'error',
                    'message' => "Gmail rechazó autenticación. Verifica usuario y contraseña",
                    'destinatario' => $destinatario
                ];
            }

            // MAIL FROM
            $this->enviarComando($socket, "MAIL FROM:<{$this->from_email}>\r\n");
            $this->leerLinea($socket);

            // RCPT TO
            $this->enviarComando($socket, "RCPT TO:<{$destinatario}>\r\n");
            $this->leerLinea($socket);

            // DATA
            $this->enviarComando($socket, "DATA\r\n");
            $this->leerLinea($socket);

            // Construir el correo completo
            $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "To: {$destinatario}\r\n";
            $headers .= "Subject: {$asunto}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: TodoReservas App\r\n";
            $headers .= "\r\n";

            $mensaje = $headers . $htmlBody;

            // Enviar el mensaje
            fwrite($socket, $mensaje . "\r\n.\r\n");
            $this->leerLinea($socket);

            // QUIT
            $this->enviarComando($socket, "QUIT\r\n");
            fclose($socket);

            return [
                'status' => 'success',
                'message' => 'Correo enviado por Gmail correctamente',
                'destinatario' => $destinatario
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'destinatario' => $destinatario
            ];
        }
    }

    /**
     * Conectar directamente a MailHog por SMTP (sin Sendmail)
     */
    /**
     * Enviar comando SMTP
     */
    private function enviarComando($socket, $comando) {
        fwrite($socket, $comando);
    }

    /**
     * Leer una línea de respuesta SMTP
     */
    private function leerLinea($socket) {
        return fgets($socket, 512);
    }

    /**
     * Leer respuesta SMTP completa (puede tener múltiples líneas)
     */
    private function leerRespuesta($socket) {
        $respuesta = '';
        do {
            $linea = fgets($socket, 512);
            $respuesta .= $linea;
        } while ($linea && $linea[3] === '-');
        return $respuesta;
    }

    /**
     * Verificar si respuesta SMTP es exitosa (2xx o 3xx)
     */
    private function esRespuestaOk($respuesta) {
        $codigo = substr(trim($respuesta), 0, 1);
        return in_array($codigo, ['2', '3']);
    }

    /**
     * Enviar por SMTP real (producción)
     */
    /**
     * Guardar correo en archivo de log (alternativa si falla SMTP)
     */
    /**
     * Formatear fecha para mostrar en correo
     */
    private function formatearFecha($fecha) {
        $fecha_obj = new DateTime($fecha);
        $locale = 'es_ES.UTF-8';
        setlocale(LC_TIME, $locale);
        
        // Nombres en español
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                  'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        
        $dia_nombre = $dias[$fecha_obj->format('w')];
        $dia = $fecha_obj->format('d');
        $mes_nombre = $meses[$fecha_obj->format('n')];
        $año = $fecha_obj->format('Y');
        
        return ucfirst("$dia_nombre, $dia de $mes_nombre de $año");
    }
}
