export class ApiError extends Error {
  readonly status: number;
  readonly code?: string;
  readonly details?: Record<string, unknown>;
  readonly errors?: Record<string, string[]>;

  constructor(
    message: string,
    status: number,
    code?: string,
    details?: Record<string, unknown>,
    errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details;
    this.errors = errors;
  }
}

const API_BASE = '/TroyLeaveFiling/portal';
let redirecting = false;

function redirectToLogin(): void {
  if (redirecting) return;
  redirecting = true;

  const adminBase = '/TroyLeaveFiling/admin';
  const pathname = window.location.pathname;
  const relative = pathname.startsWith(adminBase) ? pathname.slice(adminBase.length) : '';
  const target = adminBase + (relative && relative !== '/' ? relative : '');

  window.location.replace(
    '/TroyLeaveFiling/index.php?reauth=1&return=' + encodeURIComponent(target),
  );
}

function buildUrl(
  path: string,
  params?: Record<string, string | number | boolean | undefined>,
): string {
  const url = API_BASE + path;
  if (!params) return url;

  const parts: string[] = [];
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== '') {
      parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
    }
  }

  return parts.length ? `${url}${url.includes('?') ? '&' : '?'}${parts.join('&')}` : url;
}

interface RequestOptions {
  json?: unknown;
  params?: Record<string, string | number | boolean | undefined>;
  signal?: AbortSignal;
}

async function request<T>(
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  options: RequestOptions = {},
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  let body: BodyInit | null = null;

  if (options.json !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(options.json);
  }

  let response: Response;

  try {
    response = await fetch(buildUrl(path, options.params), {
      method,
      headers,
      body,
      credentials: 'include',
      signal: options.signal,
    });
  } catch (error) {
    if (options.signal?.aborted) throw error;
    throw new ApiError('Cannot reach the server. Check your connection.', 0, 'network_error');
  }

  let payload: unknown = null;
  const contentType = response.headers.get('content-type') ?? '';

  if (contentType.includes('application/json')) {
    payload = await response.json().catch(() => null);
  }

  if (response.status === 401) {
    redirectToLogin();
    throw new ApiError('Your session expired. Please sign in again.', 401, 'unauthenticated');
  }

  if (!response.ok) {
    const json = (payload ?? {}) as {
      message?: string;
      code?: string;
      details?: Record<string, unknown>;
      errors?: Record<string, string[]>;
    };

    throw new ApiError(
      json.message ?? `Request failed (HTTP ${response.status}).`,
      response.status,
      json.code,
      json.details,
      json.errors,
    );
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return payload as T;
}

export const api = {
  get<T>(
    path: string,
    params?: Record<string, string | number | boolean | undefined>,
    signal?: AbortSignal,
  ): Promise<T> {
    return request<T>('GET', path, { params, signal });
  },
  post<T>(path: string, json?: unknown): Promise<T> {
    return request<T>('POST', path, { json });
  },
  patch<T>(path: string, json?: unknown): Promise<T> {
    return request<T>('PATCH', path, { json });
  },
  delete<T>(path: string): Promise<T> {
    return request<T>('DELETE', path);
  },
};
