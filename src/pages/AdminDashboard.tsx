import { ArrowRight, Building2, CarFront, FileText, LayoutDashboard, LogOut, Settings, Shield, Users } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'

export function AdminDashboard() {
  const navigate = useNavigate()
  const { user, logout } = useAuth()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const cards = [
    { title: 'Empresas', value: '128', icon: Building2, color: 'bg-blue-100 text-blue-700' },
    { title: 'Conductores', value: '340', icon: Users, color: 'bg-violet-100 text-violet-700' },
    { title: 'Vehículos', value: '412', icon: CarFront, color: 'bg-emerald-100 text-emerald-700' },
    { title: 'Permisos', value: '89', icon: FileText, color: 'bg-amber-100 text-amber-700' },
  ]

  return (
    <div className="min-h-screen bg-slate-100">
      <aside className="fixed inset-y-0 left-0 w-72 bg-[#0A2342] text-white">
        <div className="flex items-center gap-3 border-b border-white/10 p-6">
          <img src="/Marcona_Escudo.png" alt="Escudo Marcona" className="h-12 w-12 object-contain" />
          <div>
            <p className="text-sm font-bold">Marcona</p>
            <p className="text-[10px] uppercase tracking-[0.2em] text-slate-300">Movilidad</p>
          </div>
        </div>

        <nav className="space-y-2 p-4">
          <button className="flex w-full items-center gap-3 rounded-xl bg-white/10 px-4 py-3 text-sm font-semibold text-white">
            <LayoutDashboard className="h-4 w-4" />
            Dashboard
          </button>
          <button className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/5">
            <Building2 className="h-4 w-4" />
            Empresas
          </button>
          <button className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/5">
            <Users className="h-4 w-4" />
            Conductores
          </button>
          <button className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/5">
            <CarFront className="h-4 w-4" />
            Vehículos
          </button>
          <button className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/5">
            <FileText className="h-4 w-4" />
            Permisos
          </button>
          <button className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-white/5">
            <Settings className="h-4 w-4" />
            Configuración
          </button>
        </nav>

        <div className="absolute bottom-0 left-0 right-0 border-t border-white/10 p-4">
          <button
            onClick={handleLogout}
            className="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-300 hover:bg-red-500/10 hover:text-red-200"
          >
            <LogOut className="h-4 w-4" />
            Cerrar sesión
          </button>
        </div>
      </aside>

      <main className="ml-72 flex-1 p-8">
        <header className="mb-8 flex items-center justify-between">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Administración</p>
            <h1 className="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Dashboard principal</h1>
          </div>
          <div className="flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#0A2342] text-sm font-bold text-white">
              {user?.nombre?.slice(0, 1) ?? 'A'}
            </div>
            <div>
              <p className="text-sm font-bold text-slate-900">{user?.nombre ?? 'Administrador'}</p>
              <p className="text-xs text-slate-500">{user?.email ?? 'admin@marcona.pe'}</p>
            </div>
          </div>
        </header>

        <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {cards.map(({ title, value, icon: Icon, color }) => (
            <div key={title} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-slate-500">{title}</p>
                  <p className="mt-3 text-3xl font-black text-slate-900">{value}</p>
                </div>
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${color}`}>
                  <Icon className="h-5 w-5" />
                </div>
              </div>
            </div>
          ))}
        </section>

        <section className="mt-8 grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
          <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-slate-500">Resumen</p>
                <h2 className="mt-2 text-xl font-extrabold text-slate-900">Actividad del sistema</h2>
              </div>
              <Shield className="h-5 w-5 text-emerald-600" />
            </div>

            <div className="space-y-4">
              {[{ label: 'Licencias vigentes', value: '96%' }, { label: 'Vehículos activos', value: '89%' }, { label: 'Empresas con documentación', value: '91%' }].map((item) => (
                <div key={item.label}>
                  <div className="mb-2 flex items-center justify-between text-sm">
                    <span className="font-medium text-slate-600">{item.label}</span>
                    <span className="font-bold text-slate-900">{item.value}</span>
                  </div>
                  <div className="h-2.5 w-full rounded-full bg-slate-200">
                    <div className="h-2.5 rounded-full bg-[#0A2342]" style={{ width: item.value }} />
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-2xl bg-[#0A2342] p-6 text-white shadow-sm">
            <p className="text-sm uppercase tracking-[0.2em] text-slate-300">Estado</p>
            <h3 className="mt-3 text-2xl font-black">Sistema operativo</h3>
            <p className="mt-3 text-sm text-slate-300">Todos los módulos principales están activos y disponibles para administración.</p>
            <button className="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-[#0A2342]">
              Revisar panel
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
        </section>
      </main>
    </div>
  )
}
