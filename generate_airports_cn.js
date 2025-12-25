const fs = require('fs');
const https = require('https');
const path = require('path');

const AIRPORTS_FILE = path.join(__dirname, 'airports_cn.json');
const OUT_FILE = path.join(__dirname, 'airports_cn_prov.json');
const WORLD_CITIES_CSV = 'https://raw.githubusercontent.com/datasets/world-cities/master/data/world-cities.csv';

function fetch(url) {
  return new Promise((resolve, reject) => {
    https.get(url, res => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        return resolve(fetch(res.headers.location));
      }
      let data = '';
      res.on('data', d => data += d);
      res.on('end', () => resolve(data));
      res.on('error', reject);
    }).on('error', reject);
  });
}

function normalize(s) {
  if (!s) return '';
  return s
    .toString()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/[^\p{L}\p{N}]/gu, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();
}

async function main() {
  if (!fs.existsSync(AIRPORTS_FILE)) {
    console.error('Missing', AIRPORTS_FILE);
    process.exit(1);
  }

  const airports = JSON.parse(fs.readFileSync(AIRPORTS_FILE, 'utf8'));
  console.log('Loaded', airports.length, 'airports');

  console.log('Downloading world-cities CSV...');
  const csv = await fetch(WORLD_CITIES_CSV);
  const lines = csv.split('\n');

  const cityToProv = new Map();
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    // split with simple logic: name,country,subcountry,geonameid
    const parts = line.split(',');
    if (parts.length < 3) continue;
    const name = parts[0].replace(/^"|"$/g, '').trim();
    const country = parts[1].replace(/^"|"$/g, '').trim();
    const subcountry = parts.slice(2, parts.length - 1).join(',').replace(/^"|"$/g, '').trim();
    if (country.toLowerCase() !== 'china') continue;
    const n = normalize(name);
    if (!cityToProv.has(n)) cityToProv.set(n, subcountry || 'Unknown');
  }

  console.log('Built city->province map entries:', cityToProv.size);

  // manual fallback mapping for major cities / IATA codes
  const manualIata = {
    PEK: 'Beijing',
    PKX: 'Beijing',
    PVG: 'Shanghai',
    SHA: 'Shanghai',
    CAN: 'Guangdong',
    SZX: 'Guangdong',
    CTU: 'Sichuan',
    XIY: 'Shaanxi',
    CKG: 'Chongqing',
    KMG: 'Yunnan',
    HGH: 'Zhejiang',
    NKG: 'Jiangsu',
    WUH: 'Hubei',
    XMN: 'Fujian',
    HRB: 'Heilongjiang',
    CGQ: 'Jilin',
    SHE: 'Liaoning'
  };

  const notMapped = [];
  const out = airports.map(a => {
    const provPrior = a.province || '';
    if (provPrior && provPrior.toLowerCase() !== 'china' && provPrior.toLowerCase() !== 'unknown') {
      return Object.assign({}, a, { province: provPrior });
    }

    const cityNorm = normalize(a.city || a.name || '');
    const nameNorm = normalize(a.name || '');

    let prov = null;
    if (cityNorm && cityToProv.has(cityNorm)) prov = cityToProv.get(cityNorm);
    if (!prov && nameNorm && cityToProv.has(nameNorm)) prov = cityToProv.get(nameNorm);

    if (!prov && cityNorm) {
      // try startsWith / includes heuristics
      for (const [k, v] of cityToProv.entries()) {
        if (k.startsWith(cityNorm) || k.includes(cityNorm) || cityNorm.includes(k)) {
          prov = v; break;
        }
      }
    }

    if (!prov && a.iata && manualIata[a.iata]) prov = manualIata[a.iata];

    if (!prov) {
      notMapped.push({ iata: a.iata, city: a.city, name: a.name });
      prov = 'Unknown';
    }

    return Object.assign({}, a, { province: prov });
  });

  // log small sample of unmapped (limit 30)
  if (notMapped.length) {
    console.log('Unmapped sample (<=30):', notMapped.slice(0, 30));
    console.log('Total unmapped:', notMapped.length);
  }

  fs.writeFileSync(OUT_FILE, JSON.stringify(out, null, 2), 'utf8');
  console.log('Wrote', OUT_FILE, 'with', out.length, 'entries');
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
