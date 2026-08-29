import axios, { AxiosError } from 'axios'
import type { AxiosInstance } from 'axios'

const API_URL = import.meta.env.VITE_API_URL ?? '/api'

class APIClient {
  private client: AxiosInstance
  private token: string | null = null

  constructor() {
    this.client = axios.create({
      baseURL: API_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    })

    this.token = localStorage.getItem('auth_token')
    if (this.token) {
      this.setToken(this.token)
    }

    this.client.interceptors.request.use((config) => {
      if (config.url && config.url.startsWith('/')) {
        config.url = config.url.substring(1)
      }
      if (config.baseURL && !config.baseURL.endsWith('/')) {
        config.baseURL += '/'
      }

      if (!config.headers) {
        config.headers = {} as any
      }

      if (this.token) {
        config.headers.Authorization = `Bearer ${this.token}`
        config.headers['X-Auth-Token'] = this.token
      }

      return config
    })

    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          this.clearToken()
          window.location.href = '/login'
        }

        return Promise.reject(error)
      },
    )
  }

  setToken(token: string) {
    this.token = token
    localStorage.setItem('auth_token', token)
    this.client.defaults.headers.common.Authorization = `Bearer ${token}`
    this.client.defaults.headers.common['X-Auth-Token'] = token
  }

  clearToken() {
    this.token = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    delete this.client.defaults.headers.common.Authorization
    delete this.client.defaults.headers.common['X-Auth-Token']
  }

  async login(email: string, password: string) {
    const response = await this.client.post('/auth/login.php', { email, password })
    return response.data
  }

  async logout() {
    try {
      const response = await this.client.post('/auth/logout.php')
      return response.data
    } finally {
      this.clearToken()
    }
  }

  async verifyToken() {
    const response = await this.client.post('/auth/verify-token.php')
    return response.data
  }
}

export const apiClient = new APIClient()
