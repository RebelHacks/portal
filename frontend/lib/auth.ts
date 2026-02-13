/** Decode JWT payload to get current user email */
export function getJwtEmail(): string | null {
  if (typeof window === 'undefined') return null;
  const token = localStorage.getItem('authToken');
  if (!token) return null;
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.username ?? payload.email ?? null;
  } catch {
    return null;
  }
}
