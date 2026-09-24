import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import { FormData } from 'https://jslib.k6.io/formdata/0.0.2/index.js';

export const options = {
  stages: [
    { duration: '10s', target: 5 },  // s
    { duration: '20s', target: 20 }, // primary phase
    { duration: '5s', target: 0 },   // completion
  ],
  thresholds: {
    http_req_failed: ['rate<0.01'],   // less 1% errors
    http_req_duration: ['p(95)<250'], // 95% requests < 250ms
    'checks': ['rate>0.99'],          // 99%+ all checks is success
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

// sample file for tests
const imgBytes = open('./test_image.png', 'b');

export default function () {
  // unique email for each user
  const uniqueId = `${__VU}_${__ITER}_${randomString(6)}`;
  const userPayload = JSON.stringify({
    name: `Test User ${uniqueId}`,
    email: `user_${uniqueId}@example.com`,
    password: 'Password123!',
    password_confirmation: 'Password123!',
  });

  const jsonHeaders = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  };

  let token = null;

  // 1. Register
  group('Auth: Register', function () {
    const res = http.post(`${BASE_URL}/api/register`, userPayload, jsonHeaders);

    const success = check(res, {
      'register status is 201 or 200': (r) => r.status === 201 || r.status === 200,
      'register response has token or success': (r) => {
        try {
          const body = r.json();
          token = body.token || (body.data && body.data.token);
          return !!token;
        } catch (e) {
          return false;
        }
      },
    });

    if (!success) {
      console.error(`Register failed: ${res.status} ${res.body}`);
    }
  });

  sleep(1);

  // 2. Login
  if (!token) {
    group('Auth: Login', function () {
      const loginPayload = JSON.stringify({
        email: `user_${uniqueId}@example.com`,
        password: 'Password123!',
      });

      const res = http.post(`${BASE_URL}/api/login`, loginPayload, jsonHeaders);

      check(res, {
        'login status is 200': (r) => r.status === 200,
        'login returning token': (r) => {
          try {
            const body = r.json();
            token = body.token || body.access_token;
            return !!token;
          } catch (e) {
            return false;
          }
        },
      });
    });

    sleep(1);
  }

  // token headers
  const authHeaders = {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  };

  // 3. Protected: User profile
  group('User Profile', function () {
    if (!token) return;

    const res = http.get(`${BASE_URL}/api/user`, authHeaders);

    const isOk = check(res, {
      'profile status is 200': (r) => r.status === 200,
      'profile email matches': (r) => {
        try {
          const body = r.json();
          // check direct structure and wrapped in data object
          const email = body.email || (body.data && body.data.email);
          return email === `user_${uniqueId}@example.com`;
        } catch (e) {
          return false;
        }
      },
    });

    if (!isOk) {
      console.log(`Profile Failed Status: ${res.status}, Body: ${res.body}`);
    }
  });
  sleep(1);

  // 4. Protected: Multipart Upload
  group('Protected Upload', function () {
    if (!token) return;

    const fd = new FormData();
    fd.append('image', http.file(imgBytes, 'test_image.png', 'image/png'));

    const uploadParams = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'multipart/form-data; boundary=' + fd.boundary,
        'Accept': 'application/json',
      },
    };

    const res = http.post(`${BASE_URL}/api/images`, fd.body(), uploadParams);

    check(res, {
      'upload status is 201 or 200': (r) => r.status === 201 || r.status === 200,
      'upload returns file metadata': (r) => {
        try {
          const json = r.json();
          return json.id !== undefined || (json.data && json.data.id !== undefined);
        } catch (e) {
          return false;
        }
      },
    });
  });

  // 5. Logout / Token Invalidation
  group('Auth: Logout', function () {
    if (!token) return;

    const res = http.post(`${BASE_URL}/api/logout`, null, authHeaders);

    check(res, {
      'logout status is 200': (r) => r.status === 200,
    });
  });

  sleep(1);
}
