import http from 'k6/http';
import { check, sleep } from 'k6';

// Konfigurasi test
export const options = {
  stages: [
    { duration: '30s', target: 20 },  // Ramp-up: 20 pengguna dalam 30 detik
    { duration: '1m', target: 50 },   // Pertahankan 50 pengguna selama 1 menit
    { duration: '30s', target: 0 },   // Ramp-down: turunkan ke 0 pengguna
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],   // Gagal jika error rate > 1%
    http_req_duration: ['p(95)<500'], // 95% request harus selesai dalam <500ms
  },
};

// Simulasi pengguna
export default function () {
  const res = http.get('http://localhost/web_rumah_sakit/'); // Ganti port sesuai aplikasi Anda

  // Verifikasi response
  check(res, {
    'Status 200': (r) => r.status === 200,
    'Homepage loaded': (r) => r.body.includes('RSU El-Syifa Kuningan'), // Ganti dengan teks unik di halaman Anda
  });

  sleep(1); // Jeda 1 detik antar request
}