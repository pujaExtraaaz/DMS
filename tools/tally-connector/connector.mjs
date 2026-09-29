#!/usr/bin/env node
/**
 * DMS Tally Connector (office bridge)
 * Pulls pending XML from DMS and POSTs it to local Tally HTTP server.
 * Requires Node.js 18+ (uses built-in fetch). No npm install needed.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const configPath = path.join(__dirname, 'config.json');

function loadConfig() {
  if (!fs.existsSync(configPath)) {
    console.error('Missing config.json — copy config.example.json to config.json and edit it.');
    process.exit(1);
  }
  const cfg = JSON.parse(fs.readFileSync(configPath, 'utf8'));
  if (!cfg.dms_url || !cfg.connector_token || !cfg.tally_url) {
    console.error('config.json must include dms_url, connector_token, tally_url');
    process.exit(1);
  }
  cfg.dms_url = String(cfg.dms_url).replace(/\/$/, '');
  cfg.poll_seconds = Number(cfg.poll_seconds || 15);
  cfg.batch_size = Number(cfg.batch_size || 10);
  return cfg;
}

async function api(cfg, method, route, body) {
  const res = await fetch(`${cfg.dms_url}/api/tally-connector/${route}`, {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${cfg.connector_token}`,
      'X-Tally-Connector-Token': cfg.connector_token,
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  const text = await res.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch {
    throw new Error(`DMS ${res.status}: ${text.slice(0, 300)}`);
  }
  if (!res.ok || json.ok === false) {
    throw new Error(json.message || `DMS HTTP ${res.status}`);
  }
  return json;
}

function withCompany(cfg, xml) {
  if (!cfg.company) return xml;
  const company = String(cfg.company)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  return xml
    .replace(/<SVCURRENTCOMPANY\s*\/>/i, `<SVCURRENTCOMPANY>${company}</SVCURRENTCOMPANY>`)
    .replace(/<SVCURRENTCOMPANY>\s*<\/SVCURRENTCOMPANY>/i, `<SVCURRENTCOMPANY>${company}</SVCURRENTCOMPANY>`);
}

async function postToTally(cfg, xml) {
  const res = await fetch(cfg.tally_url, {
    method: 'POST',
    headers: { 'Content-Type': 'text/xml; charset=utf-8' },
    body: withCompany(cfg, xml),
  });
  const text = await res.text();
  return { ok: res.ok, status: res.status, body: text };
}

function decodeXml(s) {
  return s
    .replace(/&apos;/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&amp;/g, '&');
}

function tallyCount(body, tag) {
  const m = body.match(new RegExp(`<${tag}>\\s*(-?\\d+)\\s*</${tag}>`, 'i'));
  return m ? Number(m[1]) : 0;
}

const MASTER_TYPES = new Set(['product', 'customer', 'uom', 'godown']);

/**
 * Tally answers HTTP 200 even when it rejects the data, so success has to be
 * read from the CREATED/ALTERED/ERRORS/EXCEPTIONS counters in the body.
 * Returns null on success, or a human readable reason on failure.
 */
function tallyFailureReason(item, tally) {
  if (!tally.ok) return `Tally HTTP ${tally.status}`;
  const body = tally.body || '';
  const lineErrors = [...body.matchAll(/<LINEERROR>([\s\S]*?)<\/LINEERROR>/gi)].map((m) => decodeXml(m[1].trim()));

  // Re-sending a master that already exists in Tally is fine — it is already there.
  if (MASTER_TYPES.has(item.document_type) && lineErrors.length && lineErrors.every((e) => /already exists/i.test(e))) {
    return null;
  }
  if (lineErrors.length) return lineErrors.join(' | ');

  const created = tallyCount(body, 'CREATED');
  const altered = tallyCount(body, 'ALTERED');
  const combined = tallyCount(body, 'COMBINED');
  const errors = tallyCount(body, 'ERRORS');
  const exceptions = tallyCount(body, 'EXCEPTIONS');

  if (errors > 0 || exceptions > 0) {
    return 'Tally rejected the voucher without a line error (EXCEPTIONS/ERRORS). '
      + 'Usually: voucher does not balance, a ledger/stock item/unit/godown is missing, '
      + 'the date is outside the company financial year, or the company is not open.';
  }
  if (created + altered + combined === 0 && /<RESPONSE>|<IMPORTRESULT>/i.test(body)) {
    return 'Tally imported nothing (CREATED=0, ALTERED=0).';
  }
  if (/unknown request|could not find company|no company/i.test(body)) {
    return decodeXml(body.slice(0, 500));
  }
  return null;
}

async function syncOnce(cfg) {
  const pending = await api(cfg, 'GET', `pending?limit=${cfg.batch_size}`);
  if (!pending.count) {
    console.log(`[${new Date().toISOString()}] No pending items`);
    return 0;
  }

  let done = 0;
  for (const item of pending.items) {
    process.stdout.write(`#${item.id} ${item.document_type} ... `);
    try {
      const tally = await postToTally(cfg, item.payload || '');
      const reason = tallyFailureReason(item, tally);
      if (reason) {
        await api(cfg, 'POST', `${item.id}/result`, {
          status: 'failed',
          error: reason.slice(0, 2000),
          response: (tally.body || '').slice(0, 20000),
        });
        console.log(`FAILED — ${reason}`);
      } else {
        await api(cfg, 'POST', `${item.id}/result`, { status: 'sent', response: (tally.body || '').slice(0, 20000) });
        console.log('SENT');
        done += 1;
      }
    } catch (err) {
      await api(cfg, 'POST', `${item.id}/result`, {
        status: 'failed',
        error: err.message || String(err),
      }).catch(() => {});
      console.log('ERROR', err.message || err);
    }
  }
  return done;
}

async function main() {
  const cfg = loadConfig();
  console.log('DMS Tally Connector');
  console.log(`  DMS:   ${cfg.dms_url}`);
  console.log(`  Tally: ${cfg.tally_url}`);
  console.log(`  Poll:  every ${cfg.poll_seconds}s`);

  try {
    const health = await api(cfg, 'GET', 'health');
    console.log(`  Health OK — pending on server: ${health.pending}`);
  } catch (err) {
    console.error('Cannot reach DMS API:', err.message || err);
    console.error('Check dms_url + connector_token, and that TALLY_USE_CONNECTOR=true on server.');
    process.exit(1);
  }

  // eslint-disable-next-line no-constant-condition
  while (true) {
    try {
      await syncOnce(cfg);
    } catch (err) {
      console.error('Sync cycle error:', err.message || err);
    }
    await new Promise((r) => setTimeout(r, cfg.poll_seconds * 1000));
  }
}

main();
