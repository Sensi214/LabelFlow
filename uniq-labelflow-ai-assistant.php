<?php
/*
Plugin Name: Uniq LabelFlow AI Assistant
Description: Chat-style AI assistant panel for Uniq LabelFlow. Connects WordPress frontend to Render backend API.
Version: 1.0.0
Author: Uniq LabelFlow
*/

if (!defined('ABSPATH')) exit;

add_shortcode('ulf_ai_assistant', 'ulf_ai_assistant_shortcode');

add_action('admin_menu', function () {
    add_options_page('Uniq LabelFlow AI', 'Uniq LabelFlow AI', 'manage_options', 'ulf-ai-settings', 'ulf_ai_settings_page');
});

add_action('admin_init', function () {
    register_setting('ulf_ai_settings_group', 'ulf_render_api_url');
    register_setting('ulf_ai_settings_group', 'ulf_wp_secret_key');
});

function ulf_ai_settings_page() {
    ?>
    <div class="wrap">
        <h1>Uniq LabelFlow AI Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ulf_ai_settings_group'); ?>
            <?php do_settings_sections('ulf_ai_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Render Backend URL</th>
                    <td>
                        <input type="url" name="ulf_render_api_url" value="<?php echo esc_attr(get_option('ulf_render_api_url')); ?>" class="regular-text" placeholder="https://your-app.onrender.com">
                        <p class="description">Paste your Render backend URL here.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">WP Secret Key</th>
                    <td>
                        <input type="text" name="ulf_wp_secret_key" value="<?php echo esc_attr(get_option('ulf_wp_secret_key')); ?>" class="regular-text" placeholder="same as Render WP_SECRET_KEY">
                        <p class="description">Must match the WP_SECRET_KEY in Render environment variables.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function ulf_ai_assistant_shortcode() {
    $nonce = wp_create_nonce('ulf_ai_nonce');
    ob_start(); ?>
    <style>
      .ulf-ai{max-width:960px;margin:24px auto;border:1px solid #ded7ff;border-radius:22px;background:#fff;font-family:Arial,sans-serif;box-shadow:0 18px 50px rgba(80,40,150,.08);overflow:hidden}
      .ulf-ai-head{background:#0e0e1a;color:#fff;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:12px}
      .ulf-ai-head h2{margin:0;font-size:24px}
      .ulf-ai-badge{color:#c98aff;font-weight:bold}
      .ulf-ai-body{padding:20px;display:grid;grid-template-columns:1fr 320px;gap:18px}
      .ulf-chat{background:#f8f8f8;border-radius:16px;padding:14px;height:420px;overflow:auto}
      .ulf-msg{padding:11px 13px;margin:10px 0;border-radius:14px;line-height:1.45}
      .ulf-user{background:#6a00f4;color:#fff;margin-left:45px}
      .ulf-bot{background:#fff;border:1px solid #e8e1ff;margin-right:45px}
      .ulf-ai-controls{background:#faf8ff;border:1px solid #eee;border-radius:16px;padding:14px}
      .ulf-ai-controls label{display:block;font-weight:700;margin:10px 0 5px}
      .ulf-ai-controls input,.ulf-ai-controls textarea,.ulf-ai-controls select{width:100%;box-sizing:border-box;padding:10px;border:1px solid #ccc;border-radius:10px}
      .ulf-ai-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
      .ulf-ai-btn{background:#6a00f4;color:#fff;border:0;border-radius:10px;padding:10px 12px;font-weight:800;cursor:pointer}
      .ulf-ai-btn.dark{background:#111}
      .ulf-ai-btn.gold{background:#bf8b30}
      .ulf-ai-input{display:flex;gap:8px;padding:14px;border-top:1px solid #eee}
      .ulf-ai-input input{flex:1;padding:12px;border:1px solid #ccc;border-radius:10px}
      .ulf-ai-input button{background:#6a00f4;color:white;border:0;border-radius:10px;padding:12px 16px;font-weight:bold}
      .ulf-output{font-size:13px;white-space:pre-wrap;background:#fff;border:1px dashed #c98aff;border-radius:12px;padding:12px;margin-top:12px;max-height:220px;overflow:auto}
      @media(max-width:820px){.ulf-ai-body{grid-template-columns:1fr}.ulf-chat{height:340px}}
    </style>

    <div class="ulf-ai">
      <div class="ulf-ai-head">
        <h2>🤖 Uniq LabelFlow AI Assistant</h2>
        <div class="ulf-ai-badge">AI Label + Mockup Helper</div>
      </div>

      <div class="ulf-ai-body">
        <div>
          <div class="ulf-chat" id="ulfAiChat">
            <div class="ulf-msg ulf-bot">Hi! Tell me your candle idea and I can help generate label copy, style direction, mockup prompts, and archive-ready details.</div>
          </div>

          <div class="ulf-ai-input">
            <input id="ulfAiPrompt" placeholder="Example: Make a luxury lavender candle label for Etsy">
            <button type="button" id="ulfAiSend">Send</button>
          </div>
        </div>

        <div class="ulf-ai-controls">
          <label>Brand</label>
          <input id="ulfAiBrand" placeholder="SENSI Candle Co">

          <label>Scent / Product Name</label>
          <input id="ulfAiScent" placeholder="Lavender Dream">

          <label>Style</label>
          <select id="ulfAiStyle">
            <option>Luxury boutique</option>
            <option>Minimal modern</option>
            <option>Earthy organic</option>
            <option>Holiday premium</option>
            <option>Bold colorful</option>
          </select>

          <label>Notes</label>
          <textarea id="ulfAiNotes" rows="4" placeholder="calming floral scent, soy candle, giftable"></textarea>

          <div class="ulf-ai-actions">
            <button class="ulf-ai-btn" type="button" id="ulfGenCopy">Generate Label Copy</button>
            <button class="ulf-ai-btn dark" type="button" id="ulfGenStyle">Suggest Style</button>
            <button class="ulf-ai-btn gold" type="button" id="ulfGenMockupPrompt">Mockup Prompt</button>
          </div>

          <div class="ulf-output" id="ulfAiOutput">AI output appears here.</div>
        </div>
      </div>
    </div>

    <script>
    (function(){
      const ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
      const nonce = "<?php echo esc_js($nonce); ?>";
      const chat = document.getElementById('ulfAiChat');
      const out = document.getElementById('ulfAiOutput');

      function addMsg(text, who){
        const div=document.createElement('div');
        div.className='ulf-msg '+(who==='user'?'ulf-user':'ulf-bot');
        div.textContent=text;
        chat.appendChild(div);
        chat.scrollTop=chat.scrollHeight;
      }

      function values(){
        return {
          brand: document.getElementById('ulfAiBrand').value || 'SENSI Candle Co',
          scent: document.getElementById('ulfAiScent').value || 'Lavender Dream',
          style: document.getElementById('ulfAiStyle').value || 'Luxury boutique',
          notes: document.getElementById('ulfAiNotes').value || 'premium candle'
        };
      }

      async function callAI(type, extraPrompt=''){
        const data = values();
        out.textContent='Thinking...';
        const body = new URLSearchParams();
        body.append('action','ulf_ai_proxy');
        body.append('nonce',nonce);
        body.append('type',type);
        body.append('brand',data.brand);
        body.append('scent',data.scent);
        body.append('style',data.style);
        body.append('notes',data.notes);
        body.append('prompt',extraPrompt);

        const res = await fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
        const json = await res.json();
        if(json.success){
          const text = typeof json.data === 'string' ? json.data : JSON.stringify(json.data,null,2);
          out.textContent=text;
          addMsg(text,'bot');
        } else {
          out.textContent='Error: '+json.data;
          addMsg('Error: '+json.data,'bot');
        }
      }

      document.getElementById('ulfGenCopy').onclick=()=>callAI('copy');
      document.getElementById('ulfGenStyle').onclick=()=>callAI('style');
      document.getElementById('ulfGenMockupPrompt').onclick=()=>callAI('mockup_prompt');
      document.getElementById('ulfAiSend').onclick=()=>{
        const prompt=document.getElementById('ulfAiPrompt').value;
        if(!prompt)return;
        addMsg(prompt,'user');
        callAI('chat',prompt);
      };
    })();
    </script>
    <?php
    return ob_get_clean();
}

add_action('wp_ajax_ulf_ai_proxy', 'ulf_ai_proxy');
add_action('wp_ajax_nopriv_ulf_ai_proxy', 'ulf_ai_proxy');

function ulf_ai_proxy() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ulf_ai_nonce')) {
        wp_send_json_error('Security check failed.');
    }

    $type = sanitize_text_field($_POST['type'] ?? 'copy');
    $brand = sanitize_text_field($_POST['brand'] ?? 'SENSI Candle Co');
    $scent = sanitize_text_field($_POST['scent'] ?? 'Lavender Dream');
    $style = sanitize_text_field($_POST['style'] ?? 'Luxury boutique');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');

    if ($type === 'mockup_prompt') {
        wp_send_json_success("Create a realistic luxury candle jar mockup for {$brand}, scent name '{$scent}', {$style} style, with notes: {$notes}. Use soft studio lighting, premium product photography, clean background, front-facing jar, readable label area.");
    }

    if ($type === 'style') {
        wp_send_json_success("Style Direction for {$scent}:\n\nPalette: warm cream, deep charcoal, soft gold accent.\nFonts: luxury serif for scent name, clean sans-serif for details.\nLayout: centered brand mark, large scent name, short description, small compliance footer.\nMockup: matte glass jar, soft shadows, boutique shelf-ready look.");
    }

    $render = trim(get_option('ulf_render_api_url'));
    $secret = trim(get_option('ulf_wp_secret_key'));

    if (!$render) {
        // Fallback without backend.
        $fallback = [
            'brand' => $brand,
            'scent' => $scent,
            'headline' => $scent,
            'shortDescription' => "A {$style} candle experience crafted with {$notes}.",
            'labelDescription' => "{$scent} is designed for a refined atmosphere, blending premium presentation with a memorable scent story.",
            'warning' => "Burn within sight. Keep away from children, pets, drafts, and flammable objects. Trim wick to 1/4 inch before lighting.",
            'suggestedTags' => ['candle','soy candle',strtolower($scent),'hand poured','gift candle']
        ];
        wp_send_json_success($fallback);
    }

    $endpoint = trailingslashit($render) . 'generate-label-copy';

    $response = wp_remote_post($endpoint, [
        'timeout' => 45,
        'headers' => [
            'Content-Type' => 'application/json',
            'x-wp-secret' => $secret
        ],
        'body' => wp_json_encode([
            'brand' => $brand,
            'scent' => $scent,
            'style' => $style,
            'notes' => $notes . ' ' . $prompt
        ])
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!$body) {
        wp_send_json_error('Invalid backend response.');
    }

    wp_send_json_success($body['result'] ?? $body);
}
