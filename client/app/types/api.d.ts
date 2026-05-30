export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ApiError {
  status: number
  message: string
  errors: Record<string, string[]>
}

export interface User {
  id: number
  name: string
  email: string
  opd_id: number | null
  role?: 'admin' | 'verifikator' | 'operator'
  roles: string[]
  permissions: string[]
  sim_asn_user_id: string | null
  is_sim_asn_authenticated: boolean
  avatar_url?: string
}

export interface AuthResponse {
  user: User
  token: string
}