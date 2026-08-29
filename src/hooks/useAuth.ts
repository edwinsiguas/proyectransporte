import { useCallback, useEffect, useState } from 'react'
import type { AuthUser } from '@/types'

const HARD_CODED_USER = {
  id: 1,
  email: 'admin@marcona.pe',
  nombre: 'Administrador',
  role: 'admin' as const,
  token: 'frontend-demo-token',
}

export function useAuth() {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const token = localStorage.getItem('auth_token')
    const userStr = localStorage.getItem('auth_user')

    if (!token || !userStr) {
      setUser(null)
      setLoading(false)
      return
    }

    try {
      const parsed = JSON.parse(userStr) as Partial<AuthUser>
      const hydrated: AuthUser = {
        id: Number(parsed.id ?? HARD_CODED_USER.id),
        email: String(parsed.email ?? HARD_CODED_USER.email),
        nombre: String(parsed.nombre ?? HARD_CODED_USER.nombre),
        role: (parsed.role as AuthUser['role']) ?? HARD_CODED_USER.role,
        token: String((parsed as any).token ?? token),
      }
      setUser(hydrated)
    } catch {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      setUser(null)
    }

    setLoading(false)
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    try {
      setError(null)
      setLoading(true)

      if (email.trim() !== HARD_CODED_USER.email || password !== 'Admin2024!') {
        throw new Error('Correo o contraseña incorrectos')
      }

      const userData: AuthUser = {
        ...HARD_CODED_USER,
        email: email.trim(),
      }

      localStorage.setItem('auth_token', userData.token)
      localStorage.setItem('auth_user', JSON.stringify(userData))
      setUser(userData)

      return userData
    } catch (err: any) {
      const message = err?.message || 'Error en login'
      setError(message)
      throw err
    } finally {
      setLoading(false)
    }
  }, [])

  const logout = useCallback(async () => {
    try {
      setLoading(true)
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      setUser(null)
    } catch (err: any) {
      const message = err?.message || 'Error en logout'
      setError(message)
      throw err
    } finally {
      setLoading(false)
    }
  }, [])

  const verifyToken = useCallback(async () => {
    return Boolean(localStorage.getItem('auth_token'))
  }, [])

  return {
    user,
    loading,
    error,
    login,
    logout,
    verifyToken,
    isAuthenticated: !!user,
  }
}
