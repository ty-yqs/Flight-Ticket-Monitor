<?php
// Styled subscription form with simple bilingual support (English / 中文)
// Set language via ?lang=zh or ?lang=en (default en)
$lang = isset($_GET['lang']) && $_GET['lang'] === 'zh' ? 'zh' : 'en';
$t = function($k) use ($lang) {
    $strings = [
        'en' => [
            'title' => 'Flight Price Monitor',
            'lead' => 'Enter route, date and email — we will check hourly and send sorted prices to your inbox.',
            'from_label' => 'From City / Airport (grouped by province)',
            'to_label' => 'To City / Airport (grouped by province)',
            'from_hint' => 'Lists major airports in Mainland China, Hong Kong, Macau and Taiwan. Search by name or code.',
            'date_label' => 'Departure Date',
            'date_hint' => 'Please select a future departure date.',
            'email_label' => 'Recipient Email',
            'subscribe_btn' => 'Subscribe (hourly checks)',
            'reset_btn' => 'Reset',
            'note' => 'Note: subscriptions are stored in a local SQLite database. Configure SMTP on the server to enable email delivery (see README).',
            'search_placeholder' => 'Search by airport name or IATA code',
            'no_matches' => 'No matches found',
            'alert_missing' => 'Please select departure/arrival airports and enter a valid email.',
            'alert_invalid_date' => 'Please select a valid departure date.',
            'confirm_past' => 'You selected a past date. Continue?',
            'submitting' => 'Submitting...'
        ],
        'zh' => [
            'title' => '机票价格监控',
            'lead' => '请输入航线、日期和邮箱 — 我们会每小时检查并将排序后的票价发送到您的收件箱。',
            'from_label' => '出发城市 / 机场（按省份分组）',
            'to_label' => '到达城市 / 机场（按省份分组）',
            'from_hint' => '列出中国大陆、香港、澳门和台湾的主要机场。可按名称或 IATA 代码搜索。',
            'date_label' => '出发日期',
            'date_hint' => '请选择未来的出发日期。',
            'email_label' => '接收邮箱',
            'subscribe_btn' => '订阅（每小时检查）',
            'reset_btn' => '重置',
            'note' => '注意：订阅信息保存在本地 SQLite 数据库。请在服务器上配置 SMTP 以启用邮件发送（见 README）。',
            'search_placeholder' => '按机场名称或 IATA 代码搜索',
            'no_matches' => '未找到匹配项',
            'alert_missing' => '请选择出发/到达机场并输入有效的邮箱。',
            'alert_invalid_date' => '请选择有效的出发日期。',
            'confirm_past' => '您选择了过去的日期。是否继续？',
            'submitting' => '提交中...'
        ]
    ];
    return $strings[$lang][$k] ?? $k;
};

// small helper for generating lang links
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!doctype html>
<html lang="<?php echo $lang === 'zh' ? 'zh-CN' : 'en' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($t('title')) ?> — Subscribe</title>
  <style>
    :root{--bg:#f5f7fb;--card:#ffffff;--accent:#0b78d1;--muted:#666}
    *{box-sizing:border-box}
    body{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);margin:0;padding:24px;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .wrap{width:100%;max-width:720px}
    .card{background:var(--card);border-radius:12px;padding:22px;box-shadow:0 6px 18px rgba(12,24,40,0.08)}
    h1{margin:0 0 8px;font-size:20px}
    p.lead{margin:0 0 18px;color:var(--muted)}
    form .row{display:flex;gap:12px}
    label{display:block;font-size:13px;color:#222;margin-bottom:6px}
    input[type=text],input[type=email],input[type=date],select{width:100%;padding:10px 12px;border:1px solid #e6eef8;border-radius:8px;font-size:14px}
    .small{font-size:12px;color:var(--muted);margin-top:6px}
    .actions{display:flex;gap:12px;margin-top:14px}
    button{background:var(--accent);color:#fff;border:none;padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer}
    button[disabled]{opacity:.6;cursor:not-allowed}
    @media (max-width:640px){.row{flex-direction:column}}
    .foot{margin-top:14px;font-size:13px;color:var(--muted)}
    .hint{color:#888;font-size:13px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div>
          <h1><?php echo htmlspecialchars($t('title')) ?></h1>
          <p class="lead"><?php echo htmlspecialchars($t('lead')) ?></p>
        </div>
        <div style="font-size:13px">
          <a href="<?php echo $baseUrl ?>?lang=en"<?php if($lang==='en') echo ' style="font-weight:700"'?>>EN</a> |
          <a href="<?php echo $baseUrl ?>?lang=zh"<?php if($lang==='zh') echo ' style="font-weight:700"'?>>中文</a>
        </div>
      </div>

      <form id="subForm" action="subscribe.php" method="post" onsubmit="return prepareSubmit()">
        <div class="row" style="margin-bottom:12px">
          <div style="flex:1">
            <label for="from_search"><?php echo htmlspecialchars($t('from_label')) ?></label>
            <div class="searchable" id="from_search" data-name="from"></div>
            <div class="small hint"><?php echo htmlspecialchars($t('from_hint')) ?></div>
          </div>
          <div style="flex:1">
            <label for="to_search"><?php echo htmlspecialchars($t('to_label')) ?></label>
            <div class="searchable" id="to_search" data-name="to"></div>
          </div>
        </div>

        <div style="display:flex;gap:12px;align-items:flex-end">
          <div style="flex:1">
            <label for="date"><?php echo htmlspecialchars($t('date_label')) ?></label>
            <input id="date" name="date" type="date" required>
            <div class="small hint"><?php echo htmlspecialchars($t('date_hint')) ?></div>
          </div>

          <div style="width:280px;min-width:160px">
            <label for="email"><?php echo htmlspecialchars($t('email_label')) ?></label>
            <input id="email" name="email" type="email" placeholder="you@example.com" required>
          </div>
        </div>

        <div class="actions">
          <button id="submitBtn" type="submit"><?php echo htmlspecialchars($t('subscribe_btn')) ?></button>
          <button type="button" onclick="document.getElementById('subForm').reset();">Reset</button>
        </div>
      </form>

      <div class="foot">
        <div><?php echo htmlspecialchars($t('note')) ?></div>
      </div>
    </div>
  </div>

  <script>
    // localized strings available to JS
    window.I18N = {
      search_placeholder: <?php echo json_encode($t('search_placeholder')) ?>,
      no_matches: <?php echo json_encode($t('no_matches')) ?>,
      alert_missing: <?php echo json_encode($t('alert_missing')) ?>,
      alert_invalid_date: <?php echo json_encode($t('alert_invalid_date')) ?>,
      confirm_past: <?php echo json_encode($t('confirm_past')) ?>,
      submitting: <?php echo json_encode($t('submitting')) ?>
    };

    // Dynamically load airports data and initialize the searchable dropdowns
    let AIRPORTS = [];

    function buildSearchable(id){
      const container = document.getElementById(id);
      const name = container.dataset.name || id;
      // visible input, hidden input for form submit, dropdown
      const vis = document.createElement('input');
      vis.type = 'text'; vis.placeholder = window.I18N.search_placeholder; vis.className='sinput';
      const hidden = document.createElement('input');
      hidden.type = 'hidden'; hidden.name = name; hidden.required = true;
      const list = document.createElement('div'); list.className = 'slist';
      container.appendChild(vis); container.appendChild(hidden); container.appendChild(list);

      // group airports by group
      const groups = {};
      AIRPORTS.forEach(a=>{ if(!groups[a.group]) groups[a.group]=[]; groups[a.group].push(a); });

      function renderAll(){
        list.innerHTML='';
        // Sort group names locale-aware but put 'Others' at the end
        const keys = Object.keys(groups).sort((a,b)=>{
          if (a === 'Others') return 1;
          if (b === 'Others') return -1;
          return a.localeCompare(b, 'zh-CN');
        });
        for(const g of keys){
          const gdiv = document.createElement('div'); gdiv.className='gtitle'; gdiv.textContent = g; list.appendChild(gdiv);
          groups[g].forEach(a=>{
            const it = document.createElement('div'); it.className='item'; it.dataset.code=a.code; it.dataset.name=a.name; it.textContent = a.code + ' — ' + a.name;
            it.addEventListener('click', ()=>{ vis.value = it.textContent; hidden.value = a.code; list.style.display='none'; });
            list.appendChild(it);
          });
        }
      }

      renderAll();

      vis.addEventListener('input', ()=>{
        const q = vis.value.trim().toLowerCase();
        if(q===''){ renderAll(); list.style.display='block'; hidden.value=''; return; }
        list.innerHTML='';
        const matched = AIRPORTS.filter(a=> (a.code+a.name).toLowerCase().indexOf(q) !== -1 );
        if(matched.length===0){ const none=document.createElement('div'); none.className='none'; none.textContent=window.I18N.no_matches; list.appendChild(none); }
        matched.forEach(a=>{
          const it = document.createElement('div'); it.className='item'; it.dataset.code=a.code; it.dataset.name=a.name; it.textContent = a.code + ' — ' + a.name;
          it.addEventListener('click', ()=>{ vis.value = it.textContent; hidden.value = a.code; list.style.display='none'; });
          list.appendChild(it);
        });
        list.style.display='block';
      });

      vis.addEventListener('focus', ()=>{ list.style.display='block'; });
      document.addEventListener('click', (e)=>{ if(!container.contains(e.target)) list.style.display='none'; });
    }

    // After loading airports_cn_prov.json, initialize the search components
    (function loadAirports(){
      fetch('airports_cn_prov.json')
        .then(r=>{
          if(r.ok) return r.json();
          return fetch('airports_cn.json').then(r2=>{ if(!r2.ok) throw new Error('both fetch failed'); return r2.json(); });
        })
        .then(data=>{
          AIRPORTS = data.map(a=>{
            const prov = (a.province && a.province.trim()) ? a.province.trim() : '';
            const group = prov || (a.city && a.city.trim()) || a.country || 'Others';
            return { code: a.iata, name: a.name || a.city || a.iata, group };
          });
          AIRPORTS.forEach(a=>{ if(!a.group) a.group = 'Others'; });
          buildSearchable('from_search');
          buildSearchable('to_search');
        })
        .catch(err=>{
          console.error('Failed to load airports data, using built-in minimal list:', err);
          AIRPORTS = [ {code:'PEK',name:'Beijing Capital',group:'Beijing'}, {code:'PVG',name:'Shanghai Pudong',group:'Shanghai'}, {code:'CAN',name:'Guangzhou',group:'Guangdong'} ];
          buildSearchable('from_search');
          buildSearchable('to_search');
        });
    })();

    // prepareSubmit: validate hidden inputs and date
    function prepareSubmit(){
      var date = document.getElementById('date');
      var email = document.getElementById('email');
      var fromHidden = document.querySelector('input[name=from]');
      var toHidden = document.querySelector('input[name=to]');
      if (!fromHidden.value || !toHidden.value || !email.checkValidity()){
        alert(window.I18N.alert_missing);
        return false;
      }
      var d = new Date(date.value);
      var today = new Date(); today.setHours(0,0,0,0);
      if (isNaN(d.getTime())){ alert(window.I18N.alert_invalid_date); return false; }
      if (d < today){ if(!confirm(window.I18N.confirm_past)) return false; }
      document.getElementById('submitBtn').disabled = true;
      document.getElementById('submitBtn').textContent = window.I18N.submitting;
      return true;
    }
  </script>
  <style>
    .searchable{position:relative}
    .searchable .sinput{width:100%;padding:10px;border:1px solid #e6eef8;border-radius:8px}
    .searchable .slist{position:absolute;left:0;right:0;top:48px;background:#fff;border:1px solid #e6eef8;border-radius:8px;max-height:260px;overflow:auto;box-shadow:0 6px 14px rgba(12,24,40,0.08);display:none;z-index:30}
    .searchable .gtitle{padding:8px 10px;background:#f7fbff;color:#2b5e9e;font-weight:600;font-size:13px}
    .searchable .item{padding:8px 10px;cursor:pointer}
    .searchable .item:hover{background:#f2f8ff}
    .searchable .none{padding:10px;color:#999}
  </style>
</body>
</html>
