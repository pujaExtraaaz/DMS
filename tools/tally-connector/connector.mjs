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

async function postToTally(cfg, xml) {
  const headers = { 'Content-Type': 'application/xml' };
  if (cfg.company) headers['X-Tally-Company'] = cfg.company;

  const res = await fetch(cfg.tally_url, {
    method: 'POST',
    headers,
    body: xml,
  });
  const text = await res.text();
  return { ok: res.ok, status: res.status, body: text };
}

function looksLikeTallyError(body) {
  if (!body) return false;
  const lower = body.toLowerCase();
  return lower.includes('<lineerror>') || lower.includes('unknown error') || lower.includes('does not exist');
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
      if (!tally.ok || looksLikeTallyError(tally.body)) {
        await api(cfg, 'POST', `${item.id}/result`, {
          status: 'failed',
          error: `Tally HTTP ${tally.status}`,
          response: tally.body,
        });
        console.log('FAILED');
      } else {
        await api(cfg, 'POST', `${item.id}/result`, { status: 'sent', response: tally.body });
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
