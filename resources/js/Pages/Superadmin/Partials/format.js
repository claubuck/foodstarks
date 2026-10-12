export const fecha = (value) => (value ? new Date(value).toLocaleDateString('es-AR') : '—')

export const fechaHora = (value) =>
  value ? new Date(value).toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' }) : '—'

export const pesos = (value) =>
  new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(Number(value) || 0)

/** "hace 3 días" a partir de una fecha; "Nunca" si no hay. */
export function hace(value) {
  if (!value) return 'Nunca'
  const dias = Math.floor((Date.now() - new Date(value).getTime()) / 86400000)
  if (dias <= 0) return 'Hoy'
  if (dias === 1) return 'Ayer'
  if (dias < 30) return `Hace ${dias} días`
  return fecha(value)
}

export const roles = {
  owner: 'Dueño',
  admin: 'Admin',
  manager: 'Encargado',
  chef: 'Cocina',
  waiter: 'Mozo',
}
