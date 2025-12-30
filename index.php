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
  <link rel="icon" href="/favicon.ico" />
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
    /* Ensure form columns align their label + input to the top */
    .row > div { display:flex; flex-direction:column; justify-content:flex-start; }
    .row > div label { margin-bottom:6px }
    .row > div .el-input, .row > div .el-date-editor, .row > div input, .row > div .searchable { margin-top:0 }
    /* normalize heights for Element inputs and native inputs so date and email align */
    .el-input, .el-date-editor { display:flex; align-items:center; }
    .el-input__inner, .el-date-editor .el-input__inner, input[type="email"], input[type="date"] {
      height:44px; padding:10px 12px; box-sizing:border-box; line-height:20px; display:block;
    }
    /* remove extra margins that shift date picker */
    .el-date-editor { margin-top:0; }
    /* ensure label spacing doesn't differ */
    .row > div label { margin-bottom:8px }
  </style>
  <!-- Element UI CSS (local) -->
  <link rel="stylesheet" href="/static/element-ui.css">
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

      <!-- Progressive-enhanced form: original native form kept for non-JS, Vue+Element UI will enhance when available -->
      <form id="subForm" action="subscribe.php" method="post">
        <div id="app-root">
          <!-- Vue/Element will mount here and render enhanced form; fallback native inputs below will be kept hidden when Vue initializes -->
        </div>

        <!-- Vue template moved out to avoid JS template literal parsing issues -->
        <script type="text/x-template" id="app-template">
          <div>
            <el-form :model="form" label-position="top">
              <div class="row" style="margin-bottom:12px">
                <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
                  <label><?php echo htmlspecialchars($t('from_label')) ?></label>
                  <el-select v-model="form.fromProv" placeholder="<?php echo $lang==='zh' ? '选择省份' : 'Select province' ?>" clearable>
                    <el-option v-for="p in provinces" :key="p.key" :label="p.label" :value="p.key"></el-option>
                  </el-select>
                  <el-select v-model="form.from" filterable :placeholder="form.fromProv ? '<?php echo htmlspecialchars($t('search_placeholder')) ?>' : '<?php echo htmlspecialchars($t('from_hint')) ?>'" clearable :disabled="!form.fromProv" style="margin-top:8px">
                    <el-option v-for="opt in (airportMap[form.fromProv] || [])" :key="opt.code" :label="opt.code + ' — ' + (opt.displayName || opt.name)" :value="opt.code"></el-option>
                  </el-select>
                  <div class="small hint"><?php echo htmlspecialchars($t('from_hint')) ?></div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
                  <label><?php echo htmlspecialchars($t('to_label')) ?></label>
                  <el-select v-model="form.toProv" placeholder="<?php echo $lang==='zh' ? '选择省份' : 'Select province' ?>" clearable>
                    <el-option v-for="p in provinces" :key="p.key+'-to'" :label="p.label" :value="p.key"></el-option>
                  </el-select>
                  <el-select v-model="form.to" filterable :placeholder="form.toProv ? '<?php echo htmlspecialchars($t('search_placeholder')) ?>' : '<?php echo htmlspecialchars($t('from_hint')) ?>'" clearable :disabled="!form.toProv" style="margin-top:8px">
                    <el-option v-for="opt in (airportMap[form.toProv] || [])" :key="opt.code+'-to'" :label="opt.code + ' — ' + (opt.displayName || opt.name)" :value="opt.code"></el-option>
                  </el-select>
                </div>
              </div>
              <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
                  <label><?php echo htmlspecialchars($t('date_label')) ?></label>
                  <el-date-picker v-model="form.date" type="date" placeholder="<?php echo htmlspecialchars($t('date_hint')) ?>" value-format="yyyy-MM-dd" :picker-options="datePickerOptions"></el-date-picker>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
                  <div style="max-width:280px;min-width:160px">
                    <label><?php echo htmlspecialchars($t('email_label')) ?></label>
                    <el-input v-model="form.email" type="email" placeholder="you@example.com"></el-input>
                  </div>
                </div>
              </div>
              <div class="actions" style="margin-top:14px">
                <el-button type="primary" :loading="submitting" @click.native.prevent="onSubmit"><?php echo htmlspecialchars($t('subscribe_btn')) ?></el-button>
                <el-button @click.native.prevent="onReset" type="warning"><?php echo htmlspecialchars($t('reset_btn')) ?></el-button>
              </div>
              <!-- hidden native inputs to be submitted to server -->
              <input type="hidden" name="fromProv" :value="form.fromProv">
              <input type="hidden" name="toProv" :value="form.toProv">
              <input type="hidden" name="from" :value="form.from">
              <input type="hidden" name="to" :value="form.to">
              <input type="hidden" name="date" :value="form.date">
              <input type="hidden" name="email" :value="form.email">
            </el-form>
          </div>
        </script>

        <!-- Fallback native inputs (kept for non-JS or server-side fallback) -->
        <div id="native-fallback">
            <div class="row" style="margin-bottom:12px">
            <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
              <label for="from_search"><?php echo htmlspecialchars($t('from_label')) ?></label>
              <div class="searchable" id="from_search" data-name="from"></div>
              <div class="small hint"><?php echo htmlspecialchars($t('from_hint')) ?></div>
            </div>
            <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
              <label for="to_search"><?php echo htmlspecialchars($t('to_label')) ?></label>
              <div class="searchable" id="to_search" data-name="to"></div>
            </div>
          </div>

          <div style="display:flex;gap:12px;align-items:flex-start">
              <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
              <label for="date"><?php echo htmlspecialchars($t('date_label')) ?></label>
              <input id="date" name="date" type="date" required min="<?php echo date('Y-m-d'); ?>">
              <div class="small hint"><?php echo htmlspecialchars($t('date_hint')) ?></div>
            </div>

            <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-start">
              <div style="max-width:280px;min-width:160px">
                <label for="email"><?php echo htmlspecialchars($t('email_label')) ?></label>
                <input id="email" name="email" type="email" placeholder="you@example.com" required>
              </div>
            </div>
          </div>

          <div class="actions">
            <button id="submitBtn" type="submit"><?php echo htmlspecialchars($t('subscribe_btn')) ?></button>
            <button type="button" onclick="document.getElementById('subForm').reset();">Reset</button>
          </div>
        </div>

      </form>

      <div class="foot">
        <div><?php echo htmlspecialchars($t('note')) ?></div>
      </div>
    </div>
  </div>

  <!-- Vue and Element UI (progressive enhancement) -->
  <script src="/static/vue.min.js"></script>
  <script src="/static/element-ui.js"></script>
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

    // app language
    window.APP_LANG = <?php echo json_encode($lang) ?>;

    // Chinese translations for province names (fallback to original if missing)
    window.PROVINCE_CN = {
      "Guangdong":"广东","Tibet":"西藏","Guizhou":"贵州","Guangxi":"广西","Inner Mongolia":"内蒙古","Xinjiang":"新疆","Shaanxi":"陕西","Henan":"河南","Hunan":"湖南","Hubei":"湖北","Hebei":"河北","Liaoning":"辽宁","Jiangsu":"江苏","Zhejiang":"浙江","Shanghai":"上海","Beijing":"北京","Tianjin":"天津","Chongqing":"重庆","Sichuan":"四川","Yunnan":"云南","Fujian":"福建","Shandong":"山东","Jilin":"吉林","Heilongjiang":"黑龙江","Anhui":"安徽","Shanxi":"山西","Gansu":"甘肃","Qinghai":"青海","Ningxia":"宁夏","Hainan":"海南","Taiwan":"台湾","Hong Kong":"香港","Macau":"澳门","Others":"其他","Jiangxi":"江西"
    };

    (function(){
      // build a province->airports map and a provinces list for selects
      function buildProvinceMap(data){
        var items = data.map(function(a){
          var prov = (a.province && a.province.trim()) ? a.province.trim() : '';
          var province = prov || (a.city && a.city.trim()) || a.country || 'Others';
          return { code: a.iata, name: a.name || a.city || a.iata, province: province };
        });

        var map = {};
        items.forEach(function(it){
          if(!map[it.province]) map[it.province] = [];
          map[it.province].push(it);
        });

        var provinces = Object.keys(map).sort(function(a,b){
          if(a==='Others') return 1;
          if(b==='Others') return -1;
          return a.localeCompare(b,'zh-CN');
        });

        var display = provinces.map(function(p){
          return { key: p, label: (window.APP_LANG==='zh' ? (window.PROVINCE_CN[p] || p) : p) };
        });

        return { provinces: display, map: map };
      }

      function loadAirportData(){
        return fetch('airports_cn_prov.json')
          .then(function(r){ if(r.ok) return r.json(); return fetch('airports_cn.json').then(function(r2){ if(!r2.ok) throw new Error('both fetch failed'); return r2.json(); }); })
          .catch(function(){ return [ {code:'PEK',name:'Beijing Capital',province:'Beijing'}, {code:'PVG',name:'Shanghai Pudong',province:'Shanghai'}, {code:'CAN',name:'Guangzhou',province:'Guangdong'} ]; });
      }

      loadAirportData().then(function(data){
        // optional IATA->Chinese map
        fetch('iata_cn_map.json').then(function(r){ if(r.ok) return r.json(); return {}; }).catch(function(){ return {}; }).then(function(iataMap){
          var pm = buildProvinceMap(data);
          Object.keys(pm.map).forEach(function(prov){
            pm.map[prov] = pm.map[prov].map(function(a){
              var iataKey = a.iata || a.code || a.IATA || a.Iata;
              var nameCn = a.name_cn || a.cn_name || (iataMap && (iataMap[iataKey] || iataMap[a.code]));
              var display = (window.APP_LANG==='zh' && nameCn) ? nameCn : (a.name || a.city || a.code || iataKey);
              var copy = Object.assign({}, a);
              copy.displayName = display;
              return copy;
            });
          });

          try{
            new Vue({
              el: '#app-root',
              data: function(){
                return {
                  form: { fromProv:'', toProv:'', from:'', to:'', date:'', email:'' },
                  provinces: pm.provinces,
                  airportMap: pm.map,
                  submitting: false,
                  datePickerOptions: {
                    disabledDate: function(time){
                      var today = new Date(); today.setHours(0,0,0,0); return time.getTime() < today.getTime();
                    }
                  }
                };
              },
              template: '#app-template',
              methods: {
                onReset: function(){ this.form.fromProv=''; this.form.toProv=''; this.form.from=''; this.form.to=''; this.form.date=''; this.form.email=''; },
                onSubmit: function(){
                  if(!this.form.from || !this.form.to || !this.form.email){ this.$alert(window.I18N.alert_missing); return; }
                  if(!this.form.date){ this.$alert(window.I18N.alert_invalid_date); return; }
                  var d = new Date(this.form.date); var today = new Date(); today.setHours(0,0,0,0);
                  if(d < today){
                    var self = this;
                    this.$confirm(window.I18N.confirm_past, '', { type: 'warning' }).then(function(){ self.submitNative(); }).catch(function(){});
                    return;
                  }
                  this.submitNative();
                },
                submitNative: function(){ var self=this; this.submitting=true; setTimeout(function(){ document.getElementById('subForm').submit(); }, 150); }
              },
              mounted: function(){ var nf=document.getElementById('native-fallback'); if(nf) nf.style.display='none'; }
            });
          }catch(e){ console.error('Vue init error', e); throw e; }

        }).catch(function(err){ console.error('Vue mount failed', err); });
      }).catch(function(err){ console.error('loadAirportData failed', err); });

    })();
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
