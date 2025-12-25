<?php
// Styled subscription form
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Flight Price Monitor — Subscribe</title>
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
      <h1>Flight Price Monitor</h1>
      <p class="lead">Enter route, date and email — we will check hourly and send sorted prices to your inbox.</p>

      <form id="subForm" action="subscribe.php" method="post" onsubmit="return prepareSubmit()">
        <div class="row" style="margin-bottom:12px">
          <div style="flex:1">
            <label for="from_search">From City / Airport (grouped by province)</label>
            <div class="searchable" id="from_search" data-name="from"></div>
            <div class="small hint">Lists major airports in Mainland China, Hong Kong, Macau and Taiwan. Search by name or code.</div>
          </div>
          <div style="flex:1">
            <label for="to_search">To City / Airport (grouped by province)</label>
            <div class="searchable" id="to_search" data-name="to"></div>
          </div>
        </div>

        <div style="display:flex;gap:12px;align-items:flex-end">
          <div style="flex:1">
            <label for="date">Departure Date</label>
            <input id="date" name="date" type="date" required>
            <div class="small hint">Please select a future departure date.</div>
          </div>

          <div style="width:280px;min-width:160px">
            <label for="email">Recipient Email</label>
            <input id="email" name="email" type="email" placeholder="you@example.com" required>
          </div>
        </div>

        <div class="actions">
          <button id="submitBtn" type="submit">Subscribe (hourly checks)</button>
          <button type="button" onclick="document.getElementById('subForm').reset();">Reset</button>
        </div>
      </form>

      <div class="foot">
        <div>Note: subscriptions are stored in a local SQLite database. Configure SMTP on the server to enable email delivery (see README).</div>
      </div>
    </div>
  </div>

  <script>
    // Dynamically load airports_cn.json (includes Mainland China, Hong Kong, Macau, Taiwan)
    // and initialize the searchable dropdowns
    let AIRPORTS = [];

    function buildSearchable(id){
      const container = document.getElementById(id);
      const name = container.dataset.name || id;
      // visible input, hidden input for form submit, dropdown
      const vis = document.createElement('input');
      vis.type = 'text'; vis.placeholder = 'Search by airport name or IATA code'; vis.className='sinput';
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
        if(matched.length===0){ const none=document.createElement('div'); none.className='none'; none.textContent='No matches found'; list.appendChild(none); }
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

    // After loading airports_cn.json, initialize the search components
    (function loadAirports(){
      // Prefer corrected province file; fallback to original airports_cn.json if not present
      fetch('airports_cn_prov.json')
        .then(r=>{
          if(r.ok) return r.json();
          return fetch('airports_cn.json').then(r2=>{ if(!r2.ok) throw new Error('both fetch failed'); return r2.json(); });
        })
        .then(data=>{
          // data: [{iata,name,city,country,lat,lon,province},...]
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
        alert('Please select departure/arrival airports and enter a valid email.');
        return false;
      }
      var d = new Date(date.value);
      var today = new Date(); today.setHours(0,0,0,0);
      if (isNaN(d.getTime())){ alert('Please select a valid departure date.'); return false; }
      if (d < today){ if(!confirm('You selected a past date. Continue?')) return false; }
      document.getElementById('submitBtn').disabled = true;
      document.getElementById('submitBtn').textContent = 'Submitting...';
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
  </script>
</body>
</html>
