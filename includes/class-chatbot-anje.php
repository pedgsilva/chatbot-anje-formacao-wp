<?php
/**
 * Main ChatBot ANJE Formacao class
 */

if (!defined('ABSPATH')) exit;

class ChatBot_ANJE_Formacao {

    private static $instance = null;
    private $option_key = 'chatbot_anje_formacao_settings';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_chatbot'], 100);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_chatbot_anje_chat', [$this, 'handle_chat']);
        add_action('wp_ajax_nopriv_chatbot_anje_chat', [$this, 'handle_chat']);
    }

    /**
     * Get settings with defaults
     */
    private function get_settings() {
        $defaults = [
            'chatbot_name' => 'ChatBot ANJE',
            'backend_url' => '',
            'openrouter_key' => '',
            'model' => 'openrouter/owl-alpha',
            'welcome_message' => '',
            'primary_color' => '#007bff',
            'position' => 'right',
            'max_tokens' => 800,
            'request_timeout' => 60,
            'show_on_all_pages' => 'yes',
        ];
        $settings = get_option($this->option_key, []);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Enqueue CSS and JS assets
     */
    public function enqueue_assets() {
        $settings = $this->get_settings();
        
        if ($settings['show_on_all_pages'] !== 'yes') {
            if (!is_front_page() && !is_page()) return;
        }

        wp_register_style('chatbot-anje-css', false);
        wp_enqueue_style('chatbot-anje-css');
        wp_add_inline_style('chatbot-anje-css', $this->get_css($settings));

        // No external JS file needed - all inline
    }

    /**
     * Generate dynamic CSS with custom colors/position
     */
    private function get_css($settings) {
        $color = esc_attr($settings['primary_color']);
        $pos = esc_attr($settings['position']);
        $bottom = '20px';
        $side = ($pos === 'left') ? 'left: 20px;' : 'right: 20px;';

        return "
            #chatbot-anje-widget{position:fixed;bottom:{$bottom};{$side}z-index:999999;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
            #chatbot-anje-toggle{width:60px;height:60px;border-radius:50%;border:none;background:{$color};color:#fff;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2);font-size:28px;display:flex;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s}
            #chatbot-anje-toggle:hover{transform:scale(1.08);box-shadow:0 6px 24px rgba(0,0,0,.3)}
            #chatbot-anje-window{position:absolute;bottom:75px;" . ($pos === 'left' ? 'left:0' : 'right:0') . ";width:390px;height:560px;background:#fff;border-radius:16px;box-shadow:0 12px 48px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden;animation:chatbotSlideUp .25s ease}
            @keyframes chatbotSlideUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
            #chatbot-anje-header{background:{$color};color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px}
            #chatbot-anje-header-text{flex:1}
            #chatbot-anje-header strong{display:block;font-size:14px;font-weight:600}
            #chatbot-anje-header small{font-size:11px;opacity:.85}
            #chatbot-anje-close{background:none;border:none;color:#fff;font-size:22px;cursor:pointer;opacity:.7;padding:4px;line-height:1}
            #chatbot-anje-close:hover{opacity:1}
            #chatbot-anje-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#f0f2f5}
            .chatbot-msg{max-width:85%;padding:10px 14px;border-radius:12px;font-size:13.5px;line-height:1.55;word-wrap:break-word;box-shadow:0 1px 2px rgba(0,0,0,.06)}
            .chatbot-msg-bot{background:#fff;color:#222;align-self:flex-start;border-bottom-left-radius:4px}
            .chatbot-msg-user{background:{$color};color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
            .chatbot-msg-bot a{color:#0066cc!important;text-decoration:underline!important;word-break:break-all}
            .chatbot-msg-bot strong{color:#1a1a2e}
            .chatbot-msg-bot em{font-style:italic}
            #chatbot-anje-typing{background:#fff;color:#888;align-self:flex-start;font-size:12px;font-style:italic;padding:6px 12px;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.06)}
            #chatbot-anje-input-area{display:flex;padding:10px 12px;background:#fff;border-top:1px solid #e8e8e8;gap:8px}
            #chatbot-anje-input{flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:20px;outline:none;font-size:13.5px;font-family:inherit}
            #chatbot-anje-input:focus{border-color:{$color}}
            #chatbot-anje-send{width:42px;height:42px;border-radius:50%;border:none;background:{$color};color:#fff;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:opacity .2s}
            #chatbot-anje-send:hover{opacity:.85}
            #chatbot-anje-send:disabled{background:#ccc;cursor:not-allowed}
            @media(max-width:480px){
                #chatbot-anje-window{width:calc(100vw - 16px);height:calc(100vh - 120px);right:0;left:0;margin:0 auto}
            }
        ";
    }

    /**
     * Render chatbot HTML in footer
     */
    public function render_chatbot() {
        $s = $this->get_settings();
        $name = esc_html($s['chatbot_name']);
        $welcome = $s['welcome_message'] ?: "Olá! 👋 Sou o assistente virtual da ANJE Formação.\n\nPosso ajudar com:\n• 📚 Cursos\n• 💰 Preços e datas\n• 👥 Equipa\n• 📞 Contactos\n\nO que procura?";
        $ajax = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('chatbot_anje_nonce');
        
        // Decode HTML entities in welcome message
        $welcome = html_entity_decode($welcome, ENT_QUOTES, 'UTF-8');
        ?>
        <div id="chatbot-anje-widget">
            <button id="chatbot-anje-toggle" aria-label="<?php echo $name; ?>">&#128172;</button>
            <div id="chatbot-anje-window">
                <div id="chatbot-anje-header">
                    <div id="chatbot-anje-header-text">
                        <strong id="chatbot-anje-name"><?php echo $name; ?></strong>
                        <small>Online</small>
                    </div>
                    <button id="chatbot-anje-close" aria-label="Fechar">&#10005;</button>
                </div>
                <div id="chatbot-anje-messages"></div>
                <div id="chatbot-anje-input-area">
                    <input type="text" id="chatbot-anje-input" placeholder="Escreva a sua pergunta..." maxlength="500">
                    <button id="chatbot-anje-send" aria-label="Enviar">&#10148;</button>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var ajaxUrl = <?php echo json_encode($ajax); ?>;
            var nonce = <?php echo json_encode($nonce); ?>;
            var welcome = <?php echo json_encode($welcome); ?>;
            var busy = false;
            var shown = false;
            var timeout = <?php echo intval($s['request_timeout']) * 1000; ?>;

            var toggle = document.getElementById('chatbot-anje-toggle');
            var win = document.getElementById('chatbot-anje-window');
            var input = document.getElementById('chatbot-anje-input');
            var sendBtn = document.getElementById('chatbot-anje-send');
            var msgs = document.getElementById('chatbot-anje-messages');

            toggle.addEventListener('click', function(){
                if(win.style.display === 'flex'){
                    win.style.display = 'none';
                } else {
                    win.style.display = 'flex';
                    input.focus();
                    if(!shown && welcome){
                        addMsg(welcome, 'bot');
                        shown = true;
                    }
                }
            });

            document.getElementById('chatbot-anje-close').addEventListener('click', function(){
                win.style.display = 'none';
            });

            sendBtn.addEventListener('click', sendMsg);
            input.addEventListener('keypress', function(e){ if(e.key === 'Enter') sendMsg(); });

            // Close on Escape
            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape' && win.style.display === 'flex'){
                    win.style.display = 'none';
                }
            });

            function sendMsg(){
                var msg = input.value.trim();
                if(!msg || busy) return;
                busy = true;
                sendBtn.disabled = true;
                addMsg(msg, 'user');
                input.value = '';
                addTyping();

                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxUrl);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.timeout = timeout;

                xhr.onload = function(){
                    removeTyping();
                    try {
                        var r = JSON.parse(xhr.responseText);
                        if(r.success){
                            addMsg(r.data.response || 'Erro.', 'bot');
                        } else {
                            addMsg('Erro: ' + (r.data || 'Desconhecido'), 'bot');
                        }
                    } catch(e) {
                        addMsg('Erro ao processar resposta.', 'bot');
                    }
                };
                xhr.onerror = function(){
                    removeTyping();
                    addMsg('Erro de ligação. Verifique a URL do backend ou contacte o administrador.', 'bot');
                };
                xhr.ontimeout = function(){
                    removeTyping();
                    addMsg('Timeout - o servidor demorou demasiado. Tente novamente.', 'bot');
                };
                xhr.onreadystatechange = function(){
                    if(xhr.readyState === 4){
                        busy = false;
                        sendBtn.disabled = false;
                        input.focus();
                    }
                };
                xhr.send('action=chatbot_anje_chat&message=' + encodeURIComponent(msg) + '&nonce=' + nonce);
            }

            function addMsg(text, type){
                var d = document.createElement('div');
                d.className = 'chatbot-msg chatbot-msg-' + type;
                // Format: bold, links, line breaks
                var html = text
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
                    .replace(/(https?:\/\/[^\s<>"']+)/g,'<a href="$1" target="_blank" rel="noopener">$1</a>')
                    .replace(/\n/g,'<br>');
                d.innerHTML = html;
                msgs.appendChild(d);
                d.scrollIntoView({behavior:'smooth'});
            }

            function addTyping(){
                var d = document.createElement('div');
                d.id = 'chatbot-anje-typing';
                d.className = 'chatbot-msg';
                d.textContent = 'A escrever...';
                msgs.appendChild(d);
            }

            function removeTyping(){
                var t = document.getElementById('chatbot-anje-typing');
                if(t) t.remove();
            }
        })();
        </script>
        <?php
    }

    /**
     * Handle AJAX chat request
     */
    public function handle_chat() {
        if (!check_ajax_referer('chatbot_anje_nonce', 'nonce', false)) {
            wp_send_json_error('Segurança: token inválido', 403);
        }

        $msg = sanitize_text_field($_POST['message'] ?? '');
        if (empty($msg)) {
            wp_send_json_error('Mensagem vazia', 400);
        }

        $settings = $this->get_settings();

        // Check if backend URL is configured
        $backend_url = esc_url_raw($settings['backend_url']);
        $openrouter_key = $settings['openrouter_key'];

        if (empty($backend_url) && empty($openrouter_key)) {
            wp_send_json_success(['response' => '⚠️ O chatbot não está configurado. Peça ao administrador para configurar a URL do backend ou a API Key em Definições > ChatBot ANJE.']);
        }

        // If backend URL is set, proxy the request
        if (!empty($backend_url)) {
            $response = $this->proxy_to_backend($backend_url, $msg, $settings);
            wp_send_json_success(['response' => $response]);
        }

        // Otherwise, call OpenRouter directly
        $response = $this->call_openrouter($msg, $settings);
        wp_send_json_success(['response' => $response]);
    }

    /**
     * Proxy request to Flask backend
     */
    private function proxy_to_backend($url, $msg, $settings) {
        $response = wp_remote_post(trailingslashit($url) . 'chat', [
            'timeout' => intval($settings['request_timeout']),
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['message' => $msg]),
        ]);

        if (is_wp_error($response)) {
            return 'Erro de ligação ao backend: ' . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['response'] ?? 'Erro ao processar resposta do backend.';
    }

    /**
     * Call OpenRouter API directly from WordPress
     */
    private function call_openrouter($msg, $settings) {
        $key = $settings['openrouter_key'];
        $model = $settings['model'] ?: 'openrouter/owl-alpha';
        $max_tokens = intval($settings['max_tokens']) ?: 800;

        $response = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', [
            'timeout' => intval($settings['request_timeout']),
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $this->get_system_prompt($settings)],
                    ['role' => 'user', 'content' => 'Pergunta: ' . $msg],
                ],
                'temperature' => 0.3,
                'max_tokens' => $max_tokens,
            ]),
        ]);

        if (is_wp_error($response)) {
            return 'Erro de ligação à API: ' . $response->get_error_message();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['error'])) {
            return 'Erro da API: ' . ($data['error']['message'] ?? 'Desconhecido');
        }

        return $data['choices'][0]['message']['content'] ?? 'Erro ao gerar resposta.';
    }

    /**
     * Build system prompt for direct API calls
     */
    private function get_system_prompt($settings) {
        return "És o assistente virtual da ANJE Formação (anjeformacao.pt).\n"
            . "\nSOBRE: A ANJE é uma associação de direito privado e utilidade pública fundada em 1986. A ANJE Formação está presente nas 5 regiões administrativas do país, certificada pela DGERT.\n"
            . "\nEQUIPA:\n"
            . "- Ana Jogo Mendes - Diretora ANJE Formação\n"
            . "- Coordenadores: Cláudia Almeida, Cristiana Moreira, Manuela Almeida, Vitória Pereira, Ana Rodrigues (Lisboa), Armanda Ângelo (Coimbra), Cátia Santos (Algarve), Patrícia Nobre (Alentejo)\n"
            . "- Administrativos: Sara Almeida, Susana Pereira, Fátima Pinto (Coimbra)\n"
            . "- Teresa Miranda - Comunicação e Marketing\n"
            . "\nÓRGÃOS SOCIAIS:\n"
            . "- Presidente: Carlos Carvalho\n"
            . "- Vice-Presidentes: Nuno Malheiro, Filipa Pinto de Carvalho, Gonçalo Simões de Almeida\n"
            . "- Presidente Assembleia Geral: Miguel Moreira da Silva\n"
            . "- Presidente Conselho Fiscal: Catarina Azevedo\n"
            . "- Conselho Fiscal: Pedro Cardoso (VP), Sofia Xavier (Vogal), Vítor Almeida (Vogal), Gonçalo Abreu (Vogal)\n"
            . "\nCONTACTOS: infoformacao@anje.pt | (+351) 220 108 074 | Rua do Conde de Redondo, 91-B, Lisboa\n"
            . "\nREGRAS:\n"
            . "- Português de Portugal\n"
            . "- Usa **negrita** para títulos\n"
            . "- Inclui URLs completos: https://anjeformacao.pt/curso/...\n"
            . "- Não listes cursos para perguntas sobre equipa/orgãos\n"
            . "- Para cursos, diz para visitar https://anjeformacao.pt/cursos/ ou perguntar sobre área específica";
    }

    /**
     * Admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'ChatBot ANJE Formação',
            'ChatBot ANJE',
            'manage_options',
            'chatbot-anje-formacao',
            [$this, 'admin_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('chatbot_anje_grp', $this->option_key, [$this, 'sanitize_settings']);
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        $sanitized['chatbot_name'] = sanitize_text_field($input['chatbot_name'] ?? 'ChatBot ANJE');
        $sanitized['backend_url'] = esc_url_raw($input['backend_url'] ?? '');
        $sanitized['openrouter_key'] = sanitize_text_field($input['openrouter_key'] ?? '');
        $sanitized['model'] = sanitize_text_field($input['model'] ?? 'openrouter/owl-alpha');
        $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message'] ?? '');
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color'] ?? '#007bff');
        $sanitized['position'] = in_array($input['position'] ?? '', ['left', 'right']) ? $input['position'] : 'right';
        $sanitized['max_tokens'] = absint($input['max_tokens'] ?? 800);
        $sanitized['request_timeout'] = absint($input['request_timeout'] ?? 60);
        $sanitized['show_on_all_pages'] = ($input['show_on_all_pages'] ?? '') === 'yes' ? 'yes' : 'no';
        return $sanitized;
    }

    /**
     * Admin settings page
     */
    public function admin_page() {
        $s = $this->get_settings();
        ?>
        <div class="wrap">
            <h1>🤖 ChatBot ANJE Formação</h1>
            <form method="post" action="options.php">
                <?php settings_fields('chatbot_anje_grp'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="chatbot_name">Nome do ChatBot</label></th>
                        <td>
                            <input type="text" id="chatbot_name" name="chatbot_anje_formacao_settings[chatbot_name]" value="<?php echo esc_attr($s['chatbot_name']); ?>" class="regular-text" placeholder="Ex: ChatBot ANJE, Assistente Virtual, etc.">
                            <p class="description">Nome mostrado no cabeçalho do chat</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="welcome_message">Mensagem de Boas-vindas</label></th>
                        <td>
                            <textarea id="welcome_message" name="chatbot_anje_formacao_settings[welcome_message]" rows="5" class="large-text" placeholder="Olá! 👋 Sou o assistente virtual..."><?php echo esc_textarea($s['welcome_message']); ?></textarea>
                            <p class="description">Mensagem inicial do chat. Suporta **negrita** e linhas novas (\\n)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="backend_url">URL do Backend (Flask)</label></th>
                        <td>
                            <input type="url" id="backend_url" name="chatbot_anje_formacao_settings[backend_url]" value="<?php echo esc_attr($s['backend_url']); ?>" class="regular-text" placeholder="https://exemplo.com:5000">
                            <p class="description">URL do backend Flask (ex: https://chat.anjeformacao.pt). Se preenchido, o chatbot usa o backend. Se vazio, usa a API key diretamente.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="openrouter_key">OpenRouter API Key</label></th>
                        <td>
                            <input type="password" id="openrouter_key" name="chatbot_anje_formacao_settings[openrouter_key]" value="<?php echo esc_attr($s['openrouter_key']); ?>" class="regular-text" placeholder="sk-or-...">
                            <p class="description">Necessário apenas se não usar backend. Obter em <a href="https://openrouter.ai/keys" target="_blank">openrouter.ai</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="model">Modelo LLM</label></th>
                        <td>
                            <input type="text" id="model" name="chatbot_anje_formacao_settings[model]" value="<?php echo esc_attr($s['model']); ?>" class="regular-text">
                            <p class="description">Ex: openrouter/owl-alpha</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="primary_color">Cor Principal</label></th>
                        <td>
                            <input type="color" id="primary_color" name="chatbot_anje_formacao_settings[primary_color]" value="<?php echo esc_attr($s['primary_color']); ?>">
                            <span style="margin-left:8px;color:<?php echo esc_attr($s['primary_color']); ?>">&#9632;</span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="position">Posição</label></th>
                        <td>
                            <select id="position" name="chatbot_anje_formacao_settings[position]">
                                <option value="right" <?php selected($s['position'], 'right'); ?>>Direita</option>
                                <option value="left" <?php selected($s['position'], 'left'); ?>>Esquerda</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="max_tokens">Max Tokens</label></th>
                        <td>
                            <input type="number" id="max_tokens" name="chatbot_anje_formacao_settings[max_tokens]" value="<?php echo esc_attr($s['max_tokens']); ?>" min="200" max="4000" class="small-text">
                            <p class="description">Limite de tokens para a resposta (200-4000)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="request_timeout">Timeout (segundos)</label></th>
                        <td>
                            <input type="number" id="request_timeout" name="chatbot_anje_formacao_settings[request_timeout]" value="<?php echo esc_attr($s['request_timeout']); ?>" min="15" max="120" class="small-text">
                            <p class="description">Tempo máximo de espera pela resposta</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Mostrar em todas as páginas</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chatbot_anje_formacao_settings[show_on_all_pages]" value="yes" <?php checked($s['show_on_all_pages'], 'yes'); ?>>
                                Sim, mostrar o chatbot em todas as páginas
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar Configurações'); ?>
            </form>

            <hr>
            <h2>Estado</h2>
            <table class="widefat" style="max-width:500px">
                <thead><tr><th>Configuração</th><th>Estado</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Backend URL</td>
                        <td><?php echo !empty($s['backend_url']) ? '<span style="color:green">✓ Configurado</span> <code>' . esc_html($s['backend_url']) . '</code>' : '<span style="color:orange">✗ Não configurado</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>OpenRouter API Key</td>
                        <td><?php echo !empty($s['openrouter_key']) ? '<span style="color:green">✓ Configurada</span>' : '<span style="color:orange">✗ Não configurada</span>'; ?></td>
                    </tr>
                    <tr>
                        <td>Nome do ChatBot</td>
                        <td><code><?php echo esc_html($s['chatbot_name']); ?></code></td>
                    </tr>
                </tbody>
            </table>

            <hr>
            <h2>Como Usar</h2>
            <ol>
                <li><strong>Opção A (Backend):</strong> Insira a URL do backend Flask. O chatbot proxya os pedidos para o backend.</li>
                <li><strong>Opção B (Direto):</strong> Insira a OpenRouter API Key. O chatbot chama a API diretamente do WordPress.</li>
                <li>Personalize o <strong>nome do chatbot</strong>, <strong>cor</strong> e <strong>mensagem de boas-vindas</strong>.</li>
                <li>Use o shortcode <code>[chatbot_anje]</code> para mostrar o chatbot numa página específica (opcional).</li>
            </ol>
        </div>
        <?php
    }
}
