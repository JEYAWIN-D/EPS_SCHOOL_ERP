import http from 'node:http';

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const TOTAL_REQUESTS = 100;
const CONCURRENCY = 100;

console.log('══════════════════════════════════════════════════════════════════');
console.log(`  HIGH-PERFORMANCE LOAD BENCHMARK — SCHOOL ERP`);
console.log(`  Target: 100 Concurrent Users • Response SLA: <2ms (In-Memory)`);
console.log(`  Endpoint: ${BASE_URL}/up`);
console.log(`  Concurrency: ${CONCURRENCY} simultaneous connections`);
console.log('══════════════════════════════════════════════════════════════════\n');

// HTTP Agent with keep-alive to simulate persistent connections
const agent = new http.Agent({
  keepAlive: true,
  maxSockets: 150,
  maxFreeSockets: 50,
});

function sendRequest(id) {
  return new Promise((resolve) => {
    const start = performance.now();
    const req = http.get(`${BASE_URL}/up`, { agent }, (res) => {
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        const duration = performance.now() - start;
        resolve({ id, status: res.statusCode, duration, error: null });
      });
    });

    req.on('error', (err) => {
      const duration = performance.now() - start;
      resolve({ id, status: 0, duration, error: err.message });
    });

    req.setTimeout(10000, () => {
      req.destroy();
      const duration = performance.now() - start;
      resolve({ id, status: 504, duration, error: 'Timeout' });
    });
  });
}

async function runLoadTest() {
  console.log(`Launching ${TOTAL_REQUESTS} simultaneous requests across ${CONCURRENCY} concurrent workers...`);
  const testStart = performance.now();

  // Fire all 100 concurrent requests simultaneously
  const promises = [];
  for (let i = 1; i <= TOTAL_REQUESTS; i++) {
    promises.push(sendRequest(i));
  }

  const results = await Promise.all(promises);
  const totalTestDuration = performance.now() - testStart;

  // Analysis
  const latencies = results.map(r => r.duration).sort((a, b) => a - b);
  const successes = results.filter(r => r.status === 200).length;
  const errors = results.filter(r => r.status !== 200).length;

  const min = latencies[0];
  const max = latencies[latencies.length - 1];
  const sum = latencies.reduce((a, b) => a + b, 0);
  const mean = sum / latencies.length;
  const p50 = latencies[Math.floor(latencies.length * 0.50)];
  const p75 = latencies[Math.floor(latencies.length * 0.75)];
  const p90 = latencies[Math.floor(latencies.length * 0.90)];
  const p95 = latencies[Math.floor(latencies.length * 0.95)];
  const p99 = latencies[Math.floor(latencies.length * 0.99)];

  const under2msCount = latencies.filter(l => l <= 2.0).length;
  const under10msCount = latencies.filter(l => l <= 10.0).length;

  console.log('\n📊 BENCHMARK RESULTS');
  console.log('──────────────────────────────────────────────────────────────────');
  console.log(`Total Requests:             ${TOTAL_REQUESTS}`);
  console.log(`Successful (200 OK):        ${successes} (${((successes/TOTAL_REQUESTS)*100).toFixed(1)}%)`);
  console.log(`Errors / Dropped:           ${errors}`);
  console.log(`Total Elapsed Time:         ${totalTestDuration.toFixed(2)} ms`);
  console.log(`Throughput:                 ${((TOTAL_REQUESTS / totalTestDuration) * 1000).toFixed(1)} req/sec`);
  console.log('──────────────────────────────────────────────────────────────────');
  console.log(`Fastest (Min):              ${min.toFixed(2)} ms`);
  console.log(`50th Percentile (p50):      ${p50.toFixed(2)} ms`);
  console.log(`75th Percentile (p75):      ${p75.toFixed(2)} ms`);
  console.log(`90th Percentile (p90):      ${p90.toFixed(2)} ms`);
  console.log(`95th Percentile (p95):      ${p95.toFixed(2)} ms`);
  console.log(`99th Percentile (p99):      ${p99.toFixed(2)} ms`);
  console.log(`Slowest (Max):              ${max.toFixed(2)} ms`);
  console.log(`Average (Mean):             ${mean.toFixed(2)} ms`);
  console.log('──────────────────────────────────────────────────────────────────');
  console.log(`Transactions <= 2.0 ms:     ${under2msCount} / ${TOTAL_REQUESTS}`);
  console.log(`Transactions <= 10.0 ms:    ${under10msCount} / ${TOTAL_REQUESTS}`);
  console.log('══════════════════════════════════════════════════════════════════\n');

  if (errors === 0) {
    console.log('✅ PASS: Sustained 100 concurrent user requests with ZERO dropped packets.');
  } else {
    console.log('⚠️ WARNING: Some requests were dropped or timed out.');
  }
}

runLoadTest();
