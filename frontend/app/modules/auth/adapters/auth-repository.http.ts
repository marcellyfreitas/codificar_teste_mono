import type {
  AuthRepository,
  Credentials,
  Registration,
  User,
} from '../ports/auth-repository'
import type { HttpPort } from '../../core/ports/http'

interface AuthEnvelope {
  message: string,
  data: { user: User },
}

interface MeEnvelope {
  data: User,
}

export class HttpAuthRepository implements AuthRepository {
  constructor(private readonly http: HttpPort) {}

  async login(credentials: Credentials): Promise<User> {
    const envelope = await this.http.post<AuthEnvelope>('/login', credentials)
    return envelope.data.user
  }

  async register(payload: Registration): Promise<User> {
    const envelope = await this.http.post<AuthEnvelope>('/register', payload)
    return envelope.data.user
  }

  async me(): Promise<User> {
    const envelope = await this.http.get<MeEnvelope>('/me')
    return envelope.data
  }
}
