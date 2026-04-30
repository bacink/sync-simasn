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
  role: 'admin' | 'verifikator' | 'operator'
  avatar_url?: string
}