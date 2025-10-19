/**
 * API Service - Frontend
 * Sostituisce i mock con chiamate reali al backend PHP
 */

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api';

class ApiService {
  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  setToken(token: string) {
    this.token = token;
    localStorage.setItem('auth_token', token);
  }

  clearToken() {
    this.token = null;
    localStorage.removeItem('auth_token');
  }

  async request(endpoint: string, options: RequestInit = {}) {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    if (response.status === 204) {
      return null;
    }

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error?.message || 'Si è verificato un errore');
    }

    return data.data;
  }

  // Auth
  async register(email: string, password: string, passwordConfirm: string) {
    const data = await this.request('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, password, password_confirm: passwordConfirm }),
    });
    
    if (data.token) {
      this.setToken(data.token);
    }
    
    return data;
  }

  async login(email: string, password: string) {
    const data = await this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    
    if (data.token) {
      this.setToken(data.token);
    }
    
    return data;
  }

  async getCurrentUser() {
    return await this.request('/auth/me');
  }

  logout() {
    this.clearToken();
  }

  // Events
  async getEvents(filters?: {
    from?: string;
    to?: string;
    category_id?: string;
    status?: string;
    limit?: number;
    offset?: number;
  }) {
    const params = new URLSearchParams();
    
    if (filters) {
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== undefined) {
          params.append(key, String(value));
        }
      });
    }
    
    const queryString = params.toString();
    const endpoint = `/events${queryString ? `?${queryString}` : ''}`;
    
    return await this.request(endpoint);
  }

  async getEvent(id: string) {
    return await this.request(`/events/${id}`);
  }

  async createEvent(eventData: any) {
    return await this.request('/events', {
      method: 'POST',
      body: JSON.stringify(eventData),
    });
  }

  async updateEvent(id: string, eventData: any) {
    return await this.request(`/events/${id}`, {
      method: 'PUT',
      body: JSON.stringify(eventData),
    });
  }

  async deleteEvent(id: string) {
    await this.request(`/events/${id}`, {
      method: 'DELETE',
    });
  }

  async toggleEventStatus(id: string) {
    return await this.request(`/events/${id}/complete`, {
      method: 'POST',
    });
  }

  // Categories
  async getCategories() {
    return await this.request('/categories');
  }

  async createCategory(categoryData: { name: string; color: string; icon: string }) {
    return await this.request('/categories', {
      method: 'POST',
      body: JSON.stringify(categoryData),
    });
  }

  async updateCategory(id: string, categoryData: any) {
    return await this.request(`/categories/${id}`, {
      method: 'PUT',
      body: JSON.stringify(categoryData),
    });
  }

  async deleteCategory(id: string) {
    await this.request(`/categories/${id}`, {
      method: 'DELETE',
    });
  }

  // Documents (placeholder - implementare dopo)
  async getDocuments() {
    // TODO: implementare endpoint documenti
    return [];
  }

  async uploadDocument(file: File) {
    const formData = new FormData();
    formData.append('file', file);

    const headers: HeadersInit = {};
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    const response = await fetch(`${API_BASE_URL}/documents/upload`, {
      method: 'POST',
      headers,
      body: formData,
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error?.message || 'Errore upload');
    }

    return data.data;
  }
}

export const apiService = new ApiService();
