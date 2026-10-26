<?php
class fatturapa_viewer extends rcube_plugin
{
    public $task = 'mail';

    function init()
    {
        // Carica automaticamente i file di traduzione dalla cartella localization/
        $this->add_texts('localization/', false);

        $this->add_hook('message_objects', array($this, 'add_invoice_button'));
        $this->register_action('plugin.view_fatturapa', array($this, 'render_invoice_page'));
        $this->register_action('plugin.download_fatturapa', array($this, 'download_invoice_file'));
    }

    function add_invoice_button($args)
    {
        $rcmail = rcmail::get_instance();
        $message = $args['message'];

        $has_valid_invoice = false;

        if (!empty($message->attachments)) {
            foreach ($message->attachments as $attach) {
                $filename_lc = strtolower($attach->filename);

                $is_xml = str_ends_with($filename_lc, '.xml');
                $is_p7m = str_ends_with($filename_lc, '.p7m');
                $is_not_daticert = strpos($filename_lc, 'daticert') === false;
                $is_not_postacert = strpos($filename_lc, 'postacert') === false;
                $is_metadata = strpos($filename_lc, '_mt_') !== false;

                $has_sdi_naming = (
                    str_starts_with($filename_lc, 'it') ||
                    strpos($filename_lc, 'fattura') !== false ||
                    strpos($filename_lc, 'ricevuta') !== false ||
                    strpos($filename_lc, 'notifica') !== false ||
                    strpos($filename_lc, 'rc_') !== false ||
                    strpos($filename_lc, 'ns_') !== false ||
                    strpos($filename_lc, 'ec_') !== false
                );

                if (($is_xml or $is_p7m) and $is_not_daticert and $is_not_postacert and !$is_metadata and $has_sdi_naming) {
                    $has_valid_invoice = true;

                    $url_view = $rcmail->url(array(
                        '_task' => 'mail',
                        '_action' => 'plugin.view_fatturapa',
                        '_mbox' => $rcmail->output->get_env('mailbox'),
                        '_uid' => $message->uid,
                        '_part' => $attach->mime_id,
                        '_filename' => $attach->filename
                    ));

                    $url_download = $rcmail->url(array(
                        '_task' => 'mail',
                        '_action' => 'plugin.download_fatturapa',
                        '_mbox' => $rcmail->output->get_env('mailbox'),
                        '_uid' => $message->uid,
                        '_part' => $attach->mime_id,
                        '_filename' => $attach->filename
                    ));

                    $is_scarto = (strpos($filename_lc, 'ns_') !== false || strpos($filename_lc, 'scarto') !== false);
                    $is_impossibilita = (strpos($filename_lc, 'ec_') !== false || strpos($filename_lc, 'impossibilita') !== false);
                    $is_receipt = (strpos($filename_lc, 'ricevuta') !== false || strpos($filename_lc, 'notifica') !== false || strpos($filename_lc, 'rc_') !== false);

                    // Utilizzo di $this->gettext() per le etichette multilingua
                    if ($is_scarto) {
                        $label = $this->gettext('scarto_title');
                        $bg_color = '#d9534f';
                    } elseif ($is_impossibilita) {
                        $label = $this->gettext('impossibilita_title');
                        $bg_color = '#f0ad4e';
                    } elseif ($is_receipt) {
                        $label = $this->gettext('receipt_title');
                        $bg_color = '#5cb85c';
                    } else {
                        $label = $this->gettext('invoice_title');
                        $bg_color = '#0275d8';
                    }

                    $download_tooltip = $this->gettext('download_title');
                    $fullscreen_tooltip = $this->gettext('fullscreen_title');

                    $html_preview = '<div class="fatturapa-embedded-container" style="margin-bottom: 25px; border: 1px solid #ccd0d4; border-radius: 6px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
                    
                    $html_preview .= '<div style="background: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center; border-top-left-radius: 6px; border-top-right-radius: 6px;">';
                    
                    // Sezione Sinistra: Download
                    $html_preview .= '<div style="display: flex; align-items: center;">';
                    $html_preview .= '<a href="' . $url_download . '" title="' . htmlspecialchars($download_tooltip) . '" style="background: ' . $bg_color . '; color: #fff; width: 28px; height: 28px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; flex-shrink: 0;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg></a>';
                    $html_preview .= '</div>';

                    // Sezione Centrale: Blocco unificato (Descrizione Tipo Documento prima, Nome File poi)
                    $html_preview .= '<div style="display: inline-flex; align-items: center; height: 28px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff; overflow: hidden; box-sizing: border-box;">';
                    $html_preview .= '<span style="background: ' . $bg_color . '; color: #fff; padding: 0 12px; font-size: 11px; font-weight: bold; height: 100%; display: inline-flex; align-items: center;">' . $label . '</span>';
                    $html_preview .= '<span style="padding: 0 12px; font-size: 13px; color: #333; height: 100%; display: inline-flex; align-items: center;">' . htmlspecialchars($attach->filename) . '</span>';
                    $html_preview .= '</div>';

                    // Sezione Destra: Pulsante Schermo Intero ad icona
                    $html_preview .= '<div style="display: flex; align-items: center;">';
                    $html_preview .= '<a href="' . $url_view . '" target="_blank" title="' . htmlspecialchars($fullscreen_tooltip) . '" style="background: ' . $bg_color . '; color: #fff; width: 28px; height: 28px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg></a>';
                    $html_preview .= '</div>';
                    
                    $html_preview .= '</div>';

                    $html_preview .= '<div style="padding: 0; background: #fff;">';
                    $html_preview .= '<iframe src="' . $url_view . '" style="width: 100%; height: 600px; border: none; display: block;" sandbox="allow-same-origin allow-scripts allow-popups"></iframe>';
                    $html_preview .= '</div>';
                    
                    $html_preview .= '</div>';

                    // Script JavaScript per nascondere il div .rcmBody e posizionare la nostra anteprima
                    $rcmail->output->add_script('
                        $(document).ready(function(){
                            function hideRcmBodyAndInsert() {
                                $(".rcmBody").hide();
                                
                                var $target = $(".rcmBody").first().parent();
                                if ($target.length && !$target.find(".fatturapa-embedded-container").length) {
                                    $target.prepend(\'' . addslashes($html_preview) . '\');
                                }
                            }

                            hideRcmBodyAndInsert();
                            setTimeout(hideRcmBodyAndInsert, 200);

                            var observer = new MutationObserver(function(mutations) {
                                hideRcmBodyAndInsert();
                            });
                            observer.observe(document.body, { childList: true, subtree: true });
                        });
                    ', 'docready');

                    break;
                }
            }
        }

        if ($has_valid_invoice) {
            $rcmail->output->add_header('<style>
                .rcmBody { display: none !important; }
                #attachmentslist, .attachmentslist, .message-attachments { display: none !important; }
            </style>');
        }

        return $args;
    }

    function download_invoice_file()
    {
        try {
            $rcmail = rcmail::get_instance();
            
            $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GET);
            $part = rcube_utils::get_input_value('_part', rcube_utils::INPUT_GET);
            $filename = rcube_utils::get_input_value('_filename', rcube_utils::INPUT_GET);
            $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_GET);
            
            $rcmail->storage->set_folder($mbox);
            $file_content = $rcmail->storage->get_message_part($uid, $part);

            if (empty($file_content)) {
                header('Content-Type: text/html; charset=utf-8');
                echo "<h3>" . htmlspecialchars($this->gettext('error_attachment')) . "</h3>" . htmlspecialchars($this->gettext('error_imap_read'));
                exit;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($file_content));
            echo $file_content;
            exit;

        } catch (Exception $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<h3>" . htmlspecialchars($this->gettext('error_download')) . "</h3>" . htmlspecialchars($e->getMessage());
            exit;
        }
    }

    function render_invoice_page()
    {
        try {
            $rcmail = rcmail::get_instance();
            
            $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_GET);
            $part = rcube_utils::get_input_value('_part', rcube_utils::INPUT_GET);
            $filename = rcube_utils::get_input_value('_filename', rcube_utils::INPUT_GET);
            $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_GET);
            
            $rcmail->storage->set_folder($mbox);
            $file_content = $rcmail->storage->get_message_part($uid, $part);

            if (empty($file_content)) {
                header('Content-Type: text/html; charset=utf-8');
                echo "<h3>" . htmlspecialchars($this->gettext('error_attachment')) . "</h3>" . htmlspecialchars($this->gettext('error_imap_read'));
                exit;
            }

            $filename_lc = strtolower($filename);
            $xml_content = '';

            if (strpos($filename_lc, '.p7m') !== false) {
                if (strpos($file_content, 'Content-Type:') !== false) {
                    $parts = explode("\r\n\r\n", $file_content, 2);
                    $file_content = isset($parts[1]) ? $parts[1] : $file_content;
                }

                $dirTemp = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
                $xml_content = $this->stripP7MData($file_content, $dirTemp);

                if (empty($xml_content)) {
                    $raw_bin = $file_content;
                    if (base64_decode($raw_bin, true)) {
                        $raw_bin = base64_decode($raw_bin);
                    }
                    
                    $pos = stripos($raw_bin, '<?xml');
                    if ($pos === false) { $pos = stripos($raw_bin, '<p:FatturaElettronica'); }
                    if ($pos === false) { $pos = stripos($raw_bin, '<FatturaElettronica'); }
                    if ($pos !== false) {
                        $xml_content = substr($raw_bin, $pos);
                    }
                }
            } else {
                $xml_content = $file_content;
            }

            $xml_content = preg_replace('/^[\xEF\xBB\xBF\x1A\x20\r\n\t]+/', '', $xml_content);
            $xml_content = preg_replace('/<\?.*?\?>/ms', '', $xml_content);

            $end_tags = array('</p:FatturaElettronica>', '</FatturaElettronica>', '</RicevutaConsegna>', '</RicevutaScarto>', '</NotificaScarto>', '</NotificaEsito>', '</RicevutaImpossibilitaRecapito>');
            foreach ($end_tags as $end_tag) {
                $end_pos = stripos($xml_content, $end_tag);
                if ($end_pos !== false) {
                    $xml_content = substr($xml_content, 0, $end_pos + strlen($end_tag));
                    break;
                }
            }

            $is_scarto_file = (strpos($xml_content, 'RicevutaScarto') !== false || strpos($xml_content, 'NotificaScarto') !== false || strpos($filename_lc, 'scarto') !== false || strpos($filename_lc, 'ns_') !== false);
            $is_impossibilita_file = (strpos($xml_content, 'RicevutaImpossibilitaRecapito') !== false || strpos($filename_lc, 'ec_') !== false || strpos($filename_lc, 'impossibilita') !== false);
            $is_consegna_file = (strpos($xml_content, 'RicevutaConsegna') !== false || strpos($filename_lc, 'ricevuta') !== false || strpos($filename_lc, 'rc_') !== false);

            $render_header_clean = function($title, $desc, $border_color) {
                $html = '<div style="border-bottom: 2px solid ' . $border_color . '; padding-bottom: 10px; margin-top:0;">';
                $html .= '<h2 style="margin: 0; color: ' . $border_color . '; font-size: 24px;">' . $title . '</h2>';
                $html .= '<p style="color:#555; font-size: 15px; margin-top: 8px; margin-bottom: 0;">' . $desc . '</p>';
                $html .= '</div>';
                return $html;
            };

            if ($is_scarto_file) {
                header('Content-Type: text/html; charset=utf-8');
                preg_match('/<IdentificativoSdI>(.*?)<\/IdentificativoSdI>/', $xml_content, $m1);
                preg_match('/<NomeFile>(.*?)<\/NomeFile>/', $xml_content, $m2);
                preg_match('/<Codice>(.*?)<\/Codice>/', $xml_content, $m4);
                preg_match('/<Descrizione>(.*?)<\/Descrizione>/', $xml_content, $m5);

                echo '<div style="font-family:sans-serif; padding:30px; max-width:850px; margin:30px auto; background:#fff; border:1px solid #f5c6cb; border-left:6px solid #dc3545; border-radius:6px; box-shadow:0 4px 10px rgba(0,0,0,0.08);">';
                echo $render_header_clean('🛑 ' . $this->gettext('scarto_title'), $this->gettext('scarto_desc') . ' <b>' . htmlspecialchars($filename) . '</b>', '#dc3545');
                echo '<table style="width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; color:#333;">';
                if (!empty($m1[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; width:30%; background:#fcfcfc;">' . $this->gettext('sdi_id') . ':</td><td style="padding:12px;">' . htmlspecialchars($m1[1]) . '</td></tr>';
                if (!empty($m2[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; background:#fcfcfc;">' . $this->gettext('discarded_file') . ':</td><td style="padding:12px;">' . htmlspecialchars($m2[1]) . '</td></tr>';
                if (!empty($m4[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; background:#fcfcfc; color:#dc3545;">' . $this->gettext('error_code') . ':</td><td style="padding:12px; font-weight:bold; color:#dc3545;">' . htmlspecialchars($m4[1]) . '</td></tr>';
                if (!empty($m5[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; background:#fcfcfc;">' . $this->gettext('motivation') . ':</td><td style="padding:12px; color:#721c24; background:#fff5f5; font-weight:500;">' . htmlspecialchars($m5[1]) . '</td></tr>';
                echo '</table></div>';
                exit;
            }

            if ($is_impossibilita_file) {
                header('Content-Type: text/html; charset=utf-8');
                preg_match('/<IdentificativoSdI>(.*?)<\/IdentificativoSdI>/', $xml_content, $m1);

                echo '<div style="font-family:sans-serif; padding:30px; max-width:850px; margin:30px auto; background:#fff; border:1px solid #ffeeba; border-left:6px solid #f0ad4e; border-radius:6px; box-shadow:0 4px 10px rgba(0,0,0,0.08);">';
                echo $render_header_clean('⚠️ ' . $this->gettext('impossibilita_title'), $this->gettext('impossibilita_desc') . ' <b>' . htmlspecialchars($filename) . '</b>', '#856404');
                echo '<table style="width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; color:#333;">';
                if (!empty($m1[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; width:30%; background:#fcfcfc;">' . $this->gettext('sdi_id') . ':</td><td style="padding:12px;">' . htmlspecialchars($m1[1]) . '</td></tr>';
                echo '</table></div>';
                exit;
            }

            if ($is_consegna_file) {
                header('Content-Type: text/html; charset=utf-8');
                preg_match('/<IdentificativoSdI>(.*?)<\/IdentificativoSdI>/', $xml_content, $m1);
                preg_match('/<DataOraConsegna>(.*?)<\/DataOraConsegna>/', $xml_content, $m_consegna);

                echo '<div style="font-family:sans-serif; padding:30px; max-width:850px; margin:30px auto; background:#fff; border:1px solid #ddd; border-left:6px solid #28a745; border-radius:6px; box-shadow:0 4px 10px rgba(0,0,0,0.08);">';
                echo $render_header_clean('✅ ' . $this->gettext('receipt_title'), $this->gettext('receipt_desc') . ' <b>' . htmlspecialchars($filename) . '</b>', '#28a745');
                echo '<table style="width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; color:#333;">';
                if (!empty($m1[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; width:30%; background:#fcfcfc;">' . $this->gettext('sdi_id') . ':</td><td style="padding:12px;">' . htmlspecialchars($m1[1]) . '</td></tr>';
                if (!empty($m_consegna[1])) echo '<tr style="border-bottom:1px solid #eee;"><td style="padding:12px; font-weight:bold; background:#fcfcfc;">' . $this->gettext('delivery_date') . ':</td><td style="padding:12px;">' . htmlspecialchars(str_replace('T', ' ', $m_consegna[1])) . '</td></tr>';
                echo '</table></div>';
                exit;
            }

            $xmlDoc = new DOMDocument();
            libxml_use_internal_errors(true);
            if (!@$xmlDoc->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . ltrim($xml_content))) {
                header('Content-Type: text/html; charset=utf-8');
                echo "<h3>" . htmlspecialchars($this->gettext('error_xml_structure')) . "</h3>" . htmlspecialchars($this->gettext('error_xml_invalid'));
                exit;
            }

            $xslName = 'FatturaOrdinaria.xsl';
            $xsl_path = __DIR__ . DIRECTORY_SEPARATOR . 'stylesheets' . DIRECTORY_SEPARATOR . $xslName;

            if (!file_exists($xsl_path)) {
                header('Content-Type: text/html; charset=utf-8');
                echo "<h3>" . htmlspecialchars($this->gettext('error_xsl_missing')) . "</h3>";
                exit;
            }

            $xsl_doc = new DOMDocument();
            if (@$xsl_doc->load($xsl_path) and class_exists('XSLTProcessor')) {
                $proc = new XSLTProcessor();
                if (@$proc->importStyleSheet($xsl_doc)) {
                    header('Content-Type: text/html; charset=utf-8');
                    header('Content-Disposition: inline;');
                    
                    echo $proc->transformToXML($xmlDoc);
                    exit;
                }
            }

            header('Content-Type: text/html; charset=utf-8');
            echo "<h3>" . htmlspecialchars($this->gettext('error_processing')) . "</h3>";
            exit;

        } catch (Exception $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<div style="font-family:sans-serif; padding:20px; color:#721c24; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;">';
            echo '<h3>' . htmlspecialchars($this->gettext('error_unexpected')) . '</h3><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
            exit;
        }
    }

    private function stripP7MData($string, $dirTemp) {
        $uid = uniqid('tmp_', true);
        $fileP7M = $dirTemp . $uid . '.p7m';
        $filePem = $dirTemp . $uid . '.pem';
        $fileXml = $dirTemp . $uid . '.xml';

        if (base64_decode($string, true)) {
            $string = base64_decode($string);
        }
        
        file_put_contents($fileP7M, $string);
        $this->DerToSmime($fileP7M);

        $content = null;
        if (function_exists('openssl_cms_verify')) {
            @openssl_cms_verify($fileP7M, OPENSSL_CMS_NOVERIFY + OPENSSL_CMS_NOSIGS, $filePem);
            if (file_exists($filePem)) {
                @openssl_cms_verify($fileP7M, OPENSSL_CMS_NOVERIFY + OPENSSL_CMS_NOSIGS, $filePem, [], $filePem, $fileXml);
                if (file_exists($fileXml)) {
                    $content = file_get_contents($fileXml);
                    @unlink($fileXml);
                }
                @unlink($filePem);
            }
        }
        
        @unlink($fileP7M);
        return $content;
    }

    private function DerToSmime($filePath) {
        $from = file_get_contents($filePath);
        $to_data = "MIME-Version: 1.0\r\n" .
                   "Content-Disposition: attachment; filename=\"smime.p7m\"\r\n" .
                   "Content-Type: application/pkcs7-mime; smime-type=signed-data; name=\"smime.p7m\"\r\n" .
                   "Content-Transfer-Encoding: base64\r\n\r\n";
        $to_data .= chunk_split(base64_encode($from));
        return file_put_contents($filePath, $to_data);
    }
}
