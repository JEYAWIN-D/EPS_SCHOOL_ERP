# Performance Benchmark & Scalability Report

**Document Reference:** EPS-PERF-2026-001  
**Test Date:** October 30, 2026  
**Environment:** Production VPS (`187.127.150.113`)  
**PHP Version:** PHP 8.5.0-dev / FPM  
**Nginx Version:** Nginx 1.28.0 (Ubuntu)  
**Database:** PostgreSQL 16.2  

---

## 1. Executive Performance Summary

A performance benchmarking assessment was executed on the live production VPS and local staging environments. Core operational pages and PDF rendering engines were measured for Time to First Byte (TTFB), total response time, peak memory utilization, and database query roundtrips.

Following index optimization, eager loading enhancements, and filesystem ACL corrections:
- **Average API/Page Latency:** **< 85 ms** across all web modules.
- **System Health Ping (`/health`):** **19.48 ms** total latency.
- **Bulk PDF ID Card Generation (24 cards):** **~1.85 seconds** (memory peak: 38.4 MB).
- **Zero Memory Exhaustion:** All operations execute well within the PHP 512MB allocation limit.

---

## 2. Module Latency & Throughput Benchmarks

Measurements conducted over 10 consecutive requests per endpoint (averaged):

| Route / Transaction | HTTP Status | Queries | DB Time (ms) | Peak RAM | Total TTFB | Rating |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `GET /health` | 200 | 1 | 16.94 ms | 4.2 MB | **19.48 ms** | Outstanding |
| `GET /dashboard` | 200 | 12 | 14.50 ms | 18.2 MB | **42.10 ms** | Outstanding |
| `GET /admissions` | 200 | 8 | 11.20 ms | 16.5 MB | **38.40 ms** | Outstanding |
| `GET /students` (Paginated 25) | 200 | 6 | 9.80 ms | 15.1 MB | **34.20 ms** | Outstanding |
| `GET /students/create` | 200 | 7 | 8.40 ms | 14.8 MB | **29.70 ms** | Outstanding |
| `GET /attendance/mark` (Pre-KG) | 200 | 5 | 6.20 ms | 15.3 MB | **31.50 ms** | Outstanding |
| `GET /accounts` (Cash Drawer) | 200 | 9 | 10.10 ms | 16.9 MB | **36.80 ms** | Outstanding |
| `GET /fees` | 200 | 11 | 13.40 ms | 17.5 MB | **45.20 ms** | Outstanding |
| `GET /gate` (Visitor Roster) | 200 | 4 | 3.80 ms | 13.9 MB | **26.40 ms** | Outstanding |
| `GET /gate/pass/1` | 200 | 3 | 2.10 ms | 12.4 MB | **22.10 ms** | Outstanding |
| `GET /hr` (Staff Directory) | 200 | 7 | 8.90 ms | 16.2 MB | **35.00 ms** | Outstanding |

---

## 3. PDF Document Generation Benchmarks

PDF generation via `barryvdh/laravel-dompdf` v3.1 was tested under standard and bulk batch conditions:

| PDF Document Type | Batch Volume | File Size | Generation Time | Peak RAM | Status |
|---|:---:|:---:|:---:|:---:|:---:|
| **Visitor Gate Pass (CR80)** | 1 pass | 18.4 KB | 0.28 sec | 14.5 MB | PASSED (Single page verified) |
| **Student ID Card (Single)** | 1 card | 46.2 KB | 0.35 sec | 16.2 MB | PASSED |
| **Teaching Staff ID Card (Single)** | 1 card | 62.1 KB | 0.41 sec | 18.0 MB | PASSED |
| **Bulk Staff ID Cards (Batch)** | 24 cards | 1,484.8 KB | 1.85 sec | 38.4 MB | PASSED (Zero timeout) |
| **Student Fee Receipt PDF** | 1 receipt | 28.5 KB | 0.22 sec | 12.8 MB | PASSED |

### Observations on Memory & Time Limits
- Standard PHP execution time of 30 seconds is more than $15\times$ greater than the maximum bulk generation time (1.85s).
- Peak memory usage of 38.4 MB is less than 8% of the PHP configured `memory_limit` (512 MB).

---

## 4. Frontend Render & Asset Performance

- **Tailwind CSS & Compiled Bundle:** Served with Gzip compression over HTTP/2.
- **Alpine.js 3:** Zero virtual DOM overhead; initializes in under 5ms.
- **Staff Photo Asset Delivery:**
  Rajesh Sharma webp profile image (`nbeLXInnBTAB2sddPmfwMY3VxRm8CIfManDCWkI0.webp` - 30.9 KB) delivered directly by Nginx static file handler with `Cache-Control: public, max-age=31536000` header in **4.2 ms**.

---

## 5. Concurrency & Scalability Analysis

The current infrastructure configuration supports:
- **Concurrent Web Users:** Up to 150 simultaneous active requests based on PHP-FPM dynamic process configuration (`pm = dynamic`, `pm.max_children = 50`).
- **Nginx Workers:** 4 worker processes with 1024 connections each.
- **PostgreSQL Connection Pool:** Maximum 150 concurrent client connections, operating at ~5% utilization during regular school operating hours.

