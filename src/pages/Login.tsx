import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { Loader2, ShieldCheck } from 'lucide-react'
import { useAuth } from '@/hooks/useAuth'

const loginSchema = z.object({
  email: z.string().trim().email('Email inválido'),
  password: z.string().trim().min(6, 'La contraseña debe tener al menos 6 caracteres'),
})

type LoginFormData = z.infer<typeof loginSchema>

export function LoginPage() {
  const navigate = useNavigate()
  const { login } = useAuth()
  const [isLoading, setIsLoading] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  const form = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: 'admin@marcona.pe',
      password: 'Admin2024!',
    },
  })

  async function onSubmit(data: LoginFormData) {
    try {
      setIsLoading(true)
      setErrorMessage('')
      await login(data.email, data.password)
      navigate('/dashboard')
    } catch (error: any) {
      setErrorMessage(error?.message || 'Credenciales incorrectas')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex w-full bg-white">
      <div className="hidden lg:flex lg:w-1/2 bg-[#0A2342] flex-col justify-between p-12 relative overflow-hidden">
        <div className="absolute inset-0 opacity-10 pointer-events-none">
          <svg className="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="grid-pattern" width="40" height="40" patternUnits="userSpaceOnUse">
                <path d="M0 40V0H40" fill="none" stroke="white" strokeWidth="1" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-pattern)" />
          </svg>
        </div>

        <div className="relative z-10 max-w-lg mb-12">
          <img src="/Marcona_Escudo.png" alt="Escudo de Marcona" className="w-32 h-32 object-contain mb-8 drop-shadow-2xl" />
          <h1 className="text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-4">
            Gestión de<br />Movilidad Urbana
          </h1>
          <p className="text-slate-300 text-lg leading-relaxed font-medium">
            Plataforma oficial para el registro, control y fiscalización del transporte público y privado de la Municipalidad Distrital de Marcona.
          </p>
        </div>

        <div className="relative z-10 flex items-center gap-2">
          <ShieldCheck className="w-5 h-5 text-emerald-400" />
          <span className="text-sm font-semibold text-slate-300">Acceso Seguro & Encriptado</span>
        </div>
      </div>

      <div className="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 relative bg-white">
        <div className="lg:hidden absolute top-8 left-8 flex items-center gap-3">
          <img src="/Marcona_Escudo.png" alt="Escudo Marcona" className="w-10 h-10 object-contain drop-shadow-sm" />
          <div className="flex flex-col">
            <span className="font-extrabold text-[#0A2342] text-sm leading-tight">Marcona</span>
            <span className="text-[10px] text-slate-500 font-semibold uppercase tracking-wider mt-0.5">Gestión de Movilidad</span>
          </div>
        </div>

        <div className="w-full max-w-md mx-auto space-y-8">
          <div className="text-center lg:text-left">
            <h2 className="text-3xl font-extrabold tracking-tight text-[#0A2342]">Bienvenido de vuelta</h2>
            <p className="text-slate-500 font-medium mt-2">
              Ingresa tus credenciales para acceder al panel de administración.
            </p>
          </div>

          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5">
            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wider">Correo Electrónico</label>
              <input
                type="email"
                placeholder="Correo corporativo"
                className="h-14 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 text-base transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0A2342]/20"
                {...form.register('email')}
              />
              {form.formState.errors.email && (
                <p className="text-sm text-red-600">{form.formState.errors.email.message}</p>
              )}
            </div>

            <div className="space-y-2">
              <label className="text-xs font-bold text-slate-700 uppercase tracking-wider">Contraseña</label>
              <input
                type="password"
                placeholder="••••••••"
                className="h-14 w-full rounded-xl bg-slate-50 border border-slate-200 px-4 text-base transition-colors focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0A2342]/20 font-mono tracking-widest"
                {...form.register('password')}
              />
              {form.formState.errors.password && (
                <p className="text-sm text-red-600">{form.formState.errors.password.message}</p>
              )}
            </div>

            {errorMessage && (
              <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {errorMessage}
              </div>
            )}

            <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
              Usuario demo: admin@marcona.pe <br />
              Contraseña: Admin2024!
            </div>

            <button
              type="submit"
              className="w-full bg-[#0A2342] hover:bg-[#0A2342]/90 text-white h-14 rounded-xl text-base font-bold shadow-md shadow-[#0A2342]/20 mt-2 disabled:opacity-60"
              disabled={isLoading}
            >
              {isLoading && <Loader2 className="mr-3 h-5 w-5 animate-spin text-white inline" />}
              Ingresar al Sistema
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
