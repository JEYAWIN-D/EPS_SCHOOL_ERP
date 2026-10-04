import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// Custom Metrics for High-Concurrency Tracking
export const latencyUnder2ms = new Rate('latency_under_2ms');
export const errorRate = new Rate('errors');
export const throughput = new Counter('transactions_total');
export const transactionDuration = new Trend('transaction_duration_ms', true);

// 100 Concurrent Users High-Performance Profile
export const options = {
  scenarios: {
    // Sustained 100 Concurrent Users Spike & Soak
    high_concurrency_load: {
      executor: 'ramping-vus',
      startVUs: 10,
      stages: [
        { duration: '10s', target: 50 },  // Fast ramp-up to 50 VUs
        { duration: '20s', target: 100 }, // Full saturation at 100 VUs
        { duration: '30s', target: 100 }, // Steady state soak at 100 VUs
        { duration: '10s', target: 0 },   // Graceful cooldown
      ],
      gracefulRampDown: '5s',
    },
  },
  thresholds: {
    // Uncompromising SLA Targets:
    // 95% of cached transactions must execute under 2ms
    'http_req_duration{endpoint:cached}': ['p(95)<2', 'p(99)<5'],
    // General transactions must sustain sub-100ms over WAN / <10ms LAN
    'http_req_duration{endpoint:api}': ['p(95)<10', 'p(99)<25'],
    // Zero tolerance for dropped packets/errors under 100 concurrent users
    'errors': ['rate<0.001'], // < 0.1% error rate
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';

export default function () {
  const headers = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'User-Agent': 'k6-Concurrency-Engine/1.0',
    'Connection': 'keep-alive',
  };

  // 1. Hot-Path In-Memory Health / Settings Check (<2ms Target)
  {
    const res = http.get(`${BASE_URL}/up`, {
      headers,
      tags: { endpoint: 'cached' },
    });

    const isFast = res.timings.duration < 2.0;
    latencyUnder2ms.add(isFast);
    errorRate.add(res.status !== 200);
    throughput.add(1);
    transactionDuration.add(res.timings.duration);

    check(res, {
      'Cached ping status is 200': (r) => r.status === 200,
      'Latency is under 2ms': () => isFast,
    });
  }

  // 2. High-Frequency Dashboard Instant Pulse Metrics
  {
    const res = http.get(`${BASE_URL}/dashboard/widgets/preview?source=student_count`, {
      headers,
      tags: { endpoint: 'api' },
    });

    errorRate.add(res.status !== 200);
    throughput.add(1);
    transactionDuration.add(res.timings.duration);

    check(res, {
      'Pulse widget status 200': (r) => r.status === 200,
    });
  }

  // Realistic user pacing with microsecond precision
  sleep(0.05); // 50ms think-time for aggressive concurrency
}
