export type AuthRole = 'admin' | 'empresa'

export interface AuthUser {
  id: number
  email: string
  nombre: string
  role: AuthRole
  token: string
}
