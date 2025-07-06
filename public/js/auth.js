const API_BASE = '/api';

document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = document.getElementById('name').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  const res = await fetch(`${API_BASE}/register`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, email, password })
  });

  const data = await res.json();
  alert(data.message || JSON.stringify(data));
});

document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;

  const res = await fetch(`${API_BASE}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });

  const data = await res.json();
  if (data.token) {
    localStorage.setItem('token', data.token);
    window.location.href = 'profile.html';
  } else {
    alert(data.error || 'Login failed');
  }
});

async function getProfile() {
  const token = localStorage.getItem('token');
    if (!token) {
    alert('Not logged in');
    window.location.href = 'login.html';
    return;
    }


  const res = await fetch(`${API_BASE}/profile`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await res.json();
  if (data.name) {
    document.getElementById('profileData').innerHTML = `
      <p>Name: ${data.name}</p>
      <p>Email: ${data.email}</p>
    `;
  } else {
    alert('Session expired');
    window.location.href = 'login.html';
  }
}

async function logout() {
  const token = localStorage.getItem('token');
  if (!token) return;

  await fetch(`${API_BASE}/logout`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  localStorage.removeItem('token');
  window.location.href = 'login.html';
}
